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
 * Version details.
 *
 * @package   local_catdup
 * @copyright 2019 OpenApp By Yedidia Klein http://openapp.co.il
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function local_catdup_get_courses($catid) {
    global $DB;
    $courses = $DB->get_records('course', [ 'category' => $catid ]);
    return $courses;
}

function local_catdup_get_categories($catid) {
    global $DB;
    $categories = $DB->get_records('course_categories', [ 'parent' => $catid ]);
    return $categories;
}

function local_catdup_duplicate($origin, $destination, $USER, $extension) {
    global $CFG, $DB;
    require_once( __DIR__ . '/../../course/externallib.php');
    require_once( __DIR__ . '/../..//lib/coursecatlib.php');
    // Find courses in origin cat and duplicate them to destination.
    // Get list of courses.
    $courses = local_catdup_get_courses($origin);
    $options = array(
        'activities' => 1,
        'blocks' => 1,
        'filters' => 1,
        'users' => 0,
        'role_assignments' => 0,
        'comments' => 0,
        'logs' => 0,
    );
    foreach ($courses as $course) {
        echo "[catdup] Copying " . $course->id . " To Category " . $destination . "\n";
        try {
            core_course_external::duplicate_course($course->id,
                                               $course->fullname,
                                               $course->shortname . $extension,
                                               $destination,
                                               $course->visible);

        } catch (Exception $e) {
            echo '[catdup] Caught exception on duplicate_course : ' . $course->id . $e->getMessage() . "\n";
            // Drop the file temp table on failure here.
            $DB->execute('DROP table {backup_files_temp}', []);
        }
    }
    // Get list of categories.
    $categories = local_catdup_get_categories($origin);
    foreach ($categories as $category) {
        // Create new category in destination.
        $data = new stdClass();
        $data->name = $category->name;
        $data->parent = $destination;
        echo "[catdup] Creating Category " . $category->name . " in category " . $destination . "\n";
        try {
            $newcat = coursecat::create($data);
        } catch (Exception $e) {
            echo '[catdup] Caught exception on create category : ' . $data->name . $e->getMessage() . "\n";
        }
        try {
            local_catdup_duplicate($category->id, $newcat->id, $USER, $extension);
        } catch (Exception $e) {
            echo '[catdup] Caught exception on catdup_duplicate: ' . $category->id . $newcat->id . $e->getMessage() . "\n";
        }
    }
}
