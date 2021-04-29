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
require_once( __DIR__ . '/classes/local_catdup_main_form.php');
require_once( __DIR__ . '/classes/task/duplicate_task.php');
require_login();

defined('MOODLE_INTERNAL') || die();

if (!is_siteadmin()) {
    redirect($CFG->wwwroot, get_string('accessdenied', 'local_catdup'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_catdup'));
$PAGE->set_title(get_string('pluginname', 'local_catdup'));
$PAGE->set_url('/local/catdup');
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
$PAGE->navbar->add(get_string('courses'), new moodle_url('/admin/search.php#linkcourses'));
$PAGE->navbar->add(get_string('pluginname', 'local_catdup'), new moodle_url('/local/catdup'));

$mform = new local_catdup_main_form();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot);
} else if ($fromform = $mform->get_data()) {
    require_once( __DIR__ . '/locallib.php');

    $duptask = new \local_catdup\task\duplicate_task();
    if ($duptask) {
        $duptask->set_custom_data(array(
            'origin' => $fromform->origin,
            'destination' => $fromform->destination,
            'extension' => $fromform->extension,
            'oldextension' => $fromform->oldextension,
            'userid' => $USER->id
        ));
        \core\task\manager::queue_adhoc_task($duptask);
        $answer = get_string('taskinserted', 'local_catdup');
    }

    redirect($CFG->wwwroot . '/local/catdup/', $answer);
} else {
    echo $OUTPUT->header();

    $mform->display();

}

echo $OUTPUT->footer();
