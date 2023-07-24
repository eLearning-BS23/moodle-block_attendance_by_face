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
 * Block definition class for the block_attendance_by_face plugin.
 *
 * @package   block_attendance_by_face
 * @copyright 2023, Brain Station 23 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_attendance_by_face extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_attendance_by_face');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        if(!$this->can_view()) {
            $this->content = get_string('no_permission', 'block_attendance_by_face');
            return $this->content;
        }

        global $USER;

        if(is_siteadmin()) {
            $courses = $this->get_all_visible_courses();
        } else {
            $courses = $this->get_enrolled_courselist_as_teacher($USER->id);
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $templatecontext = [
            'courses' => array_values($courses),
            'url' => new moodle_url('/blocks/attendance_by_face/manage.php')
        ];

        $this->content->text = $OUTPUT->render_from_template('block_attendance_by_face/pluginbody', $templatecontext);

        return $this->content;
    }

    /**
     * Checks that the user can view this block or not.
     * 
     * @return boolean value true if can, false otherwise
     */
    public function can_view() {
        global $DB, $USER;
        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        $coursecreatorroleid = $DB->get_field('role', 'id', ['shortname' => 'coursecreator']);
        $managerroleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);

        $teachercap = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $teacherroleid]);
        $coursecreatorcap =  $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $coursecreatorroleid]);
        $managercap = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $managerroleid]);

        if(is_siteadmin() || $teachercap || $managercap || $coursecreatorcap) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets all courses that user can view
     */
    public function get_all_visible_courses() {
        global $DB;
        $sql = "SELECT  c.id, c.fullname 
                FROM {course} c
                WHERE visible=1 AND c.id<>1";
        $courses = $DB->get_records_sql($sql);
        return $courses;
    }

    public function get_enrolled_courselist_as_teacher($userid) {
        global $DB;
        $sql = "SELECT c.fullname 'fullname', c.id
                FROM {role_assignments} r
                JOIN {user} u on r.userid = u.id
                JOIN {role} rn on r.roleid = rn.id
                JOIN {context} ctx on r.contextid = ctx.id
                JOIN {course} c on ctx.instanceid = c.id
                WHERE rn.shortname = 'editingteacher' and u.id=" . $userid;
        $courselist = $DB->get_records_sql($sql);
        return $courselist;
    }

    /**
     * 
     * Allow the block to have multiple instance
     * 
     * @return bool
     */
    function instance_allow_multiple() {
        return false;
    }

    /**
     * Defines in which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => true,
            'course-view' => true,
            'mod' => false,
            'my' => true,
        ];
    }
}