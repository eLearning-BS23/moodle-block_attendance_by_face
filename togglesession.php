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
 * Toogles session value
 *
 * @package   block_attendance_by_face
 * @copyright 2023, Brain Station 23
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_once('lib.php');

require_login();

$courseid = optional_param("cid", 0, PARAM_INT);
$sessionid = optional_param("session", 0, PARAM_INT);
$active = optional_param("active", 0, PARAM_INT);

if ($active) {
    block_toggle_window($courseid, $USER->id, $sessionid, 1);
    redirect(new moodle_url('/blocks/attendance_by_face/courselist.php'), get_string('start_text', 'block_attendance_by_face'));
} else {
    block_toggle_window($courseid, $USER->id, $sessionid, 0);
    redirect(new moodle_url('/blocks/attendance_by_face/courselist.php'), get_string('stop_text', 'block_attendance_by_face'));
}
