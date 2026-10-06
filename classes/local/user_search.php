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
 * User search for the user pickers of the enrolment page.
 *
 * @package   local_multiple_enrollments
 * @copyright 2025 E-learning Touch' <contact@elearningtouch.com> (Maintainer)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_multiple_enrollments\local;

use context_system;
use stdClass;

/**
 * Finds the users that can be picked on the enrolment page.
 *
 * The pickers query the server instead of receiving the whole user table, so the page
 * cost no longer grows with the number of users on the site.
 */
class user_search {
    /** @var int Maximum number of users returned by one search. */
    const MAX_RESULTS = 100;

    /**
     * Search active users by name, and by username / ID number when the viewer may see them.
     *
     * @param string $query Text to search, empty for no filter.
     * @param int $limit Maximum number of records to return.
     * @return stdClass[] Users indexed by id, ordered by last name.
     */
    public static function search(string $query, int $limit): array {
        global $DB;

        [$where, $params] = self::selectable_where();

        $query = trim($query);
        if ($query !== '') {
            $fields = [
                $DB->sql_fullname('u.firstname', 'u.lastname'),
                $DB->sql_fullname('u.lastname', 'u.firstname'),
            ];
            if (self::can_view_identity()) {
                $fields[] = 'u.username';
                $fields[] = 'u.idnumber';
            }
            $conditions = [];
            foreach ($fields as $i => $field) {
                $conditions[] = $DB->sql_like($field, ':search' . $i, false, false);
                $params['search' . $i] = '%' . $DB->sql_like_escape($query) . '%';
            }
            $where .= ' AND (' . implode(' OR ', $conditions) . ')';
        }

        $sql = 'SELECT ' . self::select_fields() . ' FROM {user} u WHERE ' . $where . ' ORDER BY u.lastname, u.firstname, u.id';
        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * Load the selectable users among the given ids.
     *
     * @param array $userids User ids, as submitted by the form.
     * @return stdClass[] Users indexed by id; ids that are not selectable are left out.
     */
    public static function get_users(array $userids): array {
        global $DB;

        $userids = self::clean_ids($userids);
        if (!$userids) {
            return [];
        }

        [$where, $params] = self::selectable_where();
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $sql = 'SELECT ' . self::select_fields() . ' FROM {user} u WHERE ' . $where . ' AND u.id ' . $insql;
        return $DB->get_records_sql($sql, $params + $inparams);
    }

    /**
     * Whether every given id is a selectable user.
     *
     * @param array $userids User ids, as submitted by the form.
     * @return bool False if the list is empty or contains an id that cannot be selected.
     */
    public static function all_selectable(array $userids): bool {
        $cleanids = self::clean_ids($userids);
        if (!$cleanids || count($cleanids) !== count(array_unique($userids))) {
            return false;
        }
        return count(self::get_users($cleanids)) === count($cleanids);
    }

    /**
     * Display name of a user in the pickers: full name, plus username and ID number when the viewer may see them.
     *
     * @param stdClass $user User record from {@see search()} or {@see get_users()}.
     * @return string Plain text, not escaped.
     */
    public static function get_label(stdClass $user): string {
        $label = fullname($user);

        if (self::can_view_identity()) {
            $identityfields = [];
            if (!empty($user->username)) {
                $identityfields[] = $user->username;
            }
            if (!empty($user->idnumber)) {
                $identityfields[] = $user->idnumber;
            }
            if ($identityfields) {
                $label .= ' (' . implode(' - ', $identityfields) . ')';
            }
        }

        return $label;
    }

    /**
     * Conditions shared by every query: active users only, never the guest account.
     *
     * @return array [sql, params]
     */
    protected static function selectable_where(): array {
        global $CFG;

        return ['u.deleted = 0 AND u.suspended = 0 AND u.id <> :guestid', ['guestid' => $CFG->siteguest]];
    }

    /**
     * Fields needed to build a label.
     *
     * @return string
     */
    protected static function select_fields(): string {
        $fields = array_merge(['id', 'username', 'idnumber'], \core_user\fields::get_name_fields());
        return implode(', ', array_map(function($field) {
            return 'u.' . $field;
        }, $fields));
    }

    /**
     * Keep the positive integer ids of a submitted list.
     *
     * @param array $userids
     * @return int[]
     */
    protected static function clean_ids(array $userids): array {
        $cleanids = [];
        foreach ($userids as $userid) {
            if (is_numeric($userid) && (int) $userid > 0 && (string) (int) $userid === (string) $userid) {
                $cleanids[(int) $userid] = (int) $userid;
            }
        }
        return array_values($cleanids);
    }

    /**
     * Whether the current user may see the username and ID number of other users.
     *
     * @return bool
     */
    protected static function can_view_identity(): bool {
        return has_capability('moodle/site:viewuseridentity', context_system::instance());
    }
}
