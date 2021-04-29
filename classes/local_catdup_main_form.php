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
require_once("$CFG->libdir/formslib.php");

class local_catdup_main_form extends moodleform {
    public function definition() {
        global $DB, $CFG;

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

        // Find empty categories.
        $categories = $DB->get_records_sql('SELECT cat.id, cat.name, course.id AS courseid
                                            FROM {course_categories} cat
                                            LEFT JOIN {course} course
                                            ON cat.id = course.category
                                            WHERE course.id IS NULL');
        foreach ($categories as $category) {
            $destcatpath[$category->id] = $catpath[$category->id];
        }

        $select = $mform->addElement('select', 'destination', get_string('destination', 'local_catdup'), $destcatpath);

        $mform->addElement('text', 'extension', get_string('extension', 'local_catdup'));
        $mform->setType('extension', PARAM_RAW);
        $mform->setDefault('extension', '_' . date("Y") ); // Default value.

        $mform->addElement('text', 'oldextension', get_string('oldextension', 'local_catdup'));
        $mform->setType('oldextension', PARAM_RAW);
        $mform->setDefault('oldextension', '_' . (date("Y") - 1) ); // Default value.

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('pluginname', 'local_catdup'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
        $errors = array();
        if (empty($data['origin'])) {
            $errors['origin'] = get_string('selectorigin', 'local_catdup');
        }
        if (empty($data['destination'])) {
            $errors['destination'] = get_string('selectdestination', 'local_catdup');
        }
        return $errors;
    }
}