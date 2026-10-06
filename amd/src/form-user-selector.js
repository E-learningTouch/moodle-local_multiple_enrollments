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
 * Server-side user search for the user pickers of the enrolment page.
 *
 * Implements the transport / processResults contract of core/form-autocomplete.
 *
 * @module     local_multiple_enrollments/form-user-selector
 * @copyright  2025 E-learning Touch' <contact@elearningtouch.com> (Maintainer)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/str'], function(Ajax, Str) {

    // The autocomplete inserts labels as HTML: user names must be escaped.
    var escapeHtml = function(text) {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    return {
        processResults: function(selector, results) {
            if (!Array.isArray(results)) {
                // A message, such as "too many users to show".
                return results;
            }
            return results.map(function(user) {
                return {value: user.id, label: escapeHtml(user.label)};
            });
        },

        transport: function(selector, query, success, failure) {
            Ajax.call([{
                methodname: 'local_multiple_enrollments_search_users',
                args: {query: query}
            }])[0].then(function(response) {
                if (response.overflow) {
                    return Str.get_string('toomanyuserstoshow', 'core', '>' + response.maxresults).then(success);
                }
                return success(response.users);
            }).catch(failure);
        }
    };
});
