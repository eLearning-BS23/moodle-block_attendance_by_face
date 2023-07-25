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

function block_participant_image_upload_get_image_url($studentid)
{
    $context = context_system::instance();
 
    $fs = get_file_storage();
    if ($files = $fs->get_area_files($context->id, 'local_participant_image_upload', 'student_photo')) {
 
        foreach ($files as $file) {
            if ($studentid == $file->get_itemid() && $file->get_filename() != '.') {
                // Build the File URL. Long process! But extremely accurate.
                $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), true);
                // Display the image
                $download_url = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://' . $fileurl->get_host() . $fileurl->get_path();
                return $download_url;
            }
        }
    }
    return false;
}

/**
 * Checks if the current user is a manager.
 */
function ismanager() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

/**
 * Checks if the current user is a coursecreator.
 */
function iscoursecreator() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

/**
 * Checks if the current user is an editing teacher in any of the courses.
 */
function isteacher() {
    global $DB, $USER;
    $roleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
    return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]); 
}

/**
 * Returns the attendance list for a specfic course for a specific time range.
 */
function student_attandancelist($courseid, $from, $to, $sort) {
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
    foreach($sessionlist1 as $session) {
        array_push($distintsessions, $session->session_id);
    }
    foreach($sessionlist2 as $session) {
        array_push($distintsessions, $session->session_id);
    }

    $string = implode(", ", $distintsessions);
    
    if(empty($string)) {
        $studentdata = array();
        return $studentdata;
    }
 
    $sql = "SELECT {user}.id, {user}.username, {block_attendance_piu_window}.session_id, {block_attendance_piu_window}.session_name, {course}.id course_id, {block_attendance_fc_recog}.time, {user}.firstname, {user}.lastname, {user}.email
        FROM {role_assignments}
        JOIN {user} on {role_assignments}.userid = {user}.id
        JOIN {role} on {role_assignments}.roleid = {role}.id
        JOIN {context} on {role_assignments}.contextid = {context}.id
        JOIN {course} on {context}.instanceid = {course}.id
        LEFT JOIN {block_attendance_piu_window} on {course}.id = {block_attendance_piu_window}.course_id
        LEFT JOIN {block_attendance_fc_recog} on {course}.id = {block_attendance_fc_recog}.course_id AND {user}.id = {block_attendance_fc_recog}.student_id AND {block_attendance_piu_window}.session_id = {block_attendance_fc_recog}.session_id
        WHERE {role}.shortname = 'student' AND {course}.id=$courseid AND {block_attendance_piu_window}.session_id in 
        (" . $string . ") 
        GROUP BY {user}.id, {block_attendance_piu_window}.session_id
        ORDER BY {block_attendance_piu_window}.session_id " . $sort;

    $studentdata = $DB->get_recordset_sql($sql);
    return $studentdata;
}