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
 * Web service searching the users of the enrolment page pickers.
 *
 * @package   local_multiple_enrollments
 * @copyright 2025 E-learning Touch' <contact@elearningtouch.com> (Maintainer)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_multiple_enrollments\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use context_system;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_multiple_enrollments\local\user_search;

/**
 * Search the users that can be enrolled, with a bounded result set.
 */
class search_users extends external_api {
    /**
     * Define function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'query' => new external_value(PARAM_NOTAGS, 'Text to search in user names, usernames and ID numbers'),
        ]);
    }

    /**
     * Search users.
     *
     * @param string $query
     * @return array
     */
    public static function execute($query) {
        $params = self::validate_parameters(self::execute_parameters(), ['query' => $query]);

        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/multiple_enrollments:manage', $context);

        // One extra record tells whether the search has more matches than can be shown.
        $users = user_search::search($params['query'], user_search::MAX_RESULTS + 1);
        $overflow = count($users) > user_search::MAX_RESULTS;

        $results = [];
        if (!$overflow) {
            foreach ($users as $user) {
                $results[] = ['id' => $user->id, 'label' => user_search::get_label($user)];
            }
        }

        return [
            'users' => $results,
            'overflow' => $overflow,
            'maxresults' => user_search::MAX_RESULTS,
        ];
    }

    /**
     * Define return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'label' => new external_value(PARAM_RAW, 'Display name, plain text to be escaped by the caller'),
                ])
            ),
            'overflow' => new external_value(PARAM_BOOL, 'True when there are too many matches to list them'),
            'maxresults' => new external_value(PARAM_INT, 'Maximum number of users listed'),
        ]);
    }
}
