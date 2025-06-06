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

 require_once('../../config.php');
require_once($CFG->dirroot . '/local/multiple_enrollments/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/local/multiple_enrollments/forms/menroll_form.php');


require_login();
require_capability('local/multiple_enrollments:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/multiple_enrollments/index.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('multiple_enrollments_title', 'local_multiple_enrollments'));
$PAGE->set_heading(get_string('multiple_enrollments_title', 'local_multiple_enrollments'));
$PAGE->requires->js_call_amd('local_multiple_enrollments/init', 'init');

// Fetch data for the form.
$roles = $DB->get_records_menu('role', null, 'shortname ASC', 'id, shortname');
$courses = $DB->get_records_menu('course', ['visible' => 1], 'fullname ASC', 'id, fullname');
$users = $DB->get_records_menu('user', ['deleted' => 0, 'suspended' => 0], 'lastname ASC', 'id, CONCAT(lastname, " ",firstname)');

$form = new menroll_form(null, ['roles' => $roles, 'courses' => $courses, 'users' => $users]);

// Fetch data for the form.
$rolescourse = $DB->get_records('role', null, 'shortname ASC');
$defaultrole = $DB->get_record('role', ['shortname' => 'student']);
if (!$defaultrole) {
    throw new moodle_exception('Default role "student" does not exist.');
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/index.php'));
} else if ($data = $form->get_data()) {
    $selectedusers = isset($data->new_selecteduser) ? $data->new_selecteduser : [];
    $selectedcourses = isset($data->selectedcourse) ? $data->selectedcourse : [];

    $roleid = $data->userroles ?? 0;
    $enrolduration = isset($data->enrolduration) ? (int) $data->enrolduration : 0;

    $timestart = time();
    $timeend = $enrolduration > 0 ? $timestart + ($enrolduration * DAYSECS) : 0;

    foreach ($selectedcourses as $courseid) {
        $courseid = (int) $courseid;
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = context_course::instance($courseid);
        $enrolplugin = enrol_get_plugin('manual');
        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $courseid], '*', MUST_EXIST);

        foreach ($selectedusers as $userid) {
            $userid = (int) $userid; // Force userid to be an integer.
            if ($enrolplugin && $enrolplugin->allow_enrol($instance) && has_capability('enrol/manual:enrol', $context)) {
                $enrolplugin->enrol_user($instance, $userid, $roleid, $timestart, $timeend);
            }
        }
    }

    redirect(new moodle_url('/local/multiple_enrollments/index.php'), get_string('assignmessage', 'local_multiple_enrollments'));
}





// Render the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('multiple_enrollments_title', 'local_multiple_enrollments'));
$form->display();
echo $OUTPUT->footer();
