  
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
 * Settings student attendance plugin.
 *
 * @package    block_attendance_by_face
 * @copyright  2023, Brain Station 23 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('block_attendance_by_face/successmessage',
        get_string('successmessagetext', 'block_attendance_by_face'),
        get_string('successmessagelongtext', 'block_attendance_by_face'),
        ''));

    $settings->add(new admin_setting_configtext('block_attendance_by_face/failedmessage',
        get_string('failedmessagetext', 'block_attendance_by_face'),
        get_string('failedmessagelongtext', 'block_attendance_by_face'),
        ''));

    $settings->add(new admin_setting_configtext('block_attendance_by_face/bsapi',
        get_string('setting:bs_api', 'block_attendance_by_face'),
        get_string('setting:bs_apidesc', 'block_attendance_by_face'),
        ''));

    $settings->add(new admin_setting_configpasswordunmask('block_attendance_by_face/bs_api_key',
        get_string('setting:bs_api_key', 'block_attendance_by_face'),
        get_string('setting:bs_api_keydesc', 'block_attendance_by_face'), 
        ''));
    
    $settings->add(new admin_setting_configtext('block_attendance_by_face/threshold',
        get_string('threshold', 'block_attendance_by_face'),
        get_string('thresholdlongtext', 'block_attendance_by_face'),
        '0.7'));
}