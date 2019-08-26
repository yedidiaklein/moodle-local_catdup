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

namespace local_catdup\task;
defined('MOODLE_INTERNAL') || die();
/**
 * Class for duplicating categories task.
 * @copyright  2019 Yedidia Klein OpenApp Israel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class duplicate_task extends \core\task\scheduled_task {
    public function get_name() {
        // Shown in admin screens.
        return get_string('pluginname', 'local_catdup');
    }

    public function execute() {
        global $DB;
        require_once( __DIR__ . '/../../locallib.php');
        // Get list of tasks.
        $todo = $DB->get_records('local_catdup_tasks', ['state' => 1]);
        foreach ($todo as $do) {
            $record = new \stdClass();
            $record->id = $do->id;
            $record->state = 2;
            $record->timemodified = time();
            $DB->update_record('local_catdup_tasks', $record);
            // First of all check that destination category is empty.
            $courses = local_catdup_get_courses($do->destination);
            $categories = local_catdup_get_courses($do->destination);
            $USER = $DB->get_record('user', ['id' => $do->userid]);
            if (count($courses) > 0 || count($categories) > 0) {
                email_to_user($USER, $USER, get_string('pluginname', 'local_catdup'),
                    'Category ' . $do->destination . 'is not empty..', 'Category ' . $do->destination . ' is not empty..');
                $record = new \stdClass();
                $record->id = $do->id;
                $record->state = 3;
                $record->timemodified = time();
                $DB->update_record('local_catdup_tasks', $record);

                continue;
            }
            $USER = $DB->get_record('user', ['id' => $do->userid]);
            local_catdup_duplicate($do->origin, $do->destination, $USER, $do->extension);
            $record = new \stdClass();
            $record->id = $do->id;
            $record->state = 3;
            $record->timemodified = time();
            $DB->update_record('local_catdup_tasks', $record);
            email_to_user($USER, $USER, get_string('pluginname', 'local_catdup'),
                    'Category ' . $do->origin . ' copied to ' . $do->destination,
                    'Category ' . $do->origin . ' copied to ' . $do->destination);
        }
    }
}
