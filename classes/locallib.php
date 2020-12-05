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

class locallib {
    public static function set_lastseen() {
        global $DB, $USER;

        $sql = "UPDATE {mathtournament_scores}
                    SET timelastseen=?
                    WHERE userid=?
                        AND timefinished=0";
        $DB->execute($sql, array(time(), $USER->id));
    }
}
