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
 * Smart Grade AI library functions.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the settings navigation to add the AI Grader Settings link.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_smartgradeai_extend_settings_navigation(settings_navigation $settingsnav, context $context)
{
    global $PAGE, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE || !isset($PAGE->cm) || $PAGE->cm->modname !== 'assign') {
        return;
    }

    $assignmentid = (int)$PAGE->cm->instance;
    $courseid = (int)$PAGE->course->id;
    $userid = (int)$USER->id;
    $submissionid = 0;
    $attemptnumber = 0;
    $submissionstatus = 'new';

    // Check if the current user has a submission.
    if ($submission = $DB->get_record('assign_submission', ['assignment' => $assignmentid, 'userid' => $userid, 'latest' => 1])) {
        $submissionid = (int)$submission->id;
        if (isset($submission->attemptnumber)) {
            $attemptnumber = (int)$submission->attemptnumber;
        }
        $submissionstatus = $submission->status;
        if ($submission->status === 'new') {
            $submissionid = 0;
        }
    }

    $isteacher = has_capability('mod/assign:grade', $context);

    // 1. TEACHER LOGIC (Settings + Grade Button).
    if ($isteacher) {
        $url = new moodle_url('/local/smartgradeai/settings_page.php', [
            'courseid' => $courseid,
            'assignmentid' => $assignmentid
        ]);

        $node = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
        if ($node) {
            $node->add(
                get_string('settings_link', 'local_smartgradeai'),
                $url,
                navigation_node::TYPE_SETTING
            );

            // Link to Pending Reviews Dashboard.
            if (get_config('local_smartgradeai', 'enable_review_mode')) {
                $review_url = new moodle_url('/local/smartgradeai/reviews.php');
                $node->add(
                    'Pending AI Reviews',
                    $review_url,
                    navigation_node::TYPE_SETTING,
                    null,
                    'local_smartgradeai_reviews',
                    new pix_icon('i/grades', '')
                );
            }
        }

        // Inject AMD Module for teachers.
        $PAGE->requires->js_call_amd('local_smartgradeai/grader', 'init', [[
            'assignmentid' => $assignmentid,
            'courseid' => $courseid,
            'userid' => $userid,
            'submissionid' => $submissionid,
            'isteacher' => true,
        ]]);
    }

    // 2. STUDENT LOGIC (Check Feedback Button).
    $action = optional_param('action', '', PARAM_ALPHA);
    if (!$isteacher && $action !== 'editsubmission') {
        // Check if enabled by teacher.
        $opts = $DB->get_record('local_smartgradeai_opts', ['assignmentid' => $assignmentid]);
        if (!$opts || empty($opts->enable_student_button)) {
            return;
        }

        $job_status = 'ready';
        $job_time = 0;
        $is_graded = false;
        $grade_record = null;

        if ($submissionid) {
            $job = $DB->get_record('local_smartgradeai_jobs', ['submissionid' => $submissionid]);
            $job_status = $job ? $job->status : 'ready';
            $job_time = $job ? (int)$job->timemodified : 0;

            $grade_record = $DB->get_record('assign_grades', [
                'assignment' => $assignmentid,
                'userid' => $userid,
                'attemptnumber' => $attemptnumber,
            ]);
            $is_graded = ($grade_record && $grade_record->grade >= 0);
        }

        $has_passed = false;
        $gradeitem = $DB->get_record('grade_items', [
            'courseid' => $courseid,
            'itemtype' => 'mod',
            'itemmodule' => 'assign',
            'iteminstance' => $assignmentid,
            'itemnumber' => 0,
        ]);
        if ($gradeitem && $gradeitem->gradepass > 0 && $is_graded && $grade_record) {
            if ($grade_record->grade >= $gradeitem->gradepass) {
                $has_passed = true;
            }
        }

        $assign_record = $DB->get_record('assign', ['id' => $assignmentid], 'maxattempts');
        $maxattempts = $assign_record ? (int)$assign_record->maxattempts : 1;

        // Inject AMD Module for students.
        $PAGE->requires->js_call_amd('local_smartgradeai/grader', 'init', [[
            'assignmentid' => $assignmentid,
            'courseid' => $courseid,
            'userid' => $userid,
            'submissionid' => $submissionid,
            'submissionstatus' => $submissionstatus,
            'jobstatus' => $job_status,
            'jobtime' => $job_time,
            'now' => time(),
            'isgraded' => $is_graded,
            'haspassed' => $has_passed,
            'hassubmission' => ($submissionid > 0),
            'maxattempts' => $maxattempts,
            'attemptnumber' => $attemptnumber,
            'isteacher' => false,
        ]]);
    }
}
