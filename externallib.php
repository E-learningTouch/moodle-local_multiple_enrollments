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

namespace local_multiple_enrollments\external;
use context_system;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_course;
defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/externallib.php");


class assign_courses extends external_api {
    /**
     * Define function parameters.
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'enrollmenttype' => new external_value(PARAM_TEXT, 'Type of enrollment'),
            'roleid' => new external_value(PARAM_INT, 'Role ID'), // Mandatory field.
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_DEFAULT, null),
            'courseids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID'),
                'List of course IDs'
            ),
            'recovergrades' => new external_value(PARAM_INT, 'Recover grades (1 or 0)', VALUE_DEFAULT, 0),
            'enrolduration' => new external_value(PARAM_INT, 'Enrollment duration', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the function.
     */
    public static function execute($enrollmenttype, $roleid, $userid, $courseids, $recovergrades, $enrolduration) {
        global $DB;

        self::validate_parameters(self::execute_parameters(), [
            'enrollmenttype' => $enrollmenttype,
            'roleid' => $roleid,
            'userid' => $userid,
            'courseids' => $courseids,
            'recovergrades' => $recovergrades,
            'enrolduration' => $enrolduration,
        ]);

        // Validate capabilities and context.
        $context = context_system::instance();
        require_capability('local/multiple_enrollments:manage', $context);

        $timestart = time();
        $timeend = $enrolduration > 0 ? $timestart + ($enrolduration * DAYSECS) : 0;

        $results = [];
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
            $coursecontext = context_course::instance($courseid);

            if ($enrollmenttype === 'add') {
                $enrolplugin = enrol_get_plugin('manual');
                $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', MUST_EXIST);

                if ($enrolplugin->allow_enrol($instance) && has_capability('enrol/manual:enrol', $coursecontext)) {
                    $enrolplugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend);

                    if ($recovergrades) {
                        require_once($CFG->libdir . '/gradelib.php');
                        grade_recover_history_grades($userid, $courseid);
                    }
                    $results[] = ['courseid' => $courseid, 'status' => 'enrolled', 'success' => true ];
                }
            } else if ($enrollmenttype === 'remove') {
                $enrolplugin = enrol_get_plugin('manual');
                $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', MUST_EXIST);
                // Fetch the user enrolment record.
                $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid], '*', MUST_EXIST);
                if ($enrolplugin->allow_unenrol_user($instance, $ue) && has_capability('enrol/manual:unenrol', $coursecontext)) {
                    $enrolplugin->unenrol_user($instance, $userid);
                    $results[] = ['courseid' => $courseid, 'status' => 'unenrolled', 'success' => true];
                } else {
                    $results[] = ['courseid' => $courseid, 'status' => 'failed to unenrol', 'success' => false];
                }
            }
        }

        return $results;
    }

    /**
     * Define return values.
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'status' => new external_value(PARAM_TEXT, 'Status of enrolment action'),
                'success' => new external_value(PARAM_BOOL, 'Indicates if the action was successful'),
            ])
        );
    }
}

class get_updated_courses extends external_api {
    /**
     * Define parameters for the function.
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    /**
     * Execute the function.
     */
    public static function execute($userid) {
        global $DB;

        self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
        ]);

        // Validate capabilities and context.
        $context = context_system::instance();
        require_capability('local/multiple_enrollments:manage', $context);

        // Fetch existing and potential courses.
        $existingcourses = $DB->get_records_sql(
            "SELECT c.id, c.fullname
            FROM {course} c
            JOIN {enrol} e ON e.courseid = c.id
            JOIN {user_enrolments} ue ON ue.enrolid = e.id
            WHERE ue.userid = :userid AND c.visible = 1
            ORDER BY c.fullname ASC",
            ['userid' => $userid]
        );

        $potentialcourses = $DB->get_records_sql(
            "SELECT c.id, c.fullname
             FROM {course} c
             WHERE c.visible = 1
             AND c.id NOT IN (
                 SELECT c.id
                 FROM {course} c
                 JOIN {enrol} e ON e.courseid = c.id
                 JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ue.userid = :userid
             )
             ORDER BY c.fullname ASC",
            ['userid' => $userid]
        );

        return [
            'existingcourses' => array_values(array_map(function($course) {
                return [
                    'id' => $course->id,
                    'fullname' => $course->fullname,
                ];
            }, $existingcourses)),
            'potentialcourses' => array_values(array_map(function($course) {
                return [
                    'id' => $course->id,
                    'fullname' => $course->fullname,
                ];
            }, $potentialcourses)),
        ];
    }

    /**
     * Define return structure.
     */
    public static function execute_returns() {
        return new external_single_structure([
            'existingcourses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                ])
            ),
            'potentialcourses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                ])
            ),
        ]);
    }
}

class manage_courses extends external_api {

    /**
     * Define the parameters for the web service.
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'action' => new external_value(PARAM_TEXT, 'Action to perform (assign or unassign)'),
            'userid' => new external_value(PARAM_INT, 'The user to manage course enrollments for'),
            'courses' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Course ID'),
                'List of course IDs'
            ),
            'roleid' => new external_value(PARAM_INT, 'Role ID', VALUE_REQUIRED),
            'recovergrades' => new external_value(PARAM_BOOL, 'Whether to recover grades for the user', VALUE_DEFAULT, false),
            'enrolduration' => new external_value(PARAM_INT, 'Enrollment duration in days (0 for unlimited)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute($action, $userid, $courses, $roleid, $recovergrades = 0, $enrolduration = 0) {
        global $DB;

        // Validate input parameters.
        self::validate_parameters(self::execute_parameters(), [
            'action' => $action,
            'userid' => $userid,
            'courses' => $courses,
            'roleid' => $roleid,
            'recovergrades' => $recovergrades,
            'enrolduration' => $enrolduration,
        ]);

        if (empty($userid) || !is_numeric($userid)) {
            throw new \moodle_exception('missingparameter', 'local_multiple_enrollments', '', 'userid');
        }

        // Validate capabilities.
        $context = context_system::instance();
        require_capability('local/multiple_enrollments:manage', $context);

        // Calculate start and end time.
        $timestart = make_timestamp(date('Y'), date('m'), date('d'), 0, 0, 0);
        if ($enrolduration <= 0) {
            $timeend = 0; // No end date.
        } else {
            $timeend = $timestart + ($enrolduration * 24 * 60 * 60);
        }

        $results = [];
        foreach ($courses as $courseid) {
            $coursecontext = context_course::instance($courseid);
            $enrolplugin = enrol_get_plugin('manual');
            $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', MUST_EXIST);

            if ($action === 'assign') {
                if ($enrolplugin->allow_enrol($instance) && has_capability('enrol/manual:enrol', $coursecontext)) {

                    $enrolplugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend);

                    if ($recovergrades) {
                        global $CFG;
                        require_once($CFG->libdir . '/gradelib.php');
                        grade_recover_history_grades($userid, $courseid);
                    }
                    $results[] = ['courseid' => $courseid, 'status' => 'enrolled', 'success' => true];
                } else {
                    $results[] = ['courseid' => $courseid, 'status' => 'failed', 'success' => false];
                }
            } else if ($action === 'unassign') {
                $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid], '*', IGNORE_MISSING);
                if (
                        $ue &&
                        $enrolplugin->allow_unenrol_user($instance, $ue) &&
                        has_capability('enrol/manual:unenrol', $coursecontext)
                    ) {
                    $enrolplugin->unenrol_user($instance, $userid);
                    $results[] = ['courseid' => $courseid, 'status' => 'unenrolled', 'success' => true];
                } else {
                    $results[] = ['courseid' => $courseid, 'status' => 'failed', 'success' => false];
                }
            }
        }

        return $results;
    }

    /**
     * Define the return structure for the web service.
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'status' => new external_value(PARAM_TEXT, 'Status of the action'),
                'success' => new external_value(PARAM_BOOL, 'Indicates if the action was successful'),
            ])
        );
    }
}
