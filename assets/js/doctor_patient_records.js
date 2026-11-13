
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('patientsTableBody');
    const searchInput = document.getElementById('searchPatient');
    const clearBtn = document.getElementById('clearSearch');

    const patientsCard = document.getElementById('patientsCard');
    const orderHistorySection = document.getElementById('orderHistorySection');
    const backBtn = document.getElementById('backToPatients');

    // XSS Prevention - Create safe text cells
    function createPatientCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value ?? '';
        row.appendChild(cell);
        return cell;
    }

    // Display safe messages
    function showTableMessage(message) {
        const row = document.createElement('tr');
        const cell = document.createElement('td');

        cell.colSpan = 6;
        cell.className = 'text-center';
        cell.textContent = message;

        row.appendChild(cell);
        tableBody.replaceChildren(row);
    }

    // Load dashboard statistics
    function loadDashboardStats() {
        console.log('Loading dashboard stats...');

        fetch('../../app/controller/DoctorController.php?action=getDashboardStats')
            .then(res => res.json())
            .then(data => {
                console.log('API Response:', data);

                if (data.success) {
                    document.getElementById('totalPatients').textContent =
                        data.stats.total_patients || '0';

                    document.getElementById('recentConsultations').textContent =
                        data.stats.recent_consultations || '0';

                    document.getElementById('pendingPrescriptions').textContent =
                        data.stats.pending_prescriptions || '0';

                    document.getElementById('todayAppointments').textContent =
                        data.stats.todays_appointments || '0';
                }
            })
            .catch(err => {
                console.error('Error loading dashboard stats:', err);
            });
    }

    console.log('DOM loaded, now loading stats...');
    loadDashboardStats();

    // Load patients - XSS Prevention
    function loadPatients(search = '') {
        const url = '../../app/controller/DoctorController.php?action=getPatients&search='
            + encodeURIComponent(search);

        fetch(url)
            .then(res => res.json())
            .then(data => {
                tableBody.replaceChildren();

                if (!data.success ||
                    !Array.isArray(data.patients) ||
                    data.patients.length === 0) {

                    showTableMessage('No patients found');
                    return;
                }

                data.patients.forEach(p => {
                    const row = document.createElement('tr');

                    // Use textContent instead of innerHTML
                    createPatientCell(row, p.patient_id);
                    createPatientCell(row, p.patient_name);
                    createPatientCell(row, p.patient_date_of_birth);
                    createPatientCell(row, p.patient_phone);
                    createPatientCell(row, p.patient_gender);

                    // Create View History button safely
                    const actionCell = document.createElement('td');
                    const viewButton = document.createElement('button');

                    viewButton.type = 'button';
                    viewButton.className =
                        'btn btn-sm btn-primary view-btn';

                    viewButton.textContent = 'View History';

                    // Use addEventListener instead of HTML data injection
                    viewButton.addEventListener('click', () => {
                        showHistory({
                            id: p.patient_id,
                            name: p.patient_name,
                            dob: p.patient_date_of_birth,
                            phone: p.patient_phone,
                            gender: p.patient_gender,
                            email: p.patient_email,
                            address: p.patient_address
                        });
                    });

                    actionCell.appendChild(viewButton);
                    row.appendChild(actionCell);
                    tableBody.appendChild(row);
                });
            })
            .catch(err => {
                console.error('Error loading patients:', err);
                showTableMessage('Error loading patients');
            });
    }

    // Show Patient History
    function showHistory(data) {
        patientsCard.style.display = 'none';
        orderHistorySection.style.display = 'block';

        // Display patient details safely
        document.getElementById('bannerPatientName').textContent =
            data.name ?? '';

        document.getElementById('bannerPatientId').textContent =
            'Patient ID: ' + (data.id ?? '');

        document.getElementById('detailDob').textContent =
            data.dob ?? '';

        document.getElementById('detailPhone').textContent =
            data.phone ?? '';

        document.getElementById('detailGender').textContent =
            data.gender ?? '';

        document.getElementById('detailEmail').textContent =
            data.email ?? '';

        document.getElementById('detailAddress').textContent =
            data.address ?? '';

        const container = document.getElementById('ordersContainer');

        // Loading message without innerHTML
        const loadingMessage = document.createElement('p');
        loadingMessage.className = 'text-center';
        loadingMessage.textContent = 'Loading history...';

        container.replaceChildren(loadingMessage);

        const url = '../../app/controller/DoctorController.php?action=getPatientHistory&id='
            + encodeURIComponent(data.id ?? '');

        fetch(url)
            .then(res => res.json())
            .then(resData => {
                container.replaceChildren();

                if (!resData.success ||
                    !Array.isArray(resData.history) ||
                    resData.history.length === 0) {

                    const message = document.createElement('p');
                    message.className = 'text-muted';
                    message.textContent = 'No order history found.';

                    container.appendChild(message);
                    return;
                }

                // Display order history safely
                resData.history.forEach(order => {
                    const div = document.createElement('div');
                    div.className = 'border-bottom pb-2 mb-2';

                    const header = document.createElement('div');
                    header.className = 'd-flex justify-content-between';

                    // Order ID as plain text
                    const orderTitle = document.createElement('strong');
                    orderTitle.textContent =
                        'Order #' + (order.order_id ?? '');

                    // Status badge with safe text
                    const statusBadge = document.createElement('span');
                    statusBadge.className =
                        'badge bg-' + getStatusColor(order.status_id);

                    statusBadge.textContent = order.status_id ?? '';

                    header.appendChild(orderTitle);
                    header.appendChild(statusBadge);

                    // Order date as plain text
                    const orderDate = document.createElement('small');
                    orderDate.className = 'text-muted';
                    orderDate.textContent = order.order_date ?? '';

                    // Medicine information as plain text
                    const medicines = document.createElement('p');
                    medicines.className = 'mb-0 mt-1';
                    medicines.textContent = order.medicines ?? '';

                    div.appendChild(header);
                    div.appendChild(orderDate);
                    div.appendChild(medicines);

                    container.appendChild(div);
                });
            })
            .catch(err => {
                console.error('Error loading patient history:', err);

                const message = document.createElement('p');
                message.className = 'text-muted';
                message.textContent = 'Error loading history';

                container.replaceChildren(message);
            });
    }

    // Return only predefined Bootstrap colors
    function getStatusColor(status) {
        const statusLower = String(status ?? '').toLowerCase();

        if (statusLower === 'pending') return 'warning';
        if (statusLower === 'approved') return 'success';
        if (statusLower === 'rejected') return 'danger';

        if (statusLower === 'done' ||
            statusLower === 'completed') {
            return 'info';
        }

        return 'secondary';
    }

    // Search patient
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            loadPatients(e.target.value);
        });
    }

    // Clear search
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            loadPatients();
        });
    }

    // Back to patients
    if (backBtn) {
        backBtn.addEventListener('click', () => {
            orderHistorySection.style.display = 'none';
            patientsCard.style.display = 'block';
        });
    }

    // Initial load
    loadPatients();
});
