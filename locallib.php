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

function local_catdup_duplicate($origin, $destination) {
    require_once('../../course/externallib.php');
    // Find courses in origin cat and duplicate them to destination.
    // Get list of courses.
    $courses = local_catdup_get_courses($origin);
    foreach ($courses as $course) {
        core_course_external::duplicate_course($course->id, $course->fullname, $course->shortname, $destination, $course->visible);
    }
    // Get list of categories.
    $categories = local_catdup_get_categories($origin);
    foreach ($categories as $category) {
        // Create new category in destination
        // get info of destination.
        $destcat = $DB->get_records('course_categories', ['id' => $destination]);
        $record = new stdClass;
        $record->name = $category->name;
        $record->parent = $destination;
        $record->depth = $destcat->depth + 1;
        $record->timemodified = time();
        $newcatid = $DB->insert_record('course_categories', $record);
        // Take care of path of new cat.
        $record->id = $newcatid;
        $record->path = $destcat->path . '/' . $newcatid;
        $DB->update_record('course_catyegories', $record);
        local_catdup_duplicate($$category->id, $newcatid);
    }
}

