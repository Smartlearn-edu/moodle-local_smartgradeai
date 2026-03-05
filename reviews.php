<?php

/**
 * Dashboard to review pending AI grades.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT, $USER;

// Check login
require_login();

// Set context (System context for plugin dashboard, usually)
$context = context_system::instance();
$PAGE->set_context($context);

$url = new moodle_url('/local/smartgradeai/reviews.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_smartgradeai') . ': Pending Reviews');
$PAGE->set_heading('Pending AI Reviews');

// Custom simplified layout (admin like)
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

// Fetch pending reviews
// Join with assignment and user tables for more info
$sql = "SELECT r.id, r.assignmentid, r.submissionid, r.userid, r.timecreated,
               a.name as assignmentname, c.fullname as coursename, c.id as courseid,
               u.firstname, u.lastname
        FROM {local_smartgradeai_reviews} r
        JOIN {assign} a ON a.id = r.assignmentid
        JOIN {course} c ON c.id = a.course
        JOIN {user} u ON u.id = r.userid
        WHERE r.status = :status
        ORDER BY r.timecreated ASC";

$all_reviews = $DB->get_records_sql($sql, ['status' => 'pending']);

$reviews = [];
if ($all_reviews) {
    // Filter reviews to only those the current user has permission to grade
    foreach ($all_reviews as $review) {
        $coursecontext = context_course::instance($review->courseid);
        if (has_capability('mod/assign:grade', $coursecontext)) {
            $reviews[] = $review;
        }
    }
}

// Block students completely. If they have no pending reviews, we still need to
// ensure they are at least a teacher in some course, or an admin.
$is_admin = has_capability('moodle/site:config', context_system::instance());
$can_grade_somewhere = false;

// If they have reviews, they obviously can grade somewhere.
if (!empty($reviews)) {
    $can_grade_somewhere = true;
} else {
    // Check if they have assign:grade in ANY course context
    // This is a bit heavy, but since they have no reviews, it's just a general access check.
    // A simpler way is just to check if they have moodle/grade:viewall at system/coursecat level,
    // or rely on the fact that if they are just a student, they shouldn't be here.
    if ($is_admin) {
        $can_grade_somewhere = true;
    } else {
        // Find if they are enrolled as a teacher anywhere
        $teacher_courses = enrol_get_users_courses($USER->id, true, 'id');
        foreach ($teacher_courses as $tc) {
            $coursecontext = context_course::instance($tc->id);
            if (has_capability('mod/assign:grade', $coursecontext)) {
                $can_grade_somewhere = true;
                break;
            }
        }
    }
}

if (!$is_admin && !$can_grade_somewhere) {
    // Throw standard capability exception to block the page load completely
    require_capability('mod/assign:grade', context_system::instance());
}

if (empty($reviews)) {
    echo $OUTPUT->notification('No pending reviews found. Good job!', 'success');
} else {
    echo html_writer::tag('h3', 'Pending Reviews (' . count($reviews) . ')');

    $table = new html_table();
    $table->head = ['Student', 'Assignment', 'Course', 'Waiting Since', 'Action'];
    $table->data = [];

    foreach ($reviews as $review) {
        $user_fullname = fullname($review);
        $review_url = new moodle_url('/local/smartgradeai/review.php', ['id' => $review->id]);
        $time_waiting = userdate($review->timecreated);

        // Action Button
        $btn = $OUTPUT->single_button($review_url, 'Review', 'get');

        $table->data[] = [
            $user_fullname,
            format_string($review->assignmentname),
            format_string($review->coursename),
            $time_waiting,
            $btn
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
