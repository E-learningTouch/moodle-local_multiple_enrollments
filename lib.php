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
 * Version information.
 *
 * Multiple Enrollments - Allows admin to enrol one or more users into multiple courses at the same time.
 *                        There is a single screen which allows admin to manage course enrolments.
 *
 * @package   local_multiple_enrollments
 * @copyright 2013 Deepali Gujarathi (Original Coder)
 * @copyright 2025 E-learning Touch' <contact@elearningtouch.com> (Maintainer)
 * @author    Samar Al Khalil <155988552+Sam-elearning@users.noreply.github.com> (Coder)
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * Extend settings navigation for this plugin.
  *
  * @param settings_navigation $settingsnav
  * @param context $context
  */
function local_multiple_enrollments_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    // Use this function if navigation nodes need to be added.
}

 /**
  * Get all active courses in Moodle.
  *
  * @return array List of active courses.
  */
function get_all_active_courses() {
    global $DB;
    $courses = $DB->get_records('course', ['visible' => 1], 'fullname ASC', 'id, fullname, shortname');
    return array_values($courses); // Ensure an indexed array!
}

 /**
  * Get courses for a specific user.
  *
  * @param int $userid The ID of the user.
  * @return array An array with 'existingcourses' and 'potentialcourses'.
  */
function get_user_courses($userid) {
    global $DB;
    // Get courses where the user is enrolled!
    $sqlexisting = "SELECT c.id, c.fullname
                    FROM {course} c
                    JOIN {enrol} e ON e.courseid = c.id
                    JOIN {user_enrolments} ue ON ue.enrolid = e.id
                    WHERE ue.userid = :userid AND c.visible = 1";
    $existingcourses = $DB->get_records_sql($sqlexisting, ['userid' => $userid]);

    // Get all courses excluding those where the user is already enrolled.
    $sqlpotential = "SELECT c.id, c.fullname
                      FROM {course} c
                      WHERE c.visible = 1
                      AND c.id NOT IN (
                        SELECT c.id
                        FROM {course} c
                        JOIN {enrol} e ON e.courseid = c.id
                        JOIN {user_enrolments} ue ON ue.enrolid = e.id
                        WHERE ue.userid = :userid
                      )";
    $potentialcourses = $DB->get_records_sql($sqlpotential, ['userid' => $userid]);

    return [
        'existingcourses' => array_values($existingcourses),
        'potentialcourses' => array_values($potentialcourses),
    ];
}

/**
 * AJAX function to fetch updated courses for a user.
 *
 * @param int $userid The user ID to fetch courses for.
 * @return array An array with 'existingcourses' and 'potentialcourses'.
 * @throws invalid_parameter_exception
 */
function local_multiple_enrollments_get_updated_courses($userid) {
    if (!$userid || !is_numeric($userid)) {
        throw new invalid_parameter_exception('Invalid user ID.');
    }

    return get_user_courses($userid);
}

/**
 * Renderer for the multiple enrollments plugin.
 */
class local_multiple_enrollments_renderer extends plugin_renderer_base {
    /**
     * Render the enrolment form.
     *
     * @param array $roles List of roles to display in the dropdown.
     * @param int $selectedroleid The ID of the currently selected role.
     * @param array|null $enroldurationoptions Optional list of enrol durations (default is 1 to 365).
     * @return string Rendered HTML.
     */
    public function render_menrol_form($roles, $selectedroleid, $enroldurationoptions = null) {
        if (!$enroldurationoptions) {
            $enroldurationoptions = range(1, 365);
        }

        $data = [
        'roles' => [function($role) use ($selectedroleid) {
            return [
            'id' => $role->id,
            'shortname' => $role->shortname,
            'selected' => ($role->id == $selectedroleid),
            ];
        }, $roles],
        'enrol_duration_options' => [function($days) {
            return ['value' => $days];
        }, $enroldurationoptions],
        ];

        return $this->render_from_template('local_multiple_enrollments/menrol_form', $data);
    }
}
