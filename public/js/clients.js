let allclients = [];

document.addEventListener("DOMContentLoaded", () => {
    loadclients();
    //Monthly_load();
});


async function sessionFetch(url, options = {}) {
    const response = await fetch(url, options);
    if (response.status === 401) {
        alert("Your session has expired. Please log in again.");
        window.location.href = "/login"; // Symfony login route
        throw new Error("Unauthenticated");
    }
    return response;
}


async function loadclients() {
    const tbody = document.querySelector("#client-table-body");
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">Loading…</td></tr>`;

    try {
        const response = await sessionFetch(ROUTES.list);
        const result   = await response.json();

        if (!response.ok) throw new Error("Network response was not ok");

        if (result.success) {
            allclients = result.data;

            const totalBadge = document.querySelector("#total-clients-count");
            if (totalBadge) totalBadge.textContent = allclients.length;

            renderClientRows(allclients);
        }
    } catch (error) {
        console.error("Fetch error:", error);
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Failed to load clients.</td></tr>`;
    }
}


function renderClientRows(data) {
    const tbody = document.querySelector("#client-table-body");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (data.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No clients found matching that search.</td></tr>`;
        return;
    }

    data.forEach(client => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${client.client_name}</td>
            <td>${client.email}</td>
            <td id="churn-container-${client.id}">
                <div class="spinner-border spinner-border-sm text-muted" role="status"></div>
            </td>
            <td>${client.phone || 'N/A'}</td>
            <td></td>
            <td class="text-end">
                <div class="d-inline-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="detailClient(${client.id})">Details</button>
                    <button class="btn btn-sm btn-outline-danger"    onclick="deleteClient(${client.id})">🗑️</button>
                    <button class="btn btn-sm btn-outline-warning"   onclick="editClient(${client.id})">Edit</button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}



async function Monthly_load() {
    const monthly = document.querySelector("#stat-month-amount");
    try {
        const res      = await sessionFetch(ROUTES.salesStats);
        const response = await res.json();
        if (response.success && monthly) {
            monthly.textContent = response.data.this_month.total;
        }
    } catch (error) {
        console.error("Network or Parsing error:", error);
        if (monthly) monthly.textContent = "$0";
    }
}


function handleSearch() {
    const searchInput = document.querySelector('input[placeholder*="Search"]');
    if (!searchInput) return;

    const query = searchInput.value.toLowerCase().trim();
    const filteredResults = allclients.filter(client => {
        const name  = (client.client_name || "").toLowerCase();
        const email = (client.email || "").toLowerCase();
        const phone = (client.phone || "").toLowerCase();
        return name.includes(query) || email.includes(query) || phone.includes(query);
    });

    renderClientRows(filteredResults);
}

function ExportToCSV() {
    if (!allclients || allclients.length === 0) { alert("No clients to export!"); return; }

    const headers = ["Name", "Email", "Churn Risk", "Phone"];
    const rows    = allclients.map(c => [
        `"${c.client_name || ''}"`,
        `"${c.email || ''}"`,
        `"${c.riskPercent || 'Pending'}"`,
        `"${c.phone || 'N/A'}"`
    ]);

    const csv  = [headers, ...rows].map(r => r.join(",")).join("\n");
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href  = URL.createObjectURL(blob);
    link.download = `clients_${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
}

function ExportToPDF() {
    if (!allclients || allclients.length === 0) { alert("No clients to export!"); return; }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text("Client Portfolio", 14, 15);

    doc.autoTable({
        head: [["Name", "Email", "Churn Risk", "Phone"]],
        body: allclients.map(c => [
            c.client_name || '',
            c.email || '',
            c.riskPercent || 'Pending',
            c.phone || 'N/A'
        ]),
        startY: 20,
    });

    doc.save(`clients_${new Date().toISOString().slice(0, 10)}.pdf`);
}

async function Copy() {
    if (!allclients || allclients.length === 0) { alert("No clients to copy!"); return; }

    const headers = ["Name", "Email", "Phone"];
    const rows    = allclients.map(c => [
        c.client_name || '',
        c.email || '',
        c.riskPercent || 'Pending',
        c.phone || 'N/A'
    ].join("\t"));

    const content = [headers.join("\t"), ...rows].join("\n");
    try {
        await navigator.clipboard.writeText(content);
        alert("Client data copied to clipboard!");
    } catch (err) {
        alert("Failed to copy data. Please try again.");
    }
}

function ExportToExcel() {
    if (!allclients || allclients.length === 0) { alert("No data to export."); return; }

    const excelData = allclients.map(c => ({
        "Client Name":    c.client_name || '',
        "Email Address":  c.email || '',
        "Phone Number":   c.phone || 'N/A'
    }));

    const ws = XLSX.utils.json_to_sheet(excelData);
    const wb = XLSX.utils.book_new();
    ws['!cols'] = [{ wch: 30 }, { wch: 30 }, { wch: 15 }, { wch: 20 }];
    XLSX.utils.book_append_sheet(wb, ws, "Clients");
    XLSX.writeFile(wb, `Client_Portfolio_${new Date().toISOString().slice(0, 10)}.xlsx`);
}

async function deleteClient(id) {
    if (!confirm("Are you sure you want to delete this client?")) return;

    const clientToDelete = allclients.find(c => c.id == id);
    const clientName     = clientToDelete ? clientToDelete.client_name : "Client";

    try {
        const response = await sessionFetch(`${ROUTES.delete}${id}`, { method: 'POST' });
        const result   = await response.json();

        if (response.ok && result.success) {
            allclients = allclients.filter(c => c.id != id);
            renderClientRows(allclients);

            const toastEl   = document.getElementById('deleteToast');
            toastEl.querySelector('.toast-body').innerHTML = `<strong>${clientName}</strong> deleted successfully!`;
            new bootstrap.Toast(toastEl).show();

            const totalBadge = document.querySelector("#total-clients-count");
            if (totalBadge) totalBadge.textContent = allclients.length;
        } else {
            alert("Error: " + (result.error || "Could not delete client"));
        }
    } catch (error) {
        console.error("Delete failed:", error);
    }
}

function editClient(id) {
    const client = allclients.find(c => c.id == id);
    if (!client) return;

    document.querySelector("#edit-id-input").value    = client.id;
    document.querySelector("#edit-name-input").value  = client.client_name || '';
    document.querySelector("#edit-email-input").value = client.email || '';
    document.querySelector("#edit-phone-input").value = client.phone || '';

    new bootstrap.Modal(document.getElementById('editClientModal')).show();
}

async function saveClientEdit() {
    const id = document.querySelector("#edit-id-input").value;

    const updateData = {
        client_name: document.querySelector("#edit-name-input").value,
        email:       document.querySelector("#edit-email-input").value,
        phone:       document.querySelector("#edit-phone-input").value,
        address:     "",
        client_type: "B2C",
        status:      "Active"
    };

    try {
        const response = await sessionFetch(`${ROUTES.update}${id}`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(updateData)
        });
        const result = await response.json();

        if (result.success) {
            const idx = allclients.findIndex(c => c.id == id);
            if (idx !== -1) allclients[idx] = { ...allclients[idx], ...updateData };

            renderClientRows(allclients);
            bootstrap.Modal.getInstance(document.getElementById('editClientModal')).hide();

            const toastEl = document.getElementById('deleteToast');
            toastEl.querySelector('.toast-body').innerHTML = `✅ <strong>${updateData.client_name}</strong> updated!`;
            new bootstrap.Toast(toastEl).show();
        } else {
            alert("Error: " + (result.error || "Update failed"));
        }
    } catch (error) {
        console.error("Save Error:", error);
        alert("Failed to reach server.");
    }
}

function detailClient(id) {
    const client = allclients.find(c => c.id == id);
    if (!client) return;

    document.getElementById('det-name').textContent    = client.client_name;
    document.getElementById('det-email').textContent   = client.email || 'N/A';
    document.getElementById('det-phone').textContent   = client.phone || 'N/A';
    document.getElementById('det-type').textContent    = client.client_type || 'B2C';
    document.getElementById('det-address').textContent = client.address || 'No address on file.';
    document.getElementById('det-risk').textContent    = client.riskPercent || 'Pending…';
    document.getElementById('det-initials').textContent =
        client.client_name.split(' ').map(n => n[0]).join('').toUpperCase();

    new bootstrap.Modal(document.getElementById('detailClientModal')).show();
}

function openAddModal() {
    document.getElementById("add-name-input").value  = '';
    document.getElementById("add-email-input").value = '';
    document.getElementById("add-phone-input").value = '';
    new bootstrap.Modal(document.getElementById('addClientModal')).show();
}

async function saveNewClient() {
    const name  = document.querySelector("#add-name-input").value.trim();
    const email = document.querySelector("#add-email-input").value.trim();
    const phone = document.querySelector("#add-phone-input").value.trim();

    if (!name || !email) { alert("Name and Email are required!"); return; }

    const newClientData = {
        client_name: name,
        email,
        phone,
        address:     "",
        client_type: "B2C",
        status:      "Active"
    };

    try {
        const response = await sessionFetch(ROUTES.create, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(newClientData)
        });
        const result = await response.json();

        if (result.success) {
            await loadclients();
            bootstrap.Modal.getInstance(document.getElementById('addClientModal')).hide();

            const toastEl = document.getElementById('deleteToast');
            toastEl.querySelector('.toast-body').innerHTML = `✅ <strong>${name}</strong> added successfully!`;
            new bootstrap.Toast(toastEl).show();
        } else {
            alert("Error: " + (result.error || "Failed to add client"));
        }
    } catch (error) {
        console.error("Save Error:", error);
        alert("Failed to reach server.");
    }
}