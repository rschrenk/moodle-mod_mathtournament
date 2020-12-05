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

$id       = required_param('id', PARAM_INT);

$race = $DB->get_record('mathtournament_races', array('id'=>$id), '*', MUST_EXIST);
$mt = $DB->get_record('mathtournament', array('id'=>$race->tournamentid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$mt->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/mathtournament:view', $context);

// Completion and trigger events.
mathtournament_view($mt, $course, $cm, $context);

$PAGE->set_title($mt->name);
$PAGE->set_heading($mt->name);
$PAGE->set_url('/mod/mathtournament/race.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');
$PAGE->requires->css('/mod/mathtournament/style/main.css');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_mathtournament/race', array());

echo $OUTPUT->footer();
