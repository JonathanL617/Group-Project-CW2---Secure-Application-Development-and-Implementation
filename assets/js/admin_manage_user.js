
 // assets/js/admin_manage_user.js

document.addEventListener('DOMContentLoaded', () => {
    const staffTableBody = document.getElementById('staffTable');
    const patientTableBody = document.getElementById('patientTable');
    const staffSearch = document.getElementById('staffSearch');
    const patientSearch = document.getElementById('patientSearch');
    const editStaffForm = document.getElementById('editStaffForm');
    const editPatientForm = document.getElementById('editPatientForm');
    const staffFormErrors = document.getElementById('staffFormErrors');
    const patientFormErrors = document.getElementById('patientFormErrors');

    // Validation patterns
    const emailPattern = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
    const phonePattern = /^[0-9]{10,15}$/;
    const namePattern = /^[a-zA-Z\s]{1,100}$/;
    const specializationPattern = /^[a-zA-Z\s]{1,100}$/;
    const addressPattern = /^.{1,255}$/;
    const dobPattern = /^\d{4}-\d{2}-\d{2}$/;

    let staffDataCache = [];
    let patientDataCache = [];

    // XSS Prevention - Display data as plain text
    function appendSafeCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.appendChild(cell);
    }

    // Display table messages safely
    function showTableMessage(tableBody, columns, message, isError = false) {
        if (!tableBody) return;

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = columns;
        cell.className = isError
            ? 'text-center text-danger'
            : 'text-center text-muted';

        cell.textContent = message;
        row.appendChild(cell);
        tableBody.replaceChildren(row);
    }

    // Display error messages safely
    function showFormErrors(container, messages) {
        if (!container) return;

        container.style.display = 'block';
        container.style.whiteSpace = 'pre-line';
        container.textContent = Array.isArray(messages)
            ? messages.join('\n')
            : String(messages);
    }

    function clearFormErrors(container) {
        if (!container) return;

        container.style.display = 'none';
        container.textContent = '';
    }

    // Create Edit button safely
    function appendEditButton(row, id, modalSelector) {
        const actionCell = document.createElement('td');
        const editButton = document.createElement('button');

        editButton.type = 'button';
        editButton.className = 'btn btn-primary btn-sm edit-btn';
        editButton.textContent = 'Edit';

        editButton.dataset.id = String(id ?? '');
        editButton.setAttribute('data-bs-toggle', 'modal');
        editButton.setAttribute('data-bs-target', modalSelector);

        actionCell.appendChild(editButton);
        row.appendChild(actionCell);
    }

    // Populate Staff table - XSS Prevention
    function populateStaffTable(data) {
        if (!staffTableBody) return;

        staffTableBody.replaceChildren();

        if (!Array.isArray(data) || data.length === 0) {
            showTableMessage(staffTableBody, 10, 'No staff found.');
            return;
        }

        data.forEach(staff => {
            const row = document.createElement('tr');

            // Use textContent instead of innerHTML
            appendSafeCell(row, staff.staff_id);
            appendSafeCell(row, staff.staff_name);
            appendSafeCell(row, staff.staff_dob);
            appendSafeCell(row, staff.staff_specialization);
            appendSafeCell(row, staff.staff_role);
            appendSafeCell(row, staff.staff_phone);
            appendSafeCell(row, staff.staff_email);
            appendSafeCell(row, staff.registered_by);
            appendSafeCell(row, staff.staff_status);

            appendEditButton(row, staff.staff_id, '#editStaffModal');

            staffTableBody.appendChild(row);
        });
    }

    // Populate Patient table - XSS Prevention
    function populatePatientTable(data) {
        if (!patientTableBody) return;

        patientTableBody.replaceChildren();

        if (!Array.isArray(data) || data.length === 0) {
            showTableMessage(patientTableBody, 9, 'No patients found.');
            return;
        }

        data.forEach(patient => {
            const row = document.createElement('tr');

            // Use textContent instead of innerHTML
            appendSafeCell(row, patient.patient_id);
            appendSafeCell(row, patient.patient_name);
            appendSafeCell(row, patient.patient_date_of_birth);
            appendSafeCell(row, patient.patient_phone);
            appendSafeCell(row, patient.patient_gender);
            appendSafeCell(row, patient.patient_email);
            appendSafeCell(row, patient.patient_address);
            appendSafeCell(row, patient.registered_by);

            appendEditButton(row, patient.patient_id, '#editPatientModal');

            patientTableBody.appendChild(row);
        });
    }

    // Update dashboard statistics
    function updateCounters() {
        const totalStaff = staffDataCache.length;

        const activeStaff = staffDataCache.filter(
            s => s.staff_status === 'active'
        ).length;

        const totalPatients = patientDataCache.length;

        const currentAdminId =
            typeof loggedInUserId !== 'undefined'
                ? loggedInUserId
                : '';

        const registeredByCurrent =
            staffDataCache.filter(
                s => s.registered_by === currentAdminId
            ).length +
            patientDataCache.filter(
                p => p.registered_by === currentAdminId
            ).length;

        document.getElementById('totalStaff').textContent = totalStaff;
        document.getElementById('activeStaff').textContent = activeStaff;
        document.getElementById('totalPatients').textContent = totalPatients;
        document.getElementById('registeredByCurrent').textContent =
            registeredByCurrent;
    }

    // Fetch and populate tables
    function loadTables() {
        let staffLoaded = false;
        let patientsLoaded = false;

        // Fetch Staff
        fetch('../../app/controller/AdminController.php?action=getStaff')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch staff');
                }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data)) {
                    throw new Error('Invalid staff response');
                }

                staffDataCache = data;
                populateStaffTable(data);

                staffLoaded = true;

                if (patientsLoaded) {
                    updateCounters();
                }
            })
            .catch(error => {
                console.error('Error fetching staff:', error);

                showTableMessage(
                    staffTableBody,
                    10,
                    'Error loading staff.',
                    true
                );
            });

        // Fetch Patients
        fetch('../../app/controller/AdminController.php?action=getPatients')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch patients');
                }
                return response.json();
            })
            .then(data => {
                if (!Array.isArray(data)) {
                    throw new Error('Invalid patient response');
                }

                patientDataCache = data;
                populatePatientTable(data);

                patientsLoaded = true;

                if (staffLoaded) {
                    updateCounters();
                }
            })
            .catch(error => {
                console.error('Error fetching patients:', error);

                showTableMessage(
                    patientTableBody,
                    9,
                    'Error loading patients.',
                    true
                );
            });
    }

    // Search Staff
    if (staffSearch) {
        staffSearch.addEventListener('input', () => {
            const query = staffSearch.value.toLowerCase();

            const filteredData = staffDataCache.filter(staff =>
                String(staff.staff_id ?? '').toLowerCase().includes(query) ||
                String(staff.staff_name ?? '').toLowerCase().includes(query)
            );

            populateStaffTable(filteredData);
        });
    }

    // Search Patients
    if (patientSearch) {
        patientSearch.addEventListener('input', () => {
            const query = patientSearch.value.toLowerCase();

            const filteredData = patientDataCache.filter(patient =>
                String(patient.patient_id ?? '').toLowerCase().includes(query) ||
                String(patient.patient_name ?? '').toLowerCase().includes(query)
            );

            populatePatientTable(filteredData);
        });
    }

    // Handle Edit button clicks
    document.addEventListener('click', (e) => {
        const button = e.target.closest('.edit-btn');
        if (!button) return;

        const id = button.dataset.id;
        const modal = button.getAttribute('data-bs-target');

        // Edit Staff
        if (modal === '#editStaffModal') {
            fetch(
                '../../app/controller/AdminController.php?action=getStaffById&id=' +
                encodeURIComponent(id)
            )
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch staff');
                    }
                    return response.json();
                })
                .then(staff => {
                    if (staff.error) {
                        throw new Error('Failed to load staff');
                    }

                    document.getElementById('edit_staff_id').value =
                        staff.staff_id;

                    document.getElementById('edit_staff_name').value =
                        staff.staff_name;

                    document.getElementById('edit_staff_dob').value =
                        staff.staff_dob;

                    document.getElementById('edit_staff_specialization').value =
                        staff.staff_specialization;

                    document.getElementById('edit_staff_role').value =
                        staff.staff_role;

                    document.getElementById('edit_staff_phone').value =
                        staff.staff_phone;

                    document.getElementById('edit_staff_email').value =
                        staff.staff_email;

                    document.getElementById('edit_staff_registered_by').value =
                        staff.registered_by;

                    document.getElementById('edit_staff_status').value =
                        staff.staff_status;

                    clearFormErrors(staffFormErrors);
                })
                .catch(error => {
                    console.error('Error loading staff:', error);

                    showFormErrors(
                        staffFormErrors,
                        'Error loading staff data. Please try again.'
                    );
                });
        }

        // Edit Patient
        else if (modal === '#editPatientModal') {
            fetch(
                '../../app/controller/AdminController.php?action=getPatientById&id=' +
                encodeURIComponent(id)
            )
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch patient');
                    }
                    return response.json();
                })
                .then(patient => {
                    if (patient.error) {
                        throw new Error('Failed to load patient');
                    }

                    document.getElementById('edit_patient_id').value =
                        patient.patient_id;

                    document.getElementById('edit_patient_name').value =
                        patient.patient_name;

                    document.getElementById('edit_patient_dob').value =
                        patient.patient_date_of_birth;

                    document.getElementById('edit_patient_phone').value =
                        patient.patient_phone;

                    document.getElementById('edit_patient_gender').value =
                        patient.patient_gender;

                    document.getElementById('edit_patient_email').value =
                        patient.patient_email;

                    document.getElementById('edit_patient_address').value =
                        patient.patient_address;

                    document.getElementById('edit_patient_registered_by').value =
                        patient.registered_by;

                    clearFormErrors(patientFormErrors);
                })
                .catch(error => {
                    console.error('Error loading patient:', error);

                    showFormErrors(
                        patientFormErrors,
                        'Error loading patient data. Please try again.'
                    );
                });
        }
    });

    // Validate and submit Staff form
    if (editStaffForm) {
        editStaffForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const errors = [];

            const staffName =
                document.getElementById('edit_staff_name').value;

            const staffDob =
                document.getElementById('edit_staff_dob').value;

            const staffSpecialization =
                document.getElementById('edit_staff_specialization').value;

            const staffRole =
                document.getElementById('edit_staff_role').value;

            const staffPhone =
                document.getElementById('edit_staff_phone').value;

            const staffEmail =
                document.getElementById('edit_staff_email').value;

            const staffStatus =
                document.getElementById('edit_staff_status').value;

            if (!namePattern.test(staffName)) {
                errors.push(
                    'Name must be 1-100 characters and contain only letters and spaces.'
                );
            }

            if (!dobPattern.test(staffDob)) {
                errors.push('Invalid date of birth format (YYYY-MM-DD).');
            }

            if (!specializationPattern.test(staffSpecialization)) {
                errors.push(
                    'Specialization must be 1-100 characters and contain only letters and spaces.'
                );
            }

            if (!['doctor', 'pharmacist'].includes(staffRole)) {
                errors.push('Please select a valid role.');
            }

            if (!phonePattern.test(staffPhone)) {
                errors.push('Phone must be 10-15 digits.');
            }

            if (!emailPattern.test(staffEmail)) {
                errors.push('Invalid email format.');
            }

            if (!['active', 'inactive', 'block'].includes(staffStatus)) {
                errors.push('Please select a valid status.');
            }

            if (errors.length > 0) {
                showFormErrors(staffFormErrors, errors);
                return;
            }

            clearFormErrors(staffFormErrors);

            // Update Staff
            fetch('../../app/controller/AdminController.php?action=updateStaff', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    staff_id: document.getElementById('edit_staff_id').value,
                    staff_name: staffName,
                    staff_dob: staffDob,
                    staff_specialization: staffSpecialization,
                    staff_role: staffRole,
                    staff_phone: staffPhone,
                    staff_email: staffEmail,
                    staff_status: staffStatus
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to update staff');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error || data.success === false) {
                        throw new Error('Failed to update staff');
                    }

                    alert('Staff updated successfully.');

                    bootstrap.Modal.getInstance(
                        document.getElementById('editStaffModal')
                    ).hide();

                    loadTables();
                })
                .catch(error => {
                    console.error('Error updating staff:', error);

                    showFormErrors(
                        staffFormErrors,
                        'Error updating staff. Please try again.'
                    );
                });
        });
    }

    // Validate and submit Patient form
    if (editPatientForm) {
        editPatientForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const errors = [];

            const patientName =
                document.getElementById('edit_patient_name').value;

            const patientDob =
                document.getElementById('edit_patient_dob').value;

            const patientPhone =
                document.getElementById('edit_patient_phone').value;

            const patientGender =
                document.getElementById('edit_patient_gender').value;

            const patientEmail =
                document.getElementById('edit_patient_email').value;

            const patientAddress =
                document.getElementById('edit_patient_address').value;

            if (!namePattern.test(patientName)) {
                errors.push(
                    'Name must be 1-100 characters and contain only letters and spaces.'
                );
            }

            if (!dobPattern.test(patientDob)) {
                errors.push('Invalid date of birth format (YYYY-MM-DD).');
            }

            if (!phonePattern.test(patientPhone)) {
                errors.push('Phone must be 10-15 digits.');
            }

            if (!['Male', 'Female', 'Other'].includes(patientGender)) {
                errors.push('Please select a valid gender.');
            }

            if (!emailPattern.test(patientEmail)) {
                errors.push('Invalid email format.');
            }

            if (!addressPattern.test(patientAddress)) {
                errors.push('Address must be 1-255 characters.');
            }

            if (errors.length > 0) {
                showFormErrors(patientFormErrors, errors);
                return;
            }

            clearFormErrors(patientFormErrors);

            // Update Patient
            fetch('../../app/controller/AdminController.php?action=updatePatient', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    patient_id: document.getElementById('edit_patient_id').value,
                    patient_name: patientName,
                    patient_date_of_birth: patientDob,
                    patient_phone: patientPhone,
                    patient_gender: patientGender,
                    patient_email: patientEmail,
                    patient_address: patientAddress
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to update patient');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error || data.success === false) {
                        throw new Error('Failed to update patient');
                    }

                    alert('Patient updated successfully.');

                    bootstrap.Modal.getInstance(
                        document.getElementById('editPatientModal')
                    ).hide();

                    loadTables();
                })
                .catch(error => {
                    console.error('Error updating patient:', error);

                    showFormErrors(
                        patientFormErrors,
                        'Error updating patient. Please try again.'
                    );
                });
        });
    }

    // Initial load
    loadTables();
});
