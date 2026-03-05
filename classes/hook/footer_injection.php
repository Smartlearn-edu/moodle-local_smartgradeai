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


namespace local_smartgradeai\hook;

use core\hook\output\before_footer_html_generation;

defined('MOODLE_INTERNAL') || die();

class footer_injection
{
    public static function callback(before_footer_html_generation $hook)
    {
        global $PAGE;

        // Only run on assignment pages.
        if (strpos($PAGE->pagetype, 'mod-assign-') !== 0) {
            return;
        }

        $context = $PAGE->context;
        if ($context->contextlevel == CONTEXT_MODULE && isset($PAGE->cm) && $PAGE->cm->modname === 'assign') {
            if (has_capability('mod/assign:grade', $context)) {
                $PAGE->requires->js_call_amd('local_smartgradeai/grader', 'init', [[
                    'assignmentid' => (int)$PAGE->cm->instance,
                    'courseid' => (int)$PAGE->course->id,
                    'isteacher' => true,
                ]]);
            }
        }
    }
}
