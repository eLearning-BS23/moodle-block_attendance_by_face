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