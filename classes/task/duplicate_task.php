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
class duplicate_task extends \core\task\adhoc_task {

    public function execute() {
        global $DB;
        require_once( __DIR__ . '/../../locallib.php');
        require_once( __DIR__ . '/../../../../lib/classes/user.php');
        $noreplyuser = \core_user::get_noreply_user();
        $data = $this->get_custom_data();
        $courses = local_catdup_get_courses($data->destination);
        $categories = local_catdup_get_courses($data->destination);
        $USER = $DB->get_record('user', ['id' => $data->userid]);
        echo '*******************';
        print_r($data);
        print_r($courses);
        echo '*******************';
        if (count($courses) > 0 || count($categories) > 0) {
            email_to_user($USER, $noreplyuser, get_string('pluginname', 'local_catdup'),
                get_string('category') . ' ' . $data->destination . ' ' . get_string('notempty', 'local_catdup'), get_string('category') . ' ' . $data->destination . ' ' . get_string('notempty', 'local_catdup') );
            return;
        }
        try {
            local_catdup_duplicate($data->origin, $data->destination, $USER, $data->extension, $data->oldextension);
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
        email_to_user($USER, $noreplyuser, get_string('pluginname', 'local_catdup'),
            get_string('category') . ' ' . $data->origin . ' ' . get_string('copiedto', 'local_catdup') . ' ' . $data->destination,
            get_string('category') . ' ' . $data->origin . ' ' . get_string('copiedto', 'local_catdup') . ' ' . $data->destination);
    }
}
