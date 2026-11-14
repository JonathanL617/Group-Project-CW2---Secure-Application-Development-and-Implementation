// Global variables
let editMode = false;
let currentMedicineId = '';

// Load medicines on page load
document.addEventListener('DOMContentLoaded', () => {
    loadMedicines();

    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            loadMedicines(e.target.value);
        });
    }
});

// XSS Prevention - Display data as plain text
function createMedicineCell(row, value) {
    const cell = document.createElement('td');
    cell.textContent = value ?? '';
    row.appendChild(cell);
    return cell;
}

// Display a safe table message
function showMedicineMessage(tbody, message, isError = false) {
    const row = document.createElement('tr');
    const cell = document.createElement('td');

    cell.colSpan = 6;
    cell.className = isError
        ? 'text-center text-danger'
        : 'text-center';

    cell.textContent = message;
    row.appendChild(cell);
    tbody.replaceChildren(row);
}

// Load medicines
function loadMedicines(search = '') {
    fetch('../../app/controller/PharmacistController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'list_medicines',
            search: search
        })
    })
    .then(r => r.json())
    .then(d => {
        const tbody = document.querySelector('tbody');
        if (!tbody) return;

        // Clear the previous table safely
        tbody.replaceChildren();

        if (!d.success || !d.medicines || d.medicines.length === 0) {
            showMedicineMessage(tbody, 'No medicines found');
            updateMedicinesDashboardStats([]);
            return;
        }

        // Display medicines safely
        d.medicines.forEach(m => {
            const row = document.createElement('tr');

            // Use textContent instead of innerHTML
            createMedicineCell(row, m.medicine_id);
            createMedicineCell(row, m.medicine_name);
            createMedicineCell(
                row,
                parseFloat(m.medicine_price).toFixed(2)
            );
            createMedicineCell(row, m.medicine_quantity);
            createMedicineCell(
                row,
                m.medicine_description || '-'
            );

            // Create action buttons safely
            const actionCell = document.createElement('td');

            // Edit button
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'btn btn-sm btn-warning me-1';

            const editIcon = document.createElement('i');
            editIcon.className = 'bi bi-pencil';
            editButton.appendChild(editIcon);

            // Use addEventListener instead of inline onclick
            editButton.addEventListener('click', () => {
                openEditModal(
                    m.medicine_id,
                    m.medicine_name,
                    m.medicine_price,
                    m.medicine_quantity,
                    m.medicine_description || ''
                );
            });

            // Delete button
            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'btn btn-sm btn-danger';

            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'bi bi-trash';
            deleteButton.appendChild(deleteIcon);

            deleteButton.addEventListener('click', () => {
                deleteMedicine(m.medicine_id);
            });

            actionCell.appendChild(editButton);
            actionCell.appendChild(deleteButton);
            row.appendChild(actionCell);

            tbody.appendChild(row);
        });

        updateMedicinesDashboardStats(d.medicines);
    })
    .catch(error => {
        console.error('Error loading medicines:', error);

        const tbody = document.querySelector('tbody');

        if (tbody) {
            showMedicineMessage(
                tbody,
                'Error loading data',
                true
            );
        }

        updateMedicinesDashboardStats([]);
    });
}

// Update medicines dashboard statistics
function updateMedicinesDashboardStats(medicines) {
    if (!medicines || medicines.length === 0) {
        document.getElementById('totalMedicines').textContent = '0';
        document.getElementById('lowStockItems').textContent = '0';
        document.getElementById('outOfStock').textContent = '0';
        document.getElementById('totalValue').textContent = '$0';
        return;
    }

    const totalMedicines = medicines.length;

    const lowStockItems = medicines.filter(m => {
        const qty = parseInt(m.medicine_quantity);
        return qty > 0 && qty < 10;
    }).length;

    const outOfStock = medicines.filter(m =>
        parseInt(m.medicine_quantity) === 0
    ).length;

    const totalValue = medicines.reduce((sum, m) => {
        return sum + (
            parseFloat(m.medicine_price) *
            parseInt(m.medicine_quantity)
        );
    }, 0);

    document.getElementById('totalMedicines').textContent = totalMedicines;
    document.getElementById('lowStockItems').textContent = lowStockItems;
    document.getElementById('outOfStock').textContent = outOfStock;
    document.getElementById('totalValue').textContent =
        '$' + totalValue.toFixed(2);
}

// Add Medicine Modal
function openAddModal() {
    editMode = false;
    currentMedicineId = '';

    document.getElementById('medicineForm').reset();
    document.getElementById('medicineModalLabel').textContent =
        'Add New Medicine';

    new bootstrap.Modal(
        document.getElementById('medicineModal')
    ).show();
}

// Edit Medicine Modal
function openEditModal(id, name, price, qty, desc) {
    editMode = true;
    currentMedicineId = id;

    document.getElementById('medicine_name').value = name;
    document.getElementById('medicine_price').value = price;
    document.getElementById('medicine_quantity').value = qty;
    document.getElementById('medicine_description').value = desc;

    document.getElementById('medicineModalLabel').textContent =
        'Edit Medicine';

    new bootstrap.Modal(
        document.getElementById('medicineModal')
    ).show();
}

// Save Medicine
function saveMedicine() {
    const form = document.getElementById('medicineForm');

    if (!validateForm(form)) return;

    const data = {
        action: editMode ? 'update_medicine' : 'add_medicine',
        id: currentMedicineId,
        name: form.medicine_name.value,
        price: form.medicine_price.value,
        quantity: form.medicine_quantity.value,
        description: form.medicine_description.value
    };

    fetch('../../app/controller/PharmacistController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(d.msg);

            bootstrap.Modal.getInstance(
                document.getElementById('medicineModal')
            ).hide();

            loadMedicines();
        } else {
            alert('Error: ' + d.msg);
        }
    })
    .catch(error => {
        console.error('Error saving medicine:', error);
    });
}

// Delete Medicine
function deleteMedicine(id) {
    if (!confirm('Are you sure you want to delete this medicine?')) {
        return;
    }

    fetch('../../app/controller/PharmacistController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'delete_medicine',
            id: id
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert(d.msg);
            loadMedicines();
        } else {
            alert('Error: ' + d.msg);
        }
    })
    .catch(error => {
        console.error('Error deleting medicine:', error);
    });
}

// Frontend Form Validation
function validateForm(form) {
    const name = form.medicine_name.value.trim();
    const price = parseFloat(form.medicine_price.value);
    const quantity = parseInt(form.medicine_quantity.value);

    if (name === '') {
        alert('Name cannot be empty');
        return false;
    }

    if (isNaN(price) || price <= 0) {
        alert('Price must be a positive number');
        return false;
    }

    if (isNaN(quantity) || quantity < 0) {
        alert('Quantity cannot be negative');
        return false;
    }

    return true;
}
