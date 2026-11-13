
document.addEventListener('DOMContentLoaded', () => {
    const patientsTableBody = document.getElementById('patientsTable');
    const patientForm = document.getElementById('patientRegisterForm');
    const patientIdInput = document.getElementById('patient_id');
    const formErrors = document.getElementById('formErrors');

    // XSS Prevention - Safely display patient data
    function createPatientCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.appendChild(cell);
        return cell;
    }

    // Safely display table messages
    function showTableMessage(message, isError = false) {
        if (!patientsTableBody) return;

        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 8;
        cell.className = isError
            ? 'text-center text-danger'
            : 'text-center text-muted';
        cell.textContent = message;

        row.appendChild(cell);
        patientsTableBody.replaceChildren(row);
    }

    // Safely display form errors
    function showFormErrors(messages) {
        const errorText = Array.isArray(messages)
            ? messages.join('\n')
            : String(messages);

        if (formErrors) {
            formErrors.style.display = 'block';
            formErrors.style.whiteSpace = 'pre-line';
            formErrors.textContent = errorText;
        } else {
            alert(errorText);
        }
    }

    // Fetch and display all patients
    function loadPatients() {
        if (!patientsTableBody) return;

        fetch('../../app/controller/AdminController.php?action=getPatients')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to load patients');
                }
                return res.json();
            })
            .then(data => {
                if (!Array.isArray(data)) {
                    throw new Error('Invalid patient data');
                }

                // Clear table safely
                patientsTableBody.replaceChildren();

                // Update total patients if the counter exists
                const totalPatients =
                    document.getElementById('totalPatients');

                if (totalPatients) {
                    totalPatients.textContent = data.length;
                }

                if (data.length === 0) {
                    showTableMessage('No patients found.');
                    return;
                }

                data.forEach(patient => {
                    const row = document.createElement('tr');

                    // XSS Prevention - No innerHTML
                    createPatientCell(row, patient.patient_id);
                    createPatientCell(row, patient.patient_name);
                    createPatientCell(row, patient.patient_date_of_birth);
                    createPatientCell(row, patient.patient_phone);
                    createPatientCell(row, patient.patient_gender);
                    createPatientCell(row, patient.patient_email);
                    createPatientCell(row, patient.patient_address);
                    createPatientCell(row, patient.registered_by);

                    patientsTableBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error('Load patients error:', err);
                showTableMessage('Error loading patients.', true);
            });
    }

    // Generate new patient ID
    function generatePatientId() {
        if (!patientIdInput) return;

        fetch('../../app/controller/AdminController.php?action=generatePatientId')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Failed to generate patient ID');
                }
                return res.json();
            })
            .then(data => {
                patientIdInput.value = data.id || 'Error';
            })
            .catch(err => {
                console.error('Generate patient ID error:', err);
                patientIdInput.value = 'Error generating ID';
            });
    }

    // Initial load
    loadPatients();
    generatePatientId();

    // Form submission
    if (patientForm) {
        patientForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const formData = {
                patient_id: patientIdInput.value,
                patient_name: document.getElementById('patient_name').value.trim(),
                patient_date_of_birth: document.getElementById('patient_date_of_birth').value,
                patient_phone: document.getElementById('patient_phone').value.trim(),
                patient_gender: document.getElementById('patient_gender').value,
                patient_email: document.getElementById('patient_email').value.trim(),
                patient_address: document.getElementById('patient_address').value.trim(),
                registered_by: document.getElementById('registered_by').value
            };

            const errors = [];

            // Patient ID validation
            if (!formData.patient_id ||
                formData.patient_id.includes('Error')) {
                errors.push('Invalid Patient ID.');
            }

            // Name validation
            if (!formData.patient_name ||
                formData.patient_name.length > 100) {
                errors.push(
                    'Name is required and must be ≤ 100 characters.'
                );
            }

            // Date of birth validation
            if (!formData.patient_date_of_birth) {
                errors.push('Date of Birth is required.');
            } else {
                const dob = new Date(formData.patient_date_of_birth);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (Number.isNaN(dob.getTime()) || dob > today) {
                    errors.push('Please enter a valid Date of Birth.');
                }
            }

            // Phone validation
            const cleanedPhone = formData.patient_phone.replace(/[\s-]/g, '');

            if (!/^\+?\d{7,15}$/.test(cleanedPhone)) {
                errors.push(
                    'Valid phone number required (7-15 digits).'
                );
            }

            // Gender validation
            if (!['Male', 'Female', 'Other'].includes(
                formData.patient_gender
            )) {
                errors.push('Please select a valid gender.');
            }

            // Email validation - Fixed Regex
            if (!formData.patient_email ||
                !/^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/.test(
                    formData.patient_email
                )) {
                errors.push('Valid email required.');
            }

            // Address validation
            if (!formData.patient_address ||
                formData.patient_address.length > 255) {
                errors.push(
                    'Address is required and must be ≤ 255 characters.'
                );
            }

            // Registered by validation
            if (!formData.registered_by ||
                formData.registered_by.trim() === '') {
                errors.push(
                    'Registered by field is required. Please refresh the page.'
                );
            }

            // Display errors safely
            if (errors.length > 0) {
                showFormErrors(errors);
                return;
            }

            if (formErrors) {
                formErrors.style.display = 'none';
                formErrors.textContent = '';
            }

            // Register patient
            fetch('../../app/controller/AdminController.php?action=registerPatient', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Registration request failed');
                    }
                    return res.json();
                })
                .then(result => {
                    if (result.success) {
                        alert(
                            `Patient ${formData.patient_id} registered successfully!`
                        );

                        // Refresh patient list
                        loadPatients();

                        // Reset form
                        patientForm.reset();
                        generatePatientId();

                        // Restore admin ID from session
                        if (typeof loggedInUserId !== 'undefined') {
                            document.getElementById('registered_by').value =
                                loggedInUserId;
                        }

                        // Close modal
                        const modal = bootstrap.Modal.getInstance(
                            document.getElementById('registerPatientModal')
                        );

                        if (modal) {
                            modal.hide();
                        }
                    } else {
                        throw new Error('Registration failed');
                    }
                })
                .catch(err => {
                    console.error('Register patient error:', err);

                    // Do not expose detailed server errors
                    showFormErrors(
                        'Registration failed. Please try again.'
                    );
                });
        });
    }
});
