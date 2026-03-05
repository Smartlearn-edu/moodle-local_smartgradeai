/**
 * Smart Grade AI AMD module.
 *
 * @module     local_smartgradeai/grader
 * @package    local_smartgradeai
 * @copyright  2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function ($, Ajax, Notification, Str) {
    return {
        init: function (params) {
            if (!params || typeof params !== 'object' || !params.assignmentid) {
                return;
            }

            var assignmentId = params.assignmentid;
            var courseId = params.courseid;
            var userId = params.userid;
            var submissionId = params.submissionid;
            var isTeacher = params.isteacher;

            $(document).ready(function () {
                // 1. TEACHER BUTTON.
                if (isTeacher) {
                    Str.get_string('grade_with_ai_button', 'local_smartgradeai').done(function (buttonLabel) {
                        var button = $('<button class="btn btn-primary ml-2">' + buttonLabel + '</button>');
                        button.click(function (e) {
                            e.preventDefault();
                            button.prop('disabled', true);

                            Ajax.call([{
                                methodname: 'local_smartgradeai_trigger_grading',
                                args: { assignmentid: assignmentId }
                            }])[0].done(function (response) {
                                if (response.success) {
                                    Str.get_string('trigger_success', 'local_smartgradeai').done(function (s) {
                                        Notification.alert('Success', s, 'Ok');
                                    });
                                } else {
                                    Notification.alert('Error', response.message, 'Ok');
                                }
                            }).fail(Notification.exception).always(function () {
                                button.prop('disabled', false);
                            });
                        });

                        // Append to the page.
                        if ($('.grading-actions-form').length) {
                            $('.grading-actions-form').append(button);
                        } else if ($('.submissionlinks').length) {
                            $('.submissionlinks').append(button);
                        } else {
                            var container = $('.submissionstatustable').first();
                            if (!container.length) {
                                container = $('.gradingtable').first();
                            }
                            if (!container.length) {
                                container = $('[role="main"]');
                            }
                            container.before(button);
                        }
                    });
                }

                // 2. STUDENT BUTTON.
                if (!isTeacher) {
                    // Avoid duplicates.
                    if ($('#smartgradeai-student-btn').length) {
                        return;
                    }

                    var btnLabel = 'Check AI Feedback';
                    var isInitiallyDisabled = false;
                    var btnClass = 'btn btn-info ml-2';

                    // Multi-attempt logic.
                    var maxAttempts = params.maxattempts || 1;
                    var attemptNum = params.attemptnumber || 0;
                    var isMultiAttempt = (maxAttempts === -1) || (maxAttempts > 1);
                    var hasChance = (maxAttempts === -1) || ((attemptNum + 1) < maxAttempts);
                    var enableOverride = isMultiAttempt && hasChance;

                    if (!params.hassubmission || params.submissionstatus !== 'submitted') {
                        btnLabel = 'Submit to use AI Feedback';
                        isInitiallyDisabled = true;
                        btnClass = 'btn btn-secondary ml-2';
                    } else if (params.haspassed) {
                        btnLabel = 'Passed - Great Job!';
                        isInitiallyDisabled = true;
                        btnClass = 'btn btn-success ml-2';
                    } else if (params.isgraded && !enableOverride) {
                        btnLabel = 'Feedback Available';
                        isInitiallyDisabled = true;
                        btnClass = 'btn btn-success ml-2';
                    } else if (params.jobstatus === 'pending') {
                        var diff = params.now - params.jobtime;
                        if (diff < 600) {
                            var minsLeft = Math.ceil((600 - diff) / 60);
                            btnLabel = 'AI is thinking... (' + minsLeft + 'm)';
                            isInitiallyDisabled = true;
                            btnClass = 'btn btn-secondary ml-2';
                        }
                    }

                    var studentButton = $('<button id="smartgradeai-student-btn" class="' + btnClass + '">' + btnLabel + '</button>');
                    if (isInitiallyDisabled) {
                        studentButton.prop('disabled', true);
                    }

                    studentButton.click(function (e) {
                        e.preventDefault();
                        studentButton.prop('disabled', true);

                        Ajax.call([{
                            methodname: 'local_smartgradeai_check_feedback',
                            args: {
                                submissionid: submissionId,
                                assignmentid: assignmentId,
                                courseid: courseId,
                                userid: userId
                            }
                        }])[0].done(function (response) {
                            if (response.success) {
                                Notification.alert('Feedback', response.message || 'Feedback request sent!', 'Ok');
                                studentButton.text('Feedback request sent to AI Agent.');
                                studentButton.prop('disabled', true);
                                studentButton.removeClass('btn-info').addClass('btn-secondary');
                            } else {
                                Notification.alert('Info', response.message || 'No feedback available yet.', 'Ok');
                                studentButton.prop('disabled', false);
                            }
                        }).fail(function () {
                            Notification.alert('Error', 'Could not fetch feedback status.', 'Ok');
                            studentButton.prop('disabled', false);
                        });
                    });

                    // Append to the page.
                    if ($('.submissionlinks').length) {
                        $('.submissionlinks').append(studentButton);
                    } else if ($('.submissionstatustable').length) {
                        $('.submissionstatustable').first().after(studentButton);
                    } else if ($('[role="main"]').length) {
                        $('[role="main"]').append(studentButton);
                    }
                }
            });
        }
    };
});