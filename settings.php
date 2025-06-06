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
// Check if the livetek_addons category exists under root!
defined('MOODLE_INTERNAL') || die();
if (!$ADMIN->locate('livetek_addons')) {
    $ADMIN->add('root', new admin_category('livetek_addons', get_string('livetek_addons', 'local_multiple_enrollments')));
}

// Add the plugin to "Site administration > Plugins > Local plugins"!
if (!$ADMIN->locate('multipleenrollments_local')) {
    $ADMIN->add('localplugins', new admin_externalpage(
    'multipleenrollments_local',
    get_string('multipleenrollment', 'local_multiple_enrollments'),
    "$CFG->wwwroot/local/multiple_enrollments/index.php",
    ['moodle/user:update', 'moodle/user:delete']
    ));
}

// Check if the subcategory 'multiple_enrollment_category' already exists under the 'users' section!
if (!$ADMIN->locate('multiple_enrollment_category')) {
    $ADMIN->add('users', new admin_category(
    'multiple_enrollment_category',
    get_string('multipleenrollment', 'local_multiple_enrollments')
    ));
}

// Add an external page link under the subcategory!
if (!$ADMIN->locate('multipleenrollments_users')) {
    $ADMIN->add('multiple_enrollment_category', new admin_externalpage(
    'multipleenrollments_users',
    get_string('multipleenrollment', 'local_multiple_enrollments'),
    "$CFG->wwwroot/local/multiple_enrollments/index.php",
    ['moodle/user:update', 'moodle/user:delete']
    ));
}

