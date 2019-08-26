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

        $mform->addElement('text', 'origin', get_string('origin', 'local_catdup'));
        $mform->setType('origin', PARAM_INT);
        $mform->setDefault('origin', '' ); // Default value.

        $mform->addElement('text', 'destination', get_string('destination', 'local_catdup'));
        $mform->setType('destination', PARAM_INT);
        $mform->setDefault('destination', '' ); // Default value.

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
    require_once('locallib.php');

    $record = new stdClass;
    $record->origin = $fromform->origin;
    $record->destination = $fromform->destination;
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