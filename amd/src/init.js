define(['core/ajax', 'core/notification', 'core/str', 'jquery'], function(ajax, notification, str, $) {
    const menrol_init = function() {
        
        // Initialize enrollment type handling
        $('input[name="enrollment"]').on('change', function() {
            handleEnrollmentType($(this).val());
        });

        // Set the initial state based on the selected enrollment type
        const initialEnrollmentType = $('input[name="enrollment"]:checked').val();
        handleEnrollmentType(initialEnrollmentType);

        // Add event listeners for assign/unassign buttons
        $('#id_assign').on('click', function() {
            handleCourseAssignment('assign');
        });

        $('#id_unassign').on('click', function() {
            handleCourseAssignment('unassign');
        });

        $(document).on('change', '#id_new_selecteduser_, #id_existing_selecteduser', function() {
            const selectedUser = $(this).val();
            clearCourseLists();
            if (selectedUser) {
                refreshCourseLists(selectedUser);
            }
        });

         // Attach autocomplete event for #id_existing_selecteduser
         const existingUserSelector = '#id_existing_selecteduser';
         attachAutocomplete(existingUserSelector);
        
        
         // Populate courses for the initially selected user if set (only for existing inscription)
         const initialSelectedUser = $(existingUserSelector).val();
         if (initialSelectedUser) {
             refreshCourseLists(initialSelectedUser);
         }
    };

    const attachAutocomplete = function(selector) {
        $(selector).on('change', function(event) {
            const selectedUser = $(this).val();
            clearCourseLists();
            if (selectedUser) {
                refreshCourseLists(selectedUser);
            }
        });
    };

    const handleEnrollmentType = function(type) {
        if (type === 'newenrollment') {
            $('#id_newenrollmentheader').show();
            $('#id_existingenrollmentheader').hide();
            clearNewSelections();
        } else if (type === 'existingenrollment') {
            $('#id_newenrollmentheader').hide();
            $('#id_existingenrollmentheader').show();
            clearExistingSelections();
        }
    };

    const clearExistingSelections = function() {
        $('#id_existingcourses').val([]); // Clear the selection
        $('#id_potentialcourses').val([]); // Clear the selection
    };

    const clearNewSelections = function() {
        $('#id_selecteduser').val([]); // Clear the user select list
        $('#id_selectedcourse').val([]); // Clear the course select list
    };

    const clearCourseLists = function() {
        $('#id_existingcourses').empty();
        $('#id_potentialcourses').empty();
    };

    const handleCourseAssignment = function(action) {
        const selectedCourses = action === 'assign' 
            ? $('#id_potentialcourses_').val() 
            : $('#id_existingcourses_').val();

        if (!selectedCourses || selectedCourses.length === 0) {
            str.get_strings([
                {key: 'error', component: 'core'},
                {key: 'selectcourses', component: 'local_multiple_enrollments'}
            ]).then(function(strings) {
                notification.alert(strings[0], strings[1], 'OK');
            });
            return;
        }

        const enrollmentType = $('input[name="enrollment"]:checked').val();
        let selectedUsers;

        if (enrollmentType === 'newenrollment') {
            selectedUsers = $('#id_new_selecteduser_').val(); // Multiple users (array)
        } else if (enrollmentType === 'existingenrollment') {
            selectedUsers = [$('#id_existing_selecteduser').val()]; // Single user, but use array to normalize
        }

        if (!selectedUsers || selectedUsers.length === 0 || !selectedUsers[0]) {
            console.error("User not selected, #id_selecteduser not found or has no value.");
            str.get_strings([
                {key: 'error', component: 'core'},
                {key: 'selectuser', component: 'local_multiple_enrollments'}
            ]).then(function(strings) {
                notification.alert(strings[0], strings[1], 'OK');
            });
            return;
        }

        const roleid = parseInt($('#id_userroles').val(), 10); // Get role ID from the form
        const recovergrades = $('#id_recovergrades').is(':checked') ? 1 : 0;
        const enrolDuration = parseInt($('#id_enrolduration').val(), 10) || 0;
        const params = {
            action: action,
            roleid: roleid,
            userid: parseInt(selectedUsers[0], 10),
            courses: selectedCourses.map(Number),
            recovergrades: recovergrades,
            enrolduration: enrolDuration
        };


        ajax.call([{
            methodname: 'local_multiple_enrollments_manage_courses',
            args: params,
        }])[0].then(function(response) {

            if (response && response[0].success) {
                str.get_strings([
                    {key: 'success', component: 'core'},
                    {key: 'coursesupdated', component: 'local_multiple_enrollments'}
                ]).then(function(strings) {
                    notification.alert(strings[0], strings[1], 'OK');
                });

                refreshCourseLists(selectedUsers[0]);
            }
        }).catch(function(error) {
            str.get_strings([
                {key: 'error', component: 'core'},
                {key: 'updateerror', component: 'local_multiple_enrollments'}
            ]).then(function(strings) {
                notification.alert(strings[0], strings[1], 'OK');
            });
            console.error('Course assignment failed:', error);
        });
    };

    const refreshCourseLists = function(userid) {
        ajax.call([{
            methodname: 'local_multiple_enrollments_get_updated_courses',
            args: { userid: parseInt(userid, 10) },
        }])[0].then(function(response) {
            if (response) {
                // Update existing courses
                const existingCoursesSelect = $('#id_existingcourses_');
                existingCoursesSelect.empty();
                if (response.existingcourses && response.existingcourses.length > 0) {
                    response.existingcourses.forEach(course => {
                        existingCoursesSelect.append(
                            $('<option>', { value: course.id, text: course.fullname })
                        );
                    });
                } else {
                    str.get_string('nocourses', 'local_multiple_enrollments').then(function(nocourses) {
                        existingCoursesSelect.append(
                            $('<option>', { value: '', text: nocourses, disabled: true })
                        );
                    });
                }

                // Update potential courses
                const potentialCoursesSelect = $('#id_potentialcourses_');
                potentialCoursesSelect.empty();
                if (response.potentialcourses && response.potentialcourses.length > 0) {
                    response.potentialcourses.forEach(course => {
                        potentialCoursesSelect.append(
                            $('<option>', { value: course.id, text: course.fullname })
                        );
                    });
                } else {
                    str.get_string('nocourses', 'local_multiple_enrollments').then(function(nocourses) {
                        potentialCoursesSelect.append(
                            $('<option>', { value: '', text: nocourses, disabled: true })
                        );
                    });
                }
            }
        }).catch(function(error) {
            str.get_strings([
                {key: 'error', component: 'core'},
                {key: 'refresherror', component: 'local_multiple_enrollments'}
            ]).then(function(strings) {
                notification.alert(strings[0], strings[1], 'OK');
            });
            console.error('Failed to refresh course lists:', error);
        });
    };

    return {
        init: menrol_init
    };
});
