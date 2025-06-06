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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_multiple_enrollments_assign_courses' => [
        'classname' => 'local_multiple_enrollments\external\assign_courses',
        'methodname' => 'execute',
        'classpath' => 'local/multiple_enrollments/externallib.php',
        'description' => 'Assign multiple users to multiple courses',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_multiple_enrollments_get_updated_courses' => [
        'classname'   => 'local_multiple_enrollments\\external\\get_updated_courses',
        'methodname'  => 'execute',
        'classpath'   => 'local/multiple_enrollments/externallib.php',
        'description' => 'Fetch updated existing and potential courses for a user',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_multiple_enrollments_manage_courses' => [
        'classname'   => 'local_multiple_enrollments\external\manage_courses',
        'methodname'  => 'execute',
        'classpath'   => 'local/multiple_enrollments/externallib.php',
        'description' => 'Manage courses for a user (assign or unassign)',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
