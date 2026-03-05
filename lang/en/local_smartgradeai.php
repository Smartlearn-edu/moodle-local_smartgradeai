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

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Smart Grade AI';
$string['availablemodels'] = 'Available AI Models';
$string['availablemodels_desc'] = 'List of AI models available for selection in assignment settings. Enter one model per line. These values will be passed to the n8n webhook.';
$string['n8n_url'] = 'n8n Webhook URL';
$string['n8n_url_desc'] = 'The URL of the n8n webhook to trigger grading.';
$string['n8n_token'] = 'n8n Token';
$string['n8n_token_desc'] = 'Security token to send with the request.';
$string['grade_with_ai_button'] = 'Grade with AI';
$string['settings_link'] = 'AI Grader Settings';
$string['system_message'] = 'System Message';
$string['system_message_help'] = 'Instructions for the AI agent.';
$string['ai_agent'] = 'Preferred AI Agent';
$string['complexity'] = 'Complexity';
$string['subject_general'] = 'General / Humanities';
$string['subject_math'] = 'Mathematics & Logic';
$string['subject_programming'] = 'Programming & Computer Science';
$string['subject_medical'] = 'Medical & Health Sciences';
$string['subject_science'] = 'Natural Sciences';
$string['subject_law'] = 'Law & Legal Studies';
$string['subject_creative'] = 'Creative Writing';

// AI Agents
$string['agent_gemini_3_0_pro'] = 'Gemini 3.0 Pro';
$string['agent_gemini_3_0_flash'] = 'Gemini 3.0 Flash';
$string['agent_gemini_3_0_high'] = 'Gemini 3.0 High (Reasoning)';
$string['agent_gemini_2_0_flash'] = 'Gemini 2.0 Flash';

$string['agent_gpt_5'] = 'GPT-5';
$string['agent_gpt_4o'] = 'GPT-4o';
$string['agent_o1_high'] = 'OpenAI o1 (High Reasoning)';
$string['agent_o1_mini'] = 'OpenAI o1 Mini';

$string['agent_claude_3_7_sonnet'] = 'Claude 3.7 Sonnet';
$string['agent_claude_3_5_opus'] = 'Claude 3.5 Opus';
$string['agent_claude_3_5_sonnet'] = 'Claude 3.5 Sonnet';

$string['agent_deepseek_v3'] = 'DeepSeek V3';
$string['agent_deepseek_coder'] = 'DeepSeek Coder V2';

$string['agent_llama_4_405b'] = 'Llama 4 405B';
$string['agent_llama_4_70b'] = 'Llama 4 70B';

$string['agent_grok_3'] = 'Grok 3';
$string['agent_mistral_large'] = 'Mistral Large';
$string['agent_ollama'] = 'Ollama';
$string['trigger_success'] = 'Grading triggered successfully!';
$string['trigger_error'] = 'Error triggering grading: {$a}';
$string['enable_student_button'] = 'Enable Student Feedback Button';
$string['enable_student_button_desc'] = 'If enabled, students will see a "Check AI Feedback" button on their submission status page.';
$string['enable_review_mode'] = 'Enable Review Mode (Human-in-the-Loop)';
$string['enable_review_mode_desc'] = 'If enabled, allows teachers to switch on "Review Mode" for assignments. In this mode, AI grades are saved as drafts requiring teacher approval.';
$string['review_mode'] = 'Review Mode';
$string['review_mode_desc'] = 'If enabled, AI grades will be saved as drafts and must be approved by a teacher before becoming final.';

// Privacy
$string['privacy:metadata:external:summary'] = 'To automatically grade assignments, this plugin sends data to an external AI service webhook.';
$string['privacy:metadata:external:userid'] = 'The ID of the user whose submission is being graded or who requested feedback.';
$string['privacy:metadata:external:courseid'] = 'The ID of the course containing the assignment.';
$string['privacy:metadata:external:assignmentid'] = 'The ID of the assignment.';
$string['privacy:metadata:external:submissionid'] = 'The ID of the submission being evaluated.';

$string['privacy:metadata:reviews:summary'] = 'Stores temporary pending AI grades for human review.';
$string['privacy:metadata:reviews:userid'] = 'The user whose submission was graded.';
$string['privacy:metadata:reviews:assignmentid'] = 'The assignment the submission belongs to.';
$string['privacy:metadata:reviews:submissionid'] = 'The submission being reviewed.';
$string['privacy:metadata:reviews:grade'] = 'The AI proposed grade.';
$string['privacy:metadata:reviews:rubric_data'] = 'The AI proposed rubric breakdown.';

$string['privacy:metadata:jobs:summary'] = 'Tracks the status of background AI grading and feedback requests.';
$string['privacy:metadata:jobs:submissionid'] = 'The submission the job relates to.';
$string['privacy:metadata:jobs:status'] = 'The current status of the AI request.';
$string['privacy:metadata:jobs:error_message'] = 'Any errors returned by the AI service.';
