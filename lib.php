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

/**
 * Serve the files.
 *
 * @param stdClass $course the course object.
 * @param stdClass $cm the course module object.
 * @param context $context the context.
 * @param string $filearea the name of the file area.
 * @param array $args extra arguments (itemid, path, filename).
 * @param bool $forcedownload whether or not force download.
 * @param array $options additional options affecting the file serving.
 * @return bool false if the file not found, just send the file otherwise and do not return anything.
 */
function block_attendance_by_face_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea != 'block_student_photo') {
        return false;
    }

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();

    $filename = array_pop($args);
    if (empty($args)) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $file = $fs->get_file($context->id, 'block_attendance_by_face', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, false, $options);
}


function block_attendance_get_image_url($studentid) {
    $context = context_system::instance();

    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'block_attendance_by_face', 'block_student_photo')) {

        foreach ($files as $file) {
            if ($studentid == $file->get_itemid() && $file->get_filename() != '.') {
                // Build the File URL. Long process! But extremely accurate.
                $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
                // Display the image.
                $downloadurl = $fileurl->get_port() ? $fileurl->get_scheme() . '://' .
                $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() .
                '://' . $fileurl->get_host() . $fileurl->get_path();
                return $downloadurl;
            }
        }
    }
    return false;
}

/**
 * Checks if the current user is a manager.
 */
function block_is_manager() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
}

/**
 * Checks if the current user is a coursecreator.
 */
function block_is_coursecreator() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
}

/**
 * Checks if the current user is an editing teacher in any of the courses.
 */
function block_is_teacher() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
}

/**
 * Returns the attendance list for a specfic course for a specific time range.
 */
function block_student_attandancelist($courseid, $from, $to, $sort) {
    global $DB;

    $sql = "SELECT DISTINCT session_id
    FROM {block_attendance_fc_recog}
    WHERE ({block_attendance_fc_recog}.time > " . $from . " AND {block_attendance_fc_recog}.time < " . $to .")";
    $sessionlist1 = $DB->get_records_sql($sql);

    $sql = "SELECT session_id
    FROM {block_attendance_piu_window}
    WHERE {block_attendance_piu_window}.session_id > " . $from . " AND {block_attendance_piu_window}.session_id < " . $to . "
        AND {block_attendance_piu_window}.session_id NOT IN (SELECT session_id FROM {block_attendance_fc_recog})";
    $sessionlist2 = $DB->get_records_sql($sql);

    $distintsessions = array();
    foreach ($sessionlist1 as $session) {
        array_push($distintsessions, $session->session_id);
    }
    foreach ($sessionlist2 as $session) {
        array_push($distintsessions, $session->session_id);
    }

    $string = implode(", ", $distintsessions);

    if (empty($string)) {
        $studentdata = array();
        return $studentdata;
    }

    $sql = "SELECT {user}.id, {user}.username, {block_attendance_piu_window}.session_id, {block_attendance_piu_window}.session_name,
    {course}.id course_id, {block_attendance_fc_recog}.time, {user}.firstname, {user}.lastname, {user}.email
        FROM {role_assignments}
        JOIN {user} on {role_assignments}.userid = {user}.id
        JOIN {role} on {role_assignments}.roleid = {role}.id
        JOIN {context} on {role_assignments}.contextid = {context}.id
        JOIN {course} on {context}.instanceid = {course}.id
        LEFT JOIN {block_attendance_piu_window} on {course}.id = {block_attendance_piu_window}.course_id
        LEFT JOIN {block_attendance_fc_recog} on {course}.id = {block_attendance_fc_recog}.course_id
        AND {user}.id = {block_attendance_fc_recog}.student_id AND
        {block_attendance_piu_window}.session_id = {block_attendance_fc_recog}.session_id
        WHERE {role}.shortname = 'student' AND {course}.id=$courseid AND {block_attendance_piu_window}.session_id in
        (" . $string . ")
        GROUP BY {user}.id, {block_attendance_piu_window}.session_id
        ORDER BY {block_attendance_piu_window}.session_id " . $sort;

    $studentdata = $DB->get_recordset_sql($sql);
    return $studentdata;
}

/**
 * Returns the courselist of a user where the user is enrolled as a teacher.
 */
function block_get_enrolled_courselist_as_teacher($userid) {
    global $DB;
    $sql = "SELECT lpw.id, c.fullname 'fullname', c.id, lpw.session_id, lpw.active active
                FROM {role_assignments} r
                JOIN {user} u on r.userid = u.id
                JOIN {role} rn on r.roleid = rn.id
                JOIN {context} ctx on r.contextid = ctx.id
                JOIN {course} c on ctx.instanceid = c.id
                LEFT JOIN {block_attendance_piu_window} lpw on c.id = lpw.course_id  and lpw.active=1
                WHERE rn.shortname = 'editingteacher' and u.id=" . $userid;
    $courselist = $DB->get_records_sql($sql);
    return $courselist;
}

/**
 * Create a new active session or stops a active session.
 */
function block_toggle_window($courseid, $changedby, $sessionid, $active) {
    global $DB;
    if ($active) {
        $record = new stdClass();
        $record->course_id = $courseid;
        $record->active = $active;
        $record->session_id = time();
        $record->session_name = block_get_session_name($courseid);
        $record->changedby = $changedby;

        $DB->insert_record('block_attendance_piu_window', $record);

        return $record->session_id;
    } else {
        $record = $DB->get_record('block_attendance_piu_window', array('course_id' => $courseid, 'session_id' => $sessionid));

        $record->active = $active;
        $record->changedby = $changedby;

        $DB->update_record('block_attendance_piu_window', $record);
    }
}

/**
 * Prepares and returns a session name for a course according to the convention.
 *
 * Session name: C{courseid}-y/m/d-{nth_session_of_today} (eg. C100-2022/08/01-01, C100-2022/08/01-02)
 */
function block_get_session_name($courseid) {
    global $DB;
    // Get the total number of sessions of the specific course for today.

    // Setting default timezone.
    date_default_timezone_set('Asia/Dhaka');
    $t1 = mktime(0, 0, 0);
    $t2 = mktime(23, 59, 59);

    $sql = "SELECT * FROM {block_attendance_piu_window}
            WHERE {block_attendance_piu_window}.session_id > $t1 AND {block_attendance_piu_window}.session_id < $t2";

    $records = $DB->get_records_sql($sql);
    $count = count($records) + 1;

    // Prepare session name.
    $sessionname = "C" . $courseid . "-" . date('Y/m/d', strtotime('now')) . "-" . $count;
    return $sessionname;
}

/**
 * Checks if the users is present or not in a specific session of a course.
 */
function block_attendance_status($courseid, $studentid, $sessionid) {
    global $DB;

    return $DB->record_exists('block_attendance_fc_recog', array(
                    'course_id' => $courseid,
                    'student_id' => $studentid,
                    'session_id' => $sessionid
                ));
}

/**
 * Submits attendance.
 */
function block_student_attendance_update($courseid, $studentid, $sessionid) {
    global $DB;

    $record = new stdClass();
    $record->student_id = $studentid;
    $record->course_id = $courseid;
    $record->session_id = $sessionid;
    $record->time = time();

    $DB->insert_record('block_attendance_fc_recog', $record);
}
