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

echo $OUTPUT->header();

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
            for ($a = 0; $a < $race->opponents; $a++) {
                $score = (object) array(
                    'payload' => '{}',
                    'points' => 0,
                    'raceid' => $race->id,
                    'timejoined' => 0,
                    'timelastseen' => 0,
                    'tournamentid' => $mt->id,
                    'userid' => 0,
                );
                $DB->insert_record('mathtournament_scores', $score);
            }
        break;
    }
}

if (!empty($join)) {
    $score = $DB->get_record('mathtournament_scores', array('id' => $join));
    if (!empty($score->id) && $score->tournamentid == $mt->id) {
        // Check if we already have another score in that race.
        $chk = $DB->get_record('mathtournament_scores', array('raceid' => $score->raceid, 'userid' => $USER->id));
        if (!empty($chk->id)) {
            // We already are in this race!
            echo $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'warning', 'content' => get_string('already_in_race', 'mathtournament')));
        } else {
            if (empty($score->userid)) {
                // ok, we can join.
                $DB->set_field('mathtournament_scores', 'userid', $USER->id, array('id' => $score->id));
                echo $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('joined_race', 'mathtournament')));
            } else {
                // Check if we can use another score.
                $nextscore = $DB->get_record('mathtournament_scores', array('raceid' => $score->raceid, 'userid' => 0));
                if (!empty($nextscore->id)) {
                    // Use this score.
                    $DB->set_field('mathtournament_scores', 'userid', $USER->id, array('id' => $score->id));
                    echo $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('joined_race', 'mathtournament')));
                } else {
                    // No score left.
                    echo $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'danger', 'content' => get_string('no_place_left_in_race', 'mathtournament')));
                }
            }
        }
    }
}

if (!empty($resign)) {
    $score = $DB->get_record('mathtournament_scores', array('id' => $join));
    if (!empty($score->id) && $score->tournamentid == $mt->id && $score->userid == $USER->id && $score->timefinished == 0) {
        $score->points = 0; $score->userid = 0; $score->timejoined = 0;
        $DB->update_record('mathtournament_scores', $score);
        echo $OUTPUT->render_from_template('mathtournament/alert', array('type' => 'success', 'content' => get_string('resigned_from_race', 'mathtournament')));
    }
}

$races = array_values($DB->get_records('mathtournament_races', array('tournamentid' => $mt->id, 'timefinished' => 0)));
$i = 1;
$tscompare = time()-5;
foreach ($races as &$race) {
    $race->nr = $i++;
    $sql = "SELECT mts.*,u.firstname,u.lastname
                FROM {mathtournament_scores} mts, {user} u
                WHERE mts.raceid=?
                    AND mts.userid=u.id";
    $race->scores = array_values($DB->get_records_sql($sql, array($race->id)));
    for ($a = 0; $a < $race->opponents; $a++) {

        if (empty($race->scores[$a])) {
            $race->scores[$a] = array(
                'firstname' => '-',
                'lastname' => '-',
                'btnjoin' => 1,
                'points' => '',
                'userid' => 0,
            );
        } else {
            $race->scores[$a]->isonline = ($tscompare < $score->timelastseen) ? 1 : 0;
            $race->scores[$a]->isoffline = ($race->scores[$a]->isonline) ? 0 : 1;
            $race->scores[$a]->btnresign = 1;
        }
    }
}
echo $OUTPUT->render_from_template('mod_mathtournament/races', array('races' => $races, 'baseurl' => $PAGE->url->__toString()));

// Make UI here to start and join races.
// Once all users joined and accepted they are ready, redirect to race.php.

echo $OUTPUT->footer();
