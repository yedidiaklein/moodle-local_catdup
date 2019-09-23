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

define('CLI_SCRIPT', true);

require_once( __DIR__ . '/../../../../config.php');
require_once( __DIR__ . '/../../locallib.php');

if (count($argv) < 4) {
    die("Usage: php test.php sourceCategoryId destinationCategoryId UserId \n");
}

$USER = $DB->get_record('user', ['id' => $argv[3]]);

local_catdup_duplicate($argv[1], $argv[2], $USER, '_test', '_oldtest');

require_once( __DIR__ . '/../../../../lib/classes/user.php');
$noreplyuser = \core_user::get_noreply_user();

email_to_user($USER, $noreplyuser, get_string('pluginname', 'local_catdup'),
'Category ' . $argv[1] . ' copied to ' . $argv[2] , 'Category ' . $argv[1] . ' copied to ' . $argv[2]);

