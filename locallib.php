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

function local_catdup_duplicate($origin, $destination, $USER, $extension, $oldextension) {
    global $CFG, $DB;
    require_once( __DIR__ . '/../../course/externallib.php');
    //for higher version
    //require_once( __DIR__ . '/../../lib/coursecatlib.php');
    require_once( __DIR__ . '/../../course/classes/category.php');
    // Find courses in origin cat and duplicate them to destination.
    // Get list of courses.
    $courses = local_catdup_get_courses($origin);
    foreach ($courses as $course) {
        echo "[catdup] Copying " . $course->id . " To Category " . $destination . "\n";
        try {
            local_catdup_duplicate_course($course->id,
                                            $course->fullname,
                                            $course->shortname . $extension,
                                            $destination,
                                            $oldextension,
                                            $extension,
                                            $course->visible);

        } catch (Exception $e) {
            echo '[catdup] Caught exception on duplicate_course : ' . $course->id . " " . $e->getMessage() . "\n";
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
            //for higher version
            //$newcat = coursecat::create($data);
            $newcat = core_course_category::create($data);
        } catch (Exception $e) {
            echo '[catdup] Caught exception on create category : ' . $data->name . $e->getMessage() . "\n";
        }
        try {
            local_catdup_duplicate($category->id, $newcat->id, $USER, $extension, $oldextension);
        } catch (Exception $e) {
            echo '[catdup] Caught exception on catdup_duplicate: ' . $category->id . $newcat->id . $e->getMessage() . "\n";
        }
    }
}

function local_catdup_duplicate_course($courseid, $fullname, $shortname, $categoryid, $oldextension, $extension, $visible = 1) {
    global $CFG, $DB;
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once($CFG->dirroot . '/backup/controller/backup_controller.class.php');
    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    require_once($CFG->dirroot . '/course/lib.php');


    $sourcecourse = $courseid;

    $admin = get_admin();

    $options = array(
        'users' => 0,
        'role_assignments' => 0,
    );

    // Backup.
    $bc = new \backup_controller(\backup::TYPE_1COURSE, $sourcecourse,
        \backup::FORMAT_MOODLE, \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $admin->id);

    foreach ($options as $name => $value) {
        if ($setting = $bc->get_plan()->get_setting($name)) {
            $bc->get_plan()->get_setting($name)->set_value($value);
        }
    }

    $outcome = $bc->execute_plan();
    $results = $bc->get_results();
    $file = $results['backup_destination'];

    $backupdir = basename($bc->get_plan()->get_basepath());
    $bc->destroy();
    unset($bc);

    // Restore.
    if (!file_exists($CFG->dataroot . '/temp/backup/' . $backupdir . "/moodle_backup.xml")) {
        $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $CFG->dataroot.'/temp/backup/'.$backupdir);
    }

    $data = new \stdClass();
    $data->category = $categoryid;
    $data->shortname = $shortname;
    $data->fullname = $fullname;
    $data->visible = $visible;

    // Create empty new course.
    $newcourse = create_course($data);
    $destcourse = $newcourse->id;

    if (file_exists($CFG->dataroot . '/temp/backup/' . $backupdir . '/course/course.xml')) {
        $controller = new \restore_controller($backupdir,
                                        $destcourse,
                                        \backup::INTERACTIVE_NO,
                                        \backup::MODE_SAMESITE,
                                        $admin->id,
                                        \backup::TARGET_NEW_COURSE);

        foreach ($options as $name => $value) {
            $setting = $controller->get_plan()->get_setting($name);
            if ($setting->get_status() == backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        if (!$controller->execute_precheck()) {
            if ($controller->get_status() !== \backup::STATUS_AWAITING) {
                die;
            }
        }

        $controller->execute_plan();

        // Rename fullname and shortname.
        $torename = $DB->get_record('course', ['id' => $destcourse]);
        $newfullname = preg_replace("/(\w+) copy (\d+)/", '$1', $torename->fullname);
        if ($oldextension != '') {
            $newfullname = str_replace($oldextension, $extension, $newfullname);
        } else {
            $newfullname = $newfullname . $extension;
        }
        $record = new \stdClass();
        $record->id = $destcourse;
        $record->fullname = $newfullname;
        $renamed = $DB->update_record('course', $record);
        rebuild_course_cache($destcourse);
        $file->delete();
    }
}