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

namespace mod_mathtournament;

defined('MOODLE_INTERNAL') || die;

class operation {
    const OPERATION_ADD = 0;
    const OPERATION_SUBSTRACT = 1;
    const OPERATION_MULTIPLY = 2;
    const OPERATION_DIVIDE = 3;

    // configure the points per operation here.
    const points = array(
        1, // OPERATION_ADD
        2, // OPERATION_SUBSTRACT
        3, // OPERATION_MULTIPLY
        4, // OPERATION_DIVIDE
    );

    public static function create($mt, $raceid, $operationtype, $userid = 0) {
        global $DB, $USER;
        // check validity of operationtype by determining its ponits.
        if (empty(self::points[$operationtype])) {
            return;
        }
        if ($userid == 0) {
            $userid = $USER->id;
        }

        $operation = (object) array(
            'operationtype' => $operationtype,
            'raceid' => $raceid,
            'timecreated' => time(),
            'timesolved' => 0,
            'tournamentid' => $mt->id,
            'userid' => $userid,
        );
        $operation->id = $DB->insert_record('mathtournament_operations', $operation);
        return $operation;
    }
    public static function redirect_to_race($mt, $race, $score = "") {
        global $DB, $USER;

        if (empty($score)) {
            $score = $DB->get_record('mathtournament_scores', array('raceid' => $race->id, 'timefinished' => 0, 'userid' => $USER->id));
        }

        if (empty($score->id) || $score->userid != $USER->id) {
            return false;
        }

        $url = new \moodle_url('/mod/mathtournament/race.php', array('id' => $race->id));
        redirect($url);

        return true;
    }
    public static function resign_from_race($raceid, $userid = 0) {
        global $DB, $USER;
        if ($userid == 0) {
            $userid = $USER->id;
        }

        $race = $DB->get_record('mathtournament_races', array('id' => $raceid), '*', MUST_EXIST);
        if ($race->timefinished == 0) {
            $myscore = $DB->get_record('mathtournament_scores', array('raceid' => $raceid, 'userid' => $userid));
            $DB->delete_records('mathtournament_scores', array('raceid' => $raceid, 'userid' => $userid));
            return $myscore->id;
        }
        return 0;
    }
    public static function set_lastseen() {
        global $DB, $USER;

        $sql = "UPDATE {mathtournament_scores}
                    SET timelastseen=?
                    WHERE userid=?
                        AND timefinished=0";
        $DB->execute($sql, array(time(), $USER->id));
    }
}
