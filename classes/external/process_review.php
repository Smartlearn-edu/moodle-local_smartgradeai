<?php

/**
 * External function to process (approve/reject) a review.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_smartgradeai\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_system;
use local_smartgradeai\grader_helper;

class process_review extends external_api
{

    /**
     * Parameters for execute
     */
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'reviewid' => new external_value(PARAM_INT, 'The ID of the review record'),
            'action' => new external_value(PARAM_ALPHA, 'Action: approve or reject')
        ]);
    }

    /**
     * Processing method
     *
     * @param int $reviewid
     * @param string $action
     * @return array
     */
    public static function execute($reviewid, $action)
    {
        global $DB, $USER;

        // Validation
        $params = self::validate_parameters(self::execute_parameters(), [
            'reviewid' => $reviewid,
            'action' => $action
        ]);

        $reviewid = $params['reviewid'];
        $action = $params['action'];

        // Context check (System context for now, or finding the specific assignment context)
        // Since reviews spans courses, we check system capability or verify user is teacher in that assignment.
        // For simplicity/MVP: User must have 'mod/assign:grade' in the assignment context.

        $review = $DB->get_record('local_smartgradeai_reviews', ['id' => $reviewid], '*', MUST_EXIST);

        $cm = get_coursemodule_from_instance('assign', $review->assignmentid);
        if (!$cm) {
            throw new \moodle_exception('invalidcoursemodule');
        }
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        if ($review->status !== 'pending') {
            return [
                'success' => false,
                'message' => 'This review has already been processed.'
            ];
        }

        if ($action === 'approve') {
            // Decode rubric data
            $rubric_items = json_decode($review->rubric_data, true);
            if (!is_array($rubric_items)) {
                return ['success' => false, 'message' => 'Invalid rubric data in review record.'];
            }

            // Call Grader Helper to save to Gradebook
            $result = grader_helper::save_rubric_grade(
                $review->assignmentid,
                $review->userid,
                $rubric_items,
                $USER->id // The teacher approving it becomes the grader
            );

            if ($result['success']) {
                // Update review status
                $review->status = 'approved';
                $review->graderid = $USER->id;
                $review->timemodified = time();
                $DB->update_record('local_smartgradeai_reviews', $review);

                return [
                    'success' => true,
                    'message' => 'Grade approved and saved to Gradebook.'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error saving to Gradebook: ' . $result['message']
                ];
            }
        } elseif ($action === 'reject') {
            // Just update status to rejected
            $review->status = 'rejected';
            $review->graderid = $USER->id; // Who rejected it
            $review->timemodified = time();
            $DB->update_record('local_smartgradeai_reviews', $review);

            return [
                'success' => true,
                'message' => 'Review rejected. No grade was saved.'
            ];
        } else {
            throw new \invalid_parameter_exception('Invalid action');
        }
    }

    /**
     * Return structure
     */
    public static function execute_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message')
        ]);
    }
}
