<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version metadata for the block_pluginname plugin.
 *
 * @package   block_attendance_by_face
 * @copyright 2023, Brain Station 23 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

require_login();

$PAGE->set_url(new moodle_url('/blocks/attendance_by_face/manage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('manage_page_title', 'block_attendance_by_face'));

if (!is_siteadmin() && !block_is_manager() && !block_is_coursecreator() && !block_is_teacher()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'block_attendance_by_face'), null, \core\output\notification::NOTIFY_ERROR);
}

$courseid = optional_param('cid', 0, PARAM_INT);
if ($courseid <= 0) {
    redirect($CFG->wwwroot, 'No course selected', null, \core\output\notification::NOTIFY_WARNING);
}

global $DB;
$sql = "SELECT u.id id, (u.username) 'student', u.firstname , u.lastname, u.email
        FROM {role_assignments} r
        JOIN {user} u on r.userid = u.id
        JOIN {role} rn on r.roleid = rn.id
        JOIN {context} ctx on r.contextid = ctx.id
        JOIN {course} c on ctx.instanceid = c.id
        WHERE rn.shortname = 'student'
        AND c.id=" . $courseid;

$studentdata = $DB->get_records_sql($sql);

// Check if there is any active session and the student is present or not.
foreach($studentdata as $student) {
    $activesession = $DB->get_record('block_attendance_piu_window', array('course_id' => $courseid, 'active' => 1));
    if($activesession) {
        $student->session = true;
        $student->session_id = $activesession->session_id;
        $record = $DB->get_record('block_attendance_fc_recog', array('student_id' => $student->id, 'session_id' => $activesession->session_id));
        if($record) {
            $student->present = true;
        } else {
            $student->present = false;
        }
    } else {
        $student->session = false;
    }
}

$coursename = $DB->get_record_select('course', 'id=:cid', array('cid' => $courseid), 'fullname');

echo $OUTPUT->header();

foreach ($studentdata as $student) {
    $student->image_url = block_attendance_get_image_url($student->id);
}

$sessions = $DB->get_records('block_attendance_piu_window', array('course_id' => $courseid), 'session_id DESC');

$templatecontext = (object)[
    'course_name' => $coursename->fullname,
    'courseid' => $courseid,
    'courselist_url' => new moodle_url("/blocks/attendance_by_face/courselist.php?cid=" . $courseid),
    'attandancelist_url' => new moodle_url("/blocks/attendance_by_face/attendancelist.php?cid=" . $courseid),
    'studentlist' => array_values($studentdata),
    'redirecturl' => new moodle_url('/blocks/attendance_by_face/upload_image.php'),
    'actionurl' => $CFG->wwwroot . '/blocks/attendance_by_face/submitattendance.php',
    'load_data_url' => $CFG->wwwroot . '/blocks/attendance_by_face/load_data.php',
    'sessions' => array_values($sessions),
    'number_of_students' => count($studentdata),
];

$PAGE->requires->js_call_amd('block_attendance_by_face/dropdown_handler', 'init', array(
    $CFG->wwwroot . "/blocks/attendance_by_face/submitattendance.php" . "?cid=" . $courseid
));

echo $OUTPUT->render_from_template('block_attendance_by_face/studentlist', $templatecontext);

echo $OUTPUT->footer();

