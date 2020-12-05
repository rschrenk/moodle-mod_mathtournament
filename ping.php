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

header('Content-Type: application/json');

require('../../config.php');

$reply = array();
$id       = optional_param('id', 0, PARAM_INT);
$answer   = optional_param('answer', -9999, PARAM_INT);

if (!empty($reply)) {
    $race = $DB->get_record('mathtournament_races', array('id'=>$id), '*', IGNORE_MISSING);
    $mt = $DB->get_record('mathtournament', array('id'=>$race->tournamentid), '*', IGNORE_MISSING);
    $course = $DB->get_record('course', array('id'=>$mt->course), '*', IGNORE_MISSING);

    if (!empty($race->id) && !empty($mt->id) && !empty($course->id)) {
        $selfscore = $DB->get_record('mathtournament_scores', array('raceid' => $race->id, 'userid' => $USER->id));
        if (!empty($selfscore->id)) {
            if ($answer != -9999) {
                // User gave an answer, check if it is correct.
            }

            // Load all status of all participants of this race.
            $reply['opponents'] = $DB->get_records('mathtournament_scores', array('raceid' => $params['raceid']));

        } else {
            $reply['error'] = 'not_part_of_race';
        }
    } else {
        $reply['error'] = 'missing_race';
    }
} else {
    $reply['error'] = 'missing_raceid';
}

return json_encode($reply, JSON_NUMERIC_CHECK);
