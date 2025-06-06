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

defined('MOODLE_INTERNAL') || die();require_once($CFG->libdir . '/formslib.php');

class menroll_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        // Define common select attributes!
        $selectattributes = ['multiple' => true, 'size' => 12, 'style' => 'width: 20em; margin-right: 2em; height: 20em;'];

        // Role dropdown!
        $roles = $this->_customdata['roles'];
        $mform->addElement('select', 'userroles', get_string('roletoassign', 'local_multiple_enrollments'), $roles);
        $mform->setType('userroles', PARAM_INT);
        $mform->addRule('userroles', get_string('required'), 'required', null, 'client');

        // Set "student" as the default role if it exists!
        $studentroleid = array_search('student', $roles);
        if ($studentroleid !== false) {
            $mform->setDefault('userroles', $studentroleid);
        }

        // Enrollment duration!
        $durationoptions = [
            '0' => get_string('unlimited',
            'local_multiple_enrollments'),
            ] + array_combine(range(1, 365),
            range(1, 365));
        $mform->addElement('select', 'enrolduration', get_string('enrolduration', 'local_multiple_enrollments'), $durationoptions);
        $mform->setType('enrolduration', PARAM_INT);

        // Enrollment type radio buttons!
        $radioarray = [];
        $radioarray[] = $mform->createElement(
            'radio',
            'enrollment',
            '',
            get_string('newenrollment', 'local_multiple_enrollments'),
            'newenrollment'
        );
        $radioarray[] = $mform->createElement(
            'radio',
            'enrollment',
            '',
            get_string('existingenrollment', 'local_multiple_enrollments'),
            'existingenrollment'
        );
        $mform->addGroup($radioarray, 'enrollmentgroup', get_string('enrollmenttype', 'local_multiple_enrollments'), ' ', false);
        $mform->setDefault('enrollment', 'newenrollment');
        $mform->addRule('enrollmentgroup', null, 'required', null, 'client');
        $mform->setType('enrollment', PARAM_TEXT);

        // New Enrollment Section!
        $mform->addElement('header', 'newenrollmentheader', get_string('newenrollment', 'local_multiple_enrollments'));

        // Course and User selection for new enrollment!
        $courses = $this->_customdata['courses'] ?? [];
        $users = $this->_customdata['users'] ?? [];

        // Options for multi-select autocomplete!
        $useroptions = [
            'multiple' => true, // Allow multiple users to be selected!
            'placeholder' => get_string('selectuser', 'local_multiple_enrollments'),
            'showsuggestions' => true, // Show suggestions as the user types!
            'tags' => false, // Do not allow custom tags!
        ];
        $courseoptions = [
            'multiple' => true, // Allow multiple courses to be selected!
            'placeholder' => get_string('selectcourses', 'local_multiple_enrollments'),
            'showsuggestions' => true,
            'tags' => false,
        ];

        // User selection!
        $userselect = $mform->createElement(
            'autocomplete',
            'new_selecteduser',
            get_string('roleuser', 'local_multiple_enrollments'),
            $users,
            $useroptions
        );

        // Course selection!
        $courseselect = $mform->createElement(
            'autocomplete',
            'selectedcourse',
            get_string('coursesrole', 'local_multiple_enrollments'),
            $courses,
            $courseoptions
        );

        // Group the elements into a single row!
        $mform->addGroup(
            [$userselect, $courseselect],
            'courseusergroup',
            get_string('courseusergroup', 'local_multiple_enrollments'),
            ' ',
            false
        );

        // Set types for each element!
        $mform->setType('selectedcourse', PARAM_RAW); // Change to PARAM_RAW because this will now be an array of values!
        $mform->setType('new_selecteduser', PARAM_RAW); // Change to PARAM_RAW because this will now be an array of values!

        // Existing Enrollment Section!
        $mform->addElement('header', 'existingenrollmentheader', get_string('existingenrollment', 'local_multiple_enrollments'));

        // User selection for existing enrollment (convert select to autocomplete)!
        $users = $this->_customdata['users'] ?? [];
        $options = [
            'multiple' => false, // Single user selection!
            'placeholder' => get_string('selectuser', 'local_multiple_enrollments'),
            'showsuggestions' => true, // Show suggestions as user types!
            'tags' => false, // Do not allow custom tags!
        ];
        $mform->addElement(
            'autocomplete',
            'existing_selecteduser',
            get_string('selectuser', 'local_multiple_enrollments'),
            $users,
            $options
        );
        $mform->setType('existing_selecteduser', PARAM_INT);
        $mform->addRule('existing_selecteduser', get_string('required'), 'required', null, 'client');

        // Grade recovery checkbox!
        $mform->addElement('advcheckbox', 'recovergrades', get_string('recovergrades', 'local_multiple_enrollments'));
        $mform->setType('recovergrades', PARAM_BOOL);

        // Existing courses selection!
        $existingcourses = $this->_customdata['existingcourses'] ?? [];
        $existingcoursesselect = $mform->createElement(
            'select',
            'existingcourses[]',
            get_string('existingcourse',
            'local_multiple_enrollments'),
            $existingcourses,
            $selectattributes
        );
        $mform->setType('existingcourses', PARAM_RAW);

        // Unassign button!
        $unassignbutton = $mform->createElement('button', 'unassign', get_string('unassign', 'local_multiple_enrollments'));

        // Create a subgroup for Existing Courses + Unassign button!
        $existingsubgroup = $mform->createElement(
            'group',
            'existingcoursegroup',
            '',
            [$existingcoursesselect, $unassignbutton],
            ' ',
            false
        );

        // Potential courses selection!
        $potentialcourses = $this->_customdata['potentialcourses'] ?? [];
        $potentialcoursesselect = $mform->createElement(
            'select',
            'potentialcourses[]',
            get_string('potentialcourses',
            'local_multiple_enrollments'),
            $potentialcourses,
            $selectattributes
        );
        $mform->setType('potentialcourses', PARAM_RAW);

        // Assign button!
        $assignbutton = $mform->createElement('button', 'assign', get_string('assign', 'local_multiple_enrollments'));

        // Create a subgroup for Potential Courses + Assign button!
        $potentialsubgroup = $mform->createElement(
            'group',
            'potentialcoursegroup',
            '',
            [$potentialcoursesselect, $assignbutton],
            ' ',
            false
        );

        // Group both sub-groups together in a larger group!
        $mform->addGroup(
            [$existingsubgroup, $potentialsubgroup],
            'coursesgroup',
            get_string('coursesgroup', 'local_multiple_enrollments'),
            ' ',
            false
        );
        $mform->addHelpButton('coursesgroup', 'coursesgroup', 'local_multiple_enrollments');

        // Buttons!
        $this->add_action_buttons(true, get_string('enrolselected', 'local_multiple_enrollments'));

        // Add JavaScript for dynamic behavior!
        $this->add_javascript();
    }

    // Add custom JavaScript for interactivity!
    protected function add_javascript() {
        global $PAGE;
        $PAGE->requires->js_init_code("
            require(['jquery'], function($) {
                function toggleEnrollmentFields() {
                    const enrollmentValue = $('input[name=\"enrollment\"]:checked').val();
                    if (enrollmentValue === 'newenrollment') {
                       // Show the submit button
                        $('#id_submitbutton').show();
                    } else if (enrollmentValue === 'existingenrollment') {
                        // Hide the submit button
                        $('#id_submitbutton').hide();
                    }
                }

                $(document).ready(function() {
                    toggleEnrollmentFields();
                    $('input[name=\"enrollment\"]').change(function() {
                        toggleEnrollmentFields();
                    });

                });
            });
        ");
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

         // Only validate New Enrollment fields if "New Enrollment" is selected!
        if (!empty($data['enrollment']) && $data['enrollment'] === 'newenrollment') {
            if (empty($data['new_selecteduser'])) {
                $errors['new_selecteduser'] = get_string('userrequired', 'local_multiple_enrollments');
            }
            if (empty($data['selectedcourse'])) {
                $errors['selectedcourse'] = get_string('courserequired', 'local_multiple_enrollments');
            }
        }

        // Only validate Existing Enrollment fields if "Existing Enrollment" is selected!
        if (!empty($data['enrollment']) && $data['enrollment'] === 'existingenrollment') {
            if (empty($data['existing_selecteduser'])) {
                $errors['existing_selecteduser'] = get_string('userrequired', 'local_multiple_enrollments');
            }
        }

        return $errors;
    }
}
