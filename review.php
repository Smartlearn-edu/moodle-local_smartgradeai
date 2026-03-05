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
 * Detail page to review a specific AI grade.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

global $DB, $PAGE, $OUTPUT, $USER;

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

// Check login
require_login();

$review = $DB->get_record('local_smartgradeai_reviews', ['id' => $id], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assign', $review->assignmentid);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

$PAGE->set_cm($cm, $course);
$PAGE->set_context($context);

require_capability('mod/assign:grade', $context);

// Handle POST actions (Approve/Reject)
if ($action && confirm_sesskey()) {
    require_once($CFG->dirroot . '/local/smartgradeai/classes/external/process_review.php');

    try {
        // Call the external function logic directly
        $result = \local_smartgradeai\external\process_review::execute($id, $action);

        if ($result['success']) {
            redirect(new moodle_url('/local/smartgradeai/reviews.php'), $result['message'], null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            $notification = new \core\output\notification($result['message'], \core\output\notification::NOTIFY_ERROR);
        }
    } catch (Exception $e) {
        $notification = new \core\output\notification($e->getMessage(), \core\output\notification::NOTIFY_ERROR);
    }
}

// Setup Page
$PAGE->set_context($context); // Set to assignment context so permissions check works
$url = new moodle_url('/local/smartgradeai/review.php', ['id' => $id]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('reviewaigrade', 'local_smartgradeai'));
$PAGE->set_heading(get_string('reviewaigrade', 'local_smartgradeai'));
$PAGE->set_pagelayout('incourse'); // Show within course context? Or report? Incourse is better contextually.

echo $OUTPUT->header();

if (isset($notification)) {
    echo $OUTPUT->render($notification);
}

$user = $DB->get_record('user', ['id' => $review->userid], '*', MUST_EXIST);

echo $OUTPUT->heading(get_string('submissionby', 'local_smartgradeai', fullname($user)));

// Display Rubric Preview
$rubric_data = json_decode($review->rubric_data, true);

if ($rubric_data) {
    echo html_writer::start_tag('div', ['class' => 'card mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card-body']);
    echo html_writer::tag('h5', get_string('aiproposedrubricscore', 'local_smartgradeai'), ['class' => 'card-title']);

    $table = new html_table();
    $table->head = [
        get_string('criterionid', 'local_smartgradeai'),
        get_string('levelid', 'local_smartgradeai'),
        get_string('remark', 'local_smartgradeai'),
    ];
    $table->data = [];

    foreach ($rubric_data as $item) {
        $table->data[] = [
            $item['criterionid'] ?? 'N/A',
            $item['levelid'] ?? 'N/A',
            $item['remark'] ?? ''
        ];
    }
    echo html_writer::table($table);

    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

// Actions Form
echo html_writer::start_tag('div', ['class' => 'd-flex gap-2']);

// Approve Button
$approve_url = new moodle_url($url, ['action' => 'approve', 'sesskey' => sesskey()]);
echo $OUTPUT->single_button($approve_url, get_string('approvesavetogradebook', 'local_smartgradeai'), 'post', ['class' => 'btn-success']);

// Reject Button
$reject_url = new moodle_url($url, ['action' => 'reject', 'sesskey' => sesskey()]);
echo $OUTPUT->single_button($reject_url, get_string('rejectdeletedraft', 'local_smartgradeai'), 'post', ['class' => 'btn-danger']);

// Cancel/Back
$back_url = new moodle_url('/local/smartgradeai/reviews.php');
echo $OUTPUT->single_button($back_url, get_string('cancel', 'local_smartgradeai'), 'get', ['class' => 'btn-secondary']);

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
