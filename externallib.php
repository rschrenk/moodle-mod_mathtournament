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

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

class mod_mathtournament_external extends external_api {
    public static function ping_parameters() {
        return new external_function_parameters(array(
            'raceid' => new external_value(PARAM_INT, 'id of race'),
            'answer' => new external_value(PARAM_INT, 'answer or -9999'),
        ));
    }

    /**
     * Request current status of all opponents in race.
     * @return list of all opponents statusses as json.
     */
    public static function ping($raceid, $answer) {
        global $CFG, $DB, $USER;
        $params = self::validate_parameters(self::ping_parameters(), array('raceid' => $raceid, 'answer' => $answer));

        $reply = array();
        if ($params['answer'] != -9999) {
            // The user answered the last quest. Check if it was correct.
        }
        // List all status of all opponents.
        $reply['opponents'] = $DB->get_records('mathtournament_scores', array('raceid' => $params['raceid']));

        return json_encode($reply, JSON_NUMERIC_CHECK);
    }
    /**
     * Return definition.
     * @return external_value
     */
    public static function ping_returns() {
        return new external_value(PARAM_RAW, 'Race data as JSON-string');
    }
}
