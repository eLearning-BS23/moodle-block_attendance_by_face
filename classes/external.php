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
 * Externallib file for sevices functions
 *
 * @package    block_attendance_by_face
 * @copyright  2023, Brain Station 23
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class block_attendance_by_face_student_image extends external_api {

    public static function get_student_course_image_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, "Course id"),
                'studentid' => new external_value(PARAM_INT, "Student id")
            )
        );
    }

    public static function get_student_course_image($courseid, $studentid) {
        global $DB;
        $coursename = $DB->get_record_select('course', "id = :id", array('id' => $courseid));

        $context = context_system::instance();

        $fs = get_file_storage();
        if ($files = $fs->get_area_files($context->id, 'block_attendance_by_face', 'block_student_photo')) {
            foreach ($files as $file) {
                if ($studentid == $file->get_itemid() && $file->get_filename() != '.') {
                    // Build the File URL. Long process! But extremely accurate.
                    $fileurl = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                    $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename(), false);
                    // Display the image.
                    $downloadurl = $fileurl->get_port() ? $fileurl->get_scheme() . '://' . $fileurl->get_host() .
                    $fileurl->get_path() . ':' . $fileurl->get_port() : $fileurl->get_scheme() . '://'
                    . $fileurl->get_host() . $fileurl->get_path();

                    $returnvalue = [
                        'image_url' => $downloadurl,
                        'course_name' => $coursename->fullname
                    ];

                    return $returnvalue;
                }
            }
        }
        return [
            'image_url' => false,
            'course_name' => $coursename->fullname
        ];
    }

    public static function get_student_course_image_returns() {
        return new external_single_structure(
            array(
                'image_url' => new external_value(PARAM_URL, 'Url of student image'),
                'course_name' => new external_value(PARAM_TEXT, 'Course name')

            )
        );
    }

    /**
     * Update db for student attendance
     */
    public static function student_attendance_update_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, "Course id"),
                'studentid' => new external_value(PARAM_INT, "Student id"),
                'sessionid' => new external_value(PARAM_INT, "Session id"),
            )
        );
    }
    public static function student_attendance_update($courseid, $studentid, $sessionid) {
        global $DB;

        $record = $DB->get_record('block_attendance_fc_recog', array(
                        'course_id' => $courseid,
                        'student_id' => $studentid,
                        'session_id' => $sessionid
                    ));
        if (empty($record)) {
            $record = new stdClass();
            $record->student_id = $studentid;
            $record->course_id = $courseid;
            $record->session_id = $sessionid;
            $record->time = time();

            $DB->insert_record('block_attendance_fc_recog', $record);
        } else {
            $record->time = time();

            $DB->update_record('block_attendance_fc_recog', $record);
        }

        return ['status' => 'updated'];
    }
    public static function student_attendance_update_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'upadated or failed')
            )
        );
    }

    public static function check_active_window_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, "Course id"),
            )
        );
    }
    public static function check_active_window($courseid) {
        global $DB;
        $course = $DB->get_record('block_attendance_piu_window', array('course_id' => $courseid, 'active' => 1));

        return [
            'active' => $course->active,
            'sessionid' => $course->session_id,
        ];
    }
    public static function check_active_window_returns() {
        return new external_single_structure(
            array(
                'active' => new external_value(PARAM_INT, 'Return active 0 or 1'),
                'sessionid' => new external_value(PARAM_INT, 'Return session id')
            )
        );
    }


    /**
     * calling api using curl
     */
    public static function face_recognition_api_parameters() {
        return new external_function_parameters(
            array(
                 'studentimg' => new external_value(PARAM_RAW, "Student id"),
                 'webcampicture' => new external_value(PARAM_RAW, "Student id"),
            )
        );
    }

    public static function face_recognition_api($studentimg, $webcampicture) {
        if ($studentimg == "") {
            return [
                'status' => 435,
                'distance' => null
            ];
        }
        if ($webcampicture == "") {
            return [
                'status' => 436,
                'distance' => null
            ];
        }
        $studentimg = str_replace('data:image/png;base64,', '', $studentimg);
        $webcampicture = str_replace('data:image/png;base64,', '', $webcampicture);

        $data = array (
            'original_img_response' => $studentimg,
            'face_img_response' => $webcampicture,
        );

        $payload = json_encode($data);

        $bsapi = get_config('block_attendance_by_face', 'bsapi');
        $bsapikey = get_config('block_attendance_by_face', 'bs_api_key');

        // Check similarity.
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $bsapi,
            CURLOPT_HTTPHEADER => array(
                'x-api-key: ' . $bsapikey,
                "Content-Type: application/json",
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
        ]);

        $similarityresult = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        $response = json_decode($similarityresult);

        $output = array(
            'status' => $httpStatus,
            'distance' => $response->body->distance
        );

        return $output;
    }

    public static function face_recognition_api_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_INT, 'updated or failed', VALUE_OPTIONAL),
                'distance' => new external_value(PARAM_TEXT, 'distance value', VALUE_OPTIONAL)
            )
        );
    }
}
