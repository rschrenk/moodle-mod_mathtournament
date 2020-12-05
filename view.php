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
 * @package    mod_mathtournament
 * @copyright  2020 Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$t        = optional_param('t', 0, PARAM_INT);         // tournament instance id

if ($u) {  // Two ways to specify the module
    $mt = $DB->get_record('mathtournament', array('id'=>$t), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mathtournament', $mt->id, $mt->course, false, MUST_EXIST);

} else {
    $cm = get_coursemodule_from_id('mathtournament', $id, 0, false, MUST_EXIST);
    $mt = $DB->get_record('mathtournament', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/mathtournament:view', $context);

$PAGE->set_title($mt->name);
$PAGE->set_heading($mt->name);
$PAGE->set_url('/mod/mathtournament/view.php', array('id' => $cm->id));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Make UI here to start and join races.
// Once all users joined and accepted they are ready, redirect to race.php.

echo $OUTPUT->footer();
