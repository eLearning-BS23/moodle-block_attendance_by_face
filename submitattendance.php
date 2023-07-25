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
 * Submits the attendance.
 *
 * @package    block_attendance_by_face
 * @copyright  2023, Brain Station 23 
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_once('lib.php');

$studentid = optional_param("id", 0, PARAM_INT);
$courseid = optional_param("cid", 0, PARAM_INT);
$sessionid = optional_param("session_id", 0, PARAM_INT);

if($studentid && $courseid && $sessionid) {
    // Check attendance at first.
    if(block_attendance_status($courseid, $studentid, $sessionid)) {
        redirect(new moodle_url('/blocks/attendance_by_face/manage.php?cid=' . $courseid), get_string('attendance_already_given', 'block_attendance_by_face'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        block_student_attendance_update($courseid, $studentid, $sessionid);
        redirect(new moodle_url('/blocks/attendance_by_face/manage.php?cid=' . $courseid), get_string('attendance_given', 'block_attendance_by_face'));
    }
} else {
    redirect(new moodle_url('/blocks/attendance_by_face/manage.php?cid=' . $courseid), get_string('attendance_error', 'block_attendance_by_face'), null, \core\output\notification::NOTIFY_ERROR);
}
