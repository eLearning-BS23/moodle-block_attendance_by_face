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
 * List of visible courses
 *
 * @package    block_attendance_by_face
 * @copyright  2023, Brain Station 23 
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

$PAGE->set_url(new moodle_url('/blocks/attendance_by_face/courselist.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title(get_string('title_courselist', 'block_attendance_by_face'));

// !is_siteadmin()
if (!is_siteadmin() && !block_is_manager() && !block_is_coursecreator() && !block_is_teacher()) {
    redirect($CFG->wwwroot, get_string('no_permission', 'block_attendance_by_face'), null, \core\output\notification::NOTIFY_ERROR);
}

global $DB, $PAGE, $USER;

if(block_is_teacher()) {
    $courses = block_get_enrolled_courselist_as_teacher($USER->id);
}

if(is_siteadmin() || block_is_manager()) {
    $sql = "SELECT  c.id id,c.fullname fullname, lpw.active active, lpw.session_id FROM {course} c 
    left join {block_attendance_piu_window} lpw on c.id =lpw.course_id  and lpw.active=1
    where visible=1 and c.id<>1";

    $courses = $DB->get_records_sql($sql);
}

$courses = array_values($courses);

$templatecontext = (object)[
    'course_list' => $courses,
    'redirecturl' => new moodle_url('/blocks/attendance_by_face/manage.php'),
    'actionurl' => $CFG->wwwroot . '/blocks/attendance_by_face/togglesession.php',
];

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('block_attendance_by_face/courselist', $templatecontext);

echo $OUTPUT->footer();
