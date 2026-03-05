<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Smart Grade AI plugin.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_smartgradeai\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_module;
use assign;

class save_rubric_grade extends external_api
{

    /**
     * Returns description of method parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'The assignment ID'),
            'userid' => new external_value(PARAM_INT, 'The student user ID'),
            'rubric_data' => new external_value(PARAM_RAW, 'JSON encoded string or array of rubric criteria')
        ]);
    }

    /**
     * Execution method
     */
    public static function execute($assignmentid, $userid, $rubric_data)
    {
        global $DB, $USER, $CFG;

        // Parameter validation
        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'userid' => $userid,
            'rubric_data' => $rubric_data
        ]);

        $assignmentid = $params['assignmentid'];
        $userid = $params['userid'];
        $raw_rubric_data = $params['rubric_data'];

        // Parse rubric_data
        $rubric_items = [];
        if (is_string($raw_rubric_data)) {
            $decoded = json_decode($raw_rubric_data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $rubric_items = $decoded;
            } else {
                throw new \invalid_parameter_exception('Invalid JSON format for rubric_data');
            }
        } elseif (is_array($raw_rubric_data)) {
            $rubric_items = $raw_rubric_data;
        } else {
            throw new \invalid_parameter_exception('rubric_data must be a valid JSON string or array');
        }

        // Get context
        $cm = get_coursemodule_from_instance('assign', $assignmentid);
        if (!$cm) {
            throw new \moodle_exception('invalidcoursemodule');
        }
        $context = context_module::instance($cm->id);

        // Validate capability
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        // --- NEW REVIEW MODE LOGIC ---

        // Check if Review Mode is enabled for this assignment
        $is_review_mode = false;
        // First check system setting
        if (get_config('local_smartgradeai', 'enable_review_mode')) {
            // Then check assignment setting
            $opts = $DB->get_record('local_smartgradeai_opts', ['assignmentid' => $assignmentid]);
            if ($opts && !empty($opts->review_mode)) {
                $is_review_mode = true;
            }
        }

        if ($is_review_mode) {
            // Save as draft to `local_smartgradeai_reviews`

            // Identify submission ID (needed for the table)
            // We need to look up the submission for this user/assignment
            $submission = $DB->get_record('assign_submission', [
                'assignment' => $assignmentid,
                'userid' => $userid,
                'latest' => 1
            ]);

            if (!$submission) {
                return ['success' => false, 'message' => 'No submission found for this user to review.'];
            }
            $submissionid = $submission->id;

            $review = new \stdClass();
            $review->assignmentid = $assignmentid;
            $review->submissionid = $submissionid;
            $review->userid = $userid;
            $review->graderid = 0; // 0 = AI
            $review->rubric_data = json_encode($rubric_items);
            $review->feedback_text = ''; // Add logic if we have general feedback later
            $review->status = 'pending';
            $review->timemodified = time();

            // Check if existing pending review exists?
            // Strategy: Overwrite existing pending review for same submission
            $existing = $DB->get_record('local_smartgradeai_reviews', [
                'submissionid' => $submissionid,
                'status' => 'pending'
            ]);

            if ($existing) {
                $review->id = $existing->id;
                $DB->update_record('local_smartgradeai_reviews', $review);
            } else {
                $review->timecreated = time();
                $DB->insert_record('local_smartgradeai_reviews', $review);
            }

            // Update Job Status
            // IMPORTANT: We should mark the job as 'review_pending' or similar if we want to track it precisely.
            // For now, let's update local_smartgradeai_jobs to 'done' (AI part is done) 
            // OR maybe 'review' status? 
            // The job table is mostly for the student button spinner. Use 'done' so the spinner stops?
            // If we use 'done', the button might enable "Check Feedback", which might show "Pending Review".
            // Let's stick to 'done' for simplicity in this step.
            $job = $DB->get_record('local_smartgradeai_jobs', ['submissionid' => $submissionid]);
            if ($job) {
                $job->status = 'done';
                $job->timemodified = time();
                $DB->update_record('local_smartgradeai_jobs', $job);
            }

            return [
                'success' => true,
                'message' => 'AI grading completed and saved as draft for teacher approval.'
            ];
        } else {
            // NORMAL MODE: Save to Gradebook directly
            return \local_smartgradeai\grader_helper::save_rubric_grade($assignmentid, $userid, $rubric_items, $USER->id);
        }
    }

    /**
     * Returns description of method result value
     */
    public static function execute_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if successful'),
            'message' => new external_value(PARAM_TEXT, 'Result message')
        ]);
    }

    /**
     * Calculate the maximum possible score for a rubric
     * 
     * @param object $DB Database object
     * @param int $definitionid The rubric definition ID
     * @return float Maximum possible score
     */
    private static function get_rubric_max_score($DB, $definitionid)
    {
        $criteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $definitionid]);
        $max_score = 0;

        foreach ($criteria as $criterion) {
            // Get the highest score level for this criterion
            $max_level = $DB->get_field_sql(
                'SELECT MAX(score) FROM {gradingform_rubric_levels} WHERE criterionid = ?',
                [$criterion->id]
            );
            $max_score += (float)$max_level;
        }

        return $max_score;
    }
}
