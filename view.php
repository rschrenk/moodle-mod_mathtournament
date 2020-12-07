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
 *
 * 1. Perform actions
 * 1.1 start a race (and join immediatley)
 * 1.2 join a race
 * 1.3 resign from a race
 * 2. If user is in a race, redirect to race page
 * 3. If user is not in a race, show list of races
 */


require('../../config.php');

// Should be autoloaded, but did not work...
require_once($CFG->dirroot . '/mod/mathtournament/classes/locallib.php');
require_once($CFG->dirroot . '/mod/mathtournament/classes/operation.php');
require_once($CFG->libdir . '/completionlib.php');

$id       = optional_param('id', 0, PARAM_INT);        // Course module ID
$t        = optional_param('t', 0, PARAM_INT);         // tournament instance id
$action   = optional_param('action', '', PARAM_ALPHANUM); // e.g. startrace
$join     = optional_param('join', 0, PARAM_INT);      // join a race.
$resign   = optional_param('resign', 0, PARAM_INT);      // resign from race.

if (!empty($t)) {  // Two ways to specify the module
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

\mod_mathtournament\locallib::set_lastseen();

$msgs = array(); // Array to store alert messages.

// SECTION 1: perform actions.

if (!empty($action)) {
    switch ($action) {
        case 'startrace':
            $race = (object) array(
                'opponents' => 2, // can be higher number in future.
                'tournamentid' => $mt->id,
                'timecreated' => time(),
                'timefinished' => 0,
            );
            // By setting $join to the new id, the user immediatley joins.
            $race->id = $DB->insert_record('mathtournament_races', $race);
            $score = \mod_mathtournament\locallib::join_race($mt, $race->id);
            $redirected = \mod_mathtournament\locallib::redirect_to_race($mt, $race, $score);
            if ($redirected) {
                $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('redirect_to_race', 'mathtournament')));
            }
        break;
    }
}

if (!empty($join)) {
    $race = $DB->get_record('mathtournament_races', array('id' => $join), '*', MUST_EXIST);
    if (!empty($race->id) && $race->tournamentid == $mt->id && $race->timefinished == 0) {
        $myscore = $DB->get_record('mathtournament_scores', array('raceid' => $join, 'userid' => $USER->id));
        if (empty($myscore->id)) {
            $score = \mod_mathtournament\locallib::join_race($mt, $race->id);
            if (!empty($score->id)) {
                $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('joined_race', 'mathtournament')));
            } else {
                $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'danger', 'content' => get_string('no_place_left_in_race', 'mathtournament')));
            }
        } else {
            $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'warning', 'content' => get_string('already_in_race', 'mathtournament')));
        }
    }
}

if (!empty($resign)) {
    $oldscore = \mod_mathtournament\locallib::resign_from_race($resign);
    if (!empty($oldscore)) {
        $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('resigned_from_race', 'mathtournament')));
    }
}

// 2. Check if we are in a race.
$score = $DB->get_record('mathtournament_scores', array('timefinished' => 0, 'userid' => $USER->id));
if (!empty($score->id)) {
    // We are currently in a race (may also be from another tournament...)!
    $race = $DB->get_record('mathtournament_races', array('id' => $score->raceid), '*', MUST_EXIST);
    $redirected = \mod_mathtournament\locallib::redirect_to_race($mt, $race, $score);
    if ($redirected) {
        $msgs[] = $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('redirect_to_race', 'mathtournament')));
    }
}

// 3. Show list of races.

echo $OUTPUT->header();
if (count($msgs) > 0) {
    echo implode("\n", $msgs);
}

$races = array_values($DB->get_records('mathtournament_races', array('tournamentid' => $mt->id, 'timefinished' => 0)));
$i = 1;
$tscompare = time()-5;
foreach ($races as &$race) {
    $race->raceid = $race->id;
    $race->nr = $i++;
    $sql = "SELECT mts.*,u.firstname,u.lastname
                FROM {mathtournament_scores} mts, {user} u
                WHERE mts.raceid=?
                    AND mts.userid=u.id";
    $race->scores = array_values($DB->get_records_sql($sql, array($race->id)));
    $race->btnjoin = 1;
    foreach ($race->scores as &$score) {
        $score->isonline = ($tscompare < $score->timelastseen) ? 1 : 0;
        $score->isoffline = ($score->isonline) ? 0 : 1;
        if ($score->userid == $USER->id) {
            $race->btnjoin = 0;
        }

    }
}
echo $OUTPUT->render_from_template('mod_mathtournament/races', array('races' => $races, 'baseurl' => $PAGE->url->__toString()));

// Make UI here to start and join races.
// Once all users joined and accepted they are ready, redirect to race.php.

echo $OUTPUT->footer();
