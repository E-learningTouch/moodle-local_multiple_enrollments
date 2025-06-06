define(['core/ajax', 'core/notification', 'core/str', 'jquery'], function(ajax, notification, str, $) {
    const menrol_init = function() {
        
        // Initialize enrollment type handling
        $('input[name="enrollment"]').on('change', function() {
            handleenrollmenttype($(this).val());
        });

        // Set the initial state based on the selected enrollment type
        const initialenrollmenttype = $('input[name="enrollment"]:checked').val();
        handleenrollmenttype(initialenrollmenttype);

        // Add event listeners for assign/unassign buttons
        $('#id_assign').on('click', function() {
            handlecourseassignement('assign');
        });

        $('#id_unassign').on('click', function() {
            handlecourseassignement('unassign');
        });

        $(document).on('change', '#id_new_selecteduser_, #id_existing_selecteduser', function() {
            const selecteduser = $(this).val();
            clearcourselists();
            if (selecteduser) {
                refreshCourseLists(selecteduser);
            }
        });

         // Attach autocomplete event for #id_existing_selecteduser
         const existinguserselector = '#id_existing_selecteduser';
         attachautocomplete(existinguserselector);
        
        
         // Populate courses for the initially selected user if set (only for existing inscription)
         const initialselecteduser = $(existinguserselector).val();
         if (initialselecteduser) {
             refreshCourseLists(initialselecteduser);
         }
    };

    const attachautocomplete = function(selector) {
        $(selector).on('change', function(event) {
            const selecteduser = $(this).val();
            clearcourselists();
            if (selecteduser) {
                refreshCourseLists(selecteduser);
            }
        });
    };

    const handleenrollmenttype = function(type) {
        if (type === 'newenrollment') {
            $('#id_newenrollmentheader').show();
            $('#id_existingenrollmentheader').hide();
            clearnewselections();
        } else if (type === 'existingenrollment') {
            $('#id_newenrollmentheader').hide();
            $('#id_existingenrollmentheader').show();
            clearexistingselections();
        }
    };

    const clearexistingselections = function() {
        $('#id_existingcourses').val([]); // Clear the selection
        $('#id_potentialcourses').val([]); // Clear the selection
    };

    const clearnewselections = function() {
        $('#id_selecteduser').val([]); // Clear the user select list
        $('#id_selectedcourse').val([]); // Clear the course select list
    };

    const clearcourselists = function() {
        $('#id_existingcourses').empty();
        $('#id_potentialcourses').empty();
    };

    const handlecourseassignement = function(action) {
        const selectedcourses = action === 'assign' 
            ? $('#id_potentialcourses_').val() 
            : $('#id_existingcourses_').val();

        if (!selectedcourses || selectedcourses.length === 0) {
            str.get_strings([
                {key: 'error', component: 'core'},
                {key: 'selectcourses', component: 'local_multiple_enrollments'}
            ]).then(function(strings) {
                notification.alert(strings[0], strings[1], 'OK');
            });
            return;
        }

        const enrollmenttype = $('input[name="enrollment"]:checked').val();
        let selectedusers;

        if (enrollmenttype === 'newenrollment') {
            selectedusers = $('#id_new_selecteduser_').val(); // Multiple users (array)
        } else if (enrollmenttype === 'existingenrollment') {
            selectedusers = [$('#id_existing_selecteduser').val()]; // Single user, but use array to normalize
        }

        if (!selectedusers || selectedusers.length === 0 || !selectedusers[0]) {
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
        const enrolDuration = parseInt($('#id_enrol_duration').val(), 10) || 0;
        const params = {
            action: action,
            roleid: roleid,
            userid: parseInt(selectedusers[0], 10), 
            courses: selectedcourses.map(Number),
            recovergrades: recovergrades,
            enrol_duration: enrolDuration
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
                
                refreshcourselists(selectedusers[0]);
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

    const refreshcourselists = function(userid) {
        ajax.call([{
            methodname: 'local_multiple_enrollments_get_updated_courses',
            args: { userid: parseInt(userid, 10) },
        }])[0].then(function(response) {
            if (response) {
                // Update existing courses
                const existingcoursesselect = $('#id_existingcourses_');
                existingcoursesselect.empty();
                if (response.existingcourses && response.existingcourses.length > 0) {
                    response.existingcourses.forEach(course => {
                        existingcoursesselect.append(
                            $('<option>', { value: course.id, text: course.fullname })
                        );
                    });
                } else {
                    str.get_string('nocourses', 'local_multiple_enrollments').then(function(nocourses) {
                        existingcoursesselect.append(
                            $('<option>', { value: '', text: nocourses, disabled: true })
                        );
                    });
                }

                // Update potential courses
                const potentialcoursesselect = $('#id_potentialcourses_');
                potentialcoursesselect.empty();
                if (response.potentialcourses && response.potentialcourses.length > 0) {
                    response.potentialcourses.forEach(course => {
                        potentialcoursesselect.append(
                            $('<option>', { value: course.id, text: course.fullname })
                        );
                    });
                } else {
                    str.get_string('nocourses', 'local_multiple_enrollments').then(function(nocourses) {
                        potentialcoursesselect.append(
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
