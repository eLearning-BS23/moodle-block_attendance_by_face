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

        if ($this->block_is_student()) {
            global $USER, $DB, $CFG;
            $courses = $this->get_enrolled_courselist_with_active_window($USER->id);

            $attendancedonetxt = get_string('attendance_done', 'block_attendance_by_face');
            $attendancebuttontxt = get_string('attendance_button', 'block_attendance_by_face');
            $attendancebuttontitle = get_string('attendance_button_title', 'block_attendance_by_face');

            $this->content = new stdClass;
            $this->content->text = '<hr>';

            foreach ($courses as $course) {
                $done = $DB->get_record("block_attendance_fc_recog",
                array('student_id' => $USER->id, 'course_id' => $course->cid, 'session_id' => $course->session_id));
                if (!$done) {
                    $this->content->text .= "
                    <div class='d-flex justify-content-between mb-3'>
                        <div class='d-flex align-items-center'>" . $course->fullname . "</div>
                        <div>
                            <button
                                type='button'
                                id='" . $course->cid . "'
                                class='action-modal btn btn-primary'
                                title='". $attendancebuttontitle . "'>
                                ". $attendancebuttontxt ."
                            </button>
                        </div>
                    </div>
                    <hr>
                    ";
                }
            }
            $successmessage = get_config('block_attendance_by_face', 'successmessage');

            if (empty($successmessage)) {
                $successmessage = get_string('successmessagetextdefault', 'block_attendance_by_face');
            }

            $failedmessage = get_config('block_attendance_by_face', 'failedmessage');

            if (empty($failedmessage)) {
                $failedmessage = get_string('failedmessagetextdefault', 'block_attendance_by_face');
            }

            $threshold = get_config('block_attendance_by_face', 'threshold');

            if (empty($threshold)) {
                $threshold = 0.68;
            }

            $modelurl = $CFG->wwwroot . '/blocks/attendance_by_face/thirdpartylibs/models';
            $this->page->requires->js("/blocks/attendance_by_face/amd/build/face-api.min.js", true);
            $this->page->requires->js_call_amd('block_attendance_by_face/attendance_modal',
            'init', array($USER->id, $successmessage, $failedmessage, $threshold, $modelurl));
        } else {
            if (!$this->can_view()) {
                $this->content = get_string('no_permission', 'block_attendance_by_face');
                return $this->content;
            }

            global $USER;

            if (is_siteadmin()) {
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
        }

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
        $coursecreatorcap = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $coursecreatorroleid]);
        $managercap = $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $managerroleid]);

        if (is_siteadmin() || $teachercap || $managercap || $coursecreatorcap) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if the current user is an editing teacher in any of the courses.
     */
    public function block_is_student() {
        global $DB, $USER;
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        return $DB->record_exists('role_assignments', ['userid' => $USER->id, 'roleid' => $roleid]);
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

    public function get_enrolled_courselist_with_active_window($userid) {
        global $DB;
        $sql = "SELECT c.fullname 'fullname', c.id 'cid', lpiu.session_id
                FROM {role_assignments} r
                JOIN {user} u on r.userid = u.id
                JOIN {role} rn on r.roleid = rn.id
                JOIN {context} ctx on r.contextid = ctx.id
                JOIN {course} c on ctx.instanceid = c.id
                JOIN {block_attendance_piu_window} lpiu on c.id = lpiu.course_id
                WHERE rn.shortname = 'student'  and lpiu.active = 1 and u.id=" . $userid;
        $courselist = $DB->get_records_sql($sql);
        return $courselist;
    }

    public function has_config() {
        return true;
    }

    /**
     *
     * Allow the block to have multiple instance
     *
     * @return bool
     */
    public function instance_allow_multiple() {
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
