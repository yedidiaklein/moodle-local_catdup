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

require_once( __DIR__ . '/../../config.php');
require_login();

defined('MOODLE_INTERNAL') || die();

if (!is_siteadmin()) {
    echo "Access Denied";
}

require_once("$CFG->libdir/formslib.php");

$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_catdup'));
$PAGE->set_title(get_string('pluginname', 'local_catdup'));
$PAGE->set_url('/local/catdup');
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('pluginname', 'local_catdup'), new moodle_url('/local/catdup'));

class cat_form extends moodleform {
    public function definition() {
        global $DB;

        $mform = $this->_form;

        $categories = $DB->get_records('course_categories', []);
        foreach ($categories as $category) {
            $cats[$category->id] = $category->name;
        }

        foreach ($categories as $category) {
            $path = explode('/', $category->path);
            $catpath[$category->id] = '';
            foreach ($path as $leaf) {
                if ($leaf != '') {
                    $catpath[$category->id] .= $cats[$leaf] . '/';
                }
            }
        }

        $select = $mform->addElement('select', 'origin', get_string('origin', 'local_catdup'), $catpath);

        $categories = $DB->get_records_sql('SELECT cat.id, cat.name, course.id AS courseid
                                            FROM {course_categories} cat
                                            LEFT JOIN {course} course
                                            ON cat.id = course.category
                                            WHERE course.id is ?', [null]);
        foreach ($categories as $category) {
            $destcatpath[$category->id] = $catpath[$category->id];
        }

        $select = $mform->addElement('select', 'destination', get_string('destination', 'local_catdup'), $destcatpath);

        $mform->addElement('text', 'extension', get_string('extension', 'local_catdup'));
        $mform->setType('extension', PARAM_RAW);
        $mform->setDefault('extension', '_' . date("Y") ); // Default value.

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('pluginname', 'local_catdup'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
        return array();
    }

}

$mform = new cat_form();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot);
} else if ($fromform = $mform->get_data()) {
    require_once( __DIR__ . '/locallib.php');

    $record = new stdClass;
    $record->origin = $fromform->origin;
    $record->destination = $fromform->destination;
    $record->extension = $fromform->extension;
    $record->userid = $USER->id;
    $record->state = 1;
    $record->timecreated = time();

    $id = $DB->insert_record('local_catdup_tasks', $record);
    if ($id) {
        $answer = get_string('taskinserted', 'local_catdup') . '(' . $id . ')';
    }

    redirect($CFG->wwwroot . '/local/catdup/', $answer);
} else {
    echo $OUTPUT->header();

    $mform->display();

}

echo $OUTPUT->footer();