let allTransactions = [];
let revenueChart    = null;

let filterType      = 'all';
let filterSearch    = '';
let filterStartDate = '';
let filterEndDate   = '';

let currentPage  = 1;
let totalPages   = 1;
let totalRecords = 0;
const PER_PAGE   = 20;
document.addEventListener('DOMContentLoaded', () => {
    // Set today as default sale date in modal
    const saleDateInput = document.getElementById('sale-date');
    if (saleDateInput) saleDateInput.value = new Date().toISOString().split('T')[0];

    loadStats();
    loadTransactions();
    initRevenueChart();
    bindFilters();
    bindModal();
    loadClientsForModal();
});
async function sessionFetch(url, options = {}) {
    const response = await fetch(url, options);
    if (response.status === 401) {
        alert('Your session has expired. Please log in again.');
        window.location.href = ROUTES.logout;
        throw new Error('Unauthenticated');
    }
    return response;
}

function route(name, id = null) {
    let url = ROUTES[name];
    if (id !== null) url = url.replace('__ID__', id);
    return url;
}

function buildUrl(base, params = {}) {
    const url = new URL(base, window.location.href);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) url.searchParams.set(k, v);
    });
    return url.toString();
}

const fmt = n =>
    '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const fmtDate = d =>
    d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) : '—';

function showToast(message, type = 'info') {
    const colors = { success: '#198754', error: '#dc3545', warning: '#e6a817', info: '#388087' };
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        background: ${colors[type] || colors.info};
        color: white; padding: 14px 20px; border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0,0,0,.18); z-index: 9999;
        animation: slideIn 0.3s ease; max-width: 320px; font-size: 0.9rem;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

async function loadStats() {
    try {
        const res = await sessionFetch(route('salesStats')).then(r => r.json());
        if (!res.success) return;

        const { this_month, total_clients } = res.data;

        const revenue = document.getElementById('stat-monthly-revenue');
        if (revenue) revenue.textContent = fmt(this_month?.total ?? 0);

        const clients = document.getElementById('stat-total-clients');
        if (clients) clients.textContent = total_clients ?? '—';

    } catch (e) {
        console.error('Stats error:', e);
    }
}

function updateStatusKPIs() {
    const total   = allTransactions.length;
    const closed  = allTransactions.filter(t => t._status === 'completed').length;
    const pending = allTransactions.filter(t => t._status === 'pending').length;

    const closedVal    = document.getElementById('stat-closed-deals');
    const closedTarget = document.getElementById('stat-closed-target');
    if (closedVal)    closedVal.textContent    = `${closed} / ${total}`;
    if (closedTarget) closedTarget.textContent = `Target: ${total}`;

    const pendingVal = document.getElementById('stat-pending-quotes');
    if (pendingVal) pendingVal.textContent = pending;
}

async function loadTransactions(page = 1) {
    currentPage = page;

    const tbody = document.getElementById('transaction-table-body');
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Loading…</td></tr>`;

    try {
        const params = {
            page,
            per_page:   PER_PAGE,
            search:     filterSearch,
            start_date: filterStartDate,
            end_date:   filterEndDate,
        };

        const url     = buildUrl(route('salesList'), params);
        const listRes = await sessionFetch(url).then(r => r.json());

        if (!listRes.success) throw new Error(listRes.error || 'Failed to load sales');

        totalPages   = listRes.pagination?.total_pages ?? 1;
        totalRecords = listRes.pagination?.total        ?? 0;

        allTransactions = [];

        const details = await Promise.all(
            listRes.data.map(sale =>
                sessionFetch(route('salesGet', sale.id)).then(r => r.json())
            )
        );

        for (const detail of details) {
            if (!detail.success) continue;
            const d      = detail.data;
            const status = d.payment_status === 'Paid' ? 'completed' : 'pending';

            if (d.product_items?.length > 0) {
                d.product_items.forEach(item => {
                    allTransactions.push({
                        _id:     `sale-${d.id}-${item.id}`,
                        _saleId: d.id,
                        _type:   'sale',
                        _status: status,
                        _amount: item.total_price,
                        date:    d.sale_date,
                        client:  d.client_name,
                        name:    item.product_name,
                    });
                });
            }

            if (d.service_items?.length > 0) {
                d.service_items.forEach(item => {
                    allTransactions.push({
                        _id:     `svc-${d.id}-${item.id}`,
                        _saleId: d.id,
                        _type:   'service',
                        _status: status,
                        _amount: item.total_price,
                        date:    d.sale_date,
                        client:  d.client_name,
                        name:    item.service_name,
                    });
                });
            }

            if (!d.product_items?.length && !d.service_items?.length) {
                allTransactions.push({
                    _id:     `sale-${d.id}`,
                    _saleId: d.id,
                    _type:   'sale',
                    _status: status,
                    _amount: d.total_amount,
                    date:    d.sale_date,
                    client:  d.client_name,
                    name:    '—',
                });
            }
        }

        renderTable();
        renderPagination();
        updateStatusKPIs();
        updateRevenueChart();

    } catch (e) {
        console.error('Transactions error:', e);
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load transactions.</td></tr>`;
    }
}

function renderTable() {
    const tbody = document.getElementById('transaction-table-body');
    tbody.innerHTML = '';

    let rows = filterType === 'all'
        ? allTransactions
        : allTransactions.filter(t => t._type === filterType);

    if (rows.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No transactions found.</td></tr>`;
        return;
    }

    rows.forEach(t => {
        const isCompleted = t._status === 'completed';
        const statusClass = isCompleted ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning';
        const statusLabel = isCompleted ? 'Completed' : 'Pending';
        const typeClass   = t._type === 'sale' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info';
        const typeLabel   = t._type === 'sale' ? 'Product Sale' : 'Service';

        const tr = document.createElement('tr');
        tr.dataset.id = t._id;
        tr.innerHTML = `
            <td>${fmtDate(t.date)}</td>
            <td class="fw-semibold">${t.client ?? '—'}</td>
            <td><span class="badge ${typeClass}">${typeLabel}</span></td>
            <td>${t.name ?? '—'}</td>
            <td class="fw-semibold">${fmt(t._amount)}</td>
            <td>
                <span class="badge ${statusClass} status-badge"
                      style="cursor:pointer;user-select:none"
                      data-id="${t._id}"
                      title="Click to mark as completed">
                    ${statusLabel}
                </span>
            </td>
            <td class="text-end">
                <div class="d-flex justify-content-end gap-2">
                    <button class="btn btn-sm btn-outline-secondary" onclick="generateInvoice(${t._saleId})" title="Download Invoice">
                        <i class="bi bi-file-earmark-text"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSale(${t._saleId})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </td>`;
        tbody.appendChild(tr);
    });

    document.querySelectorAll('.status-badge').forEach(badge =>
        badge.addEventListener('click', () => toggleStatus(badge.dataset.id))
    );
}

function renderPagination() {
    const info     = document.getElementById('pagination-info');
    const controls = document.getElementById('pagination-controls');
    if (!info || !controls) return;

    const from = totalRecords === 0 ? 0 : (currentPage - 1) * PER_PAGE + 1;
    const to   = Math.min(currentPage * PER_PAGE, totalRecords);
    info.textContent = `Showing ${from}–${to} of ${totalRecords} records`;

    controls.innerHTML = '';

    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
    prevLi.innerHTML = `<a class="page-link" href="#">&laquo;</a>`;
    prevLi.addEventListener('click', e => { e.preventDefault(); if (currentPage > 1) loadTransactions(currentPage - 1); });
    controls.appendChild(prevLi);

    const start = Math.max(1, currentPage - 2);
    const end   = Math.min(totalPages, start + 4);

    for (let p = start; p <= end; p++) {
        const li = document.createElement('li');
        li.className = `page-item ${p === currentPage ? 'active' : ''}`;
        li.innerHTML = `<a class="page-link" href="#">${p}</a>`;
        const pg = p;
        li.addEventListener('click', e => { e.preventDefault(); loadTransactions(pg); });
        controls.appendChild(li);
    }

    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
    nextLi.innerHTML = `<a class="page-link" href="#">&raquo;</a>`;
    nextLi.addEventListener('click', e => { e.preventDefault(); if (currentPage < totalPages) loadTransactions(currentPage + 1); });
    controls.appendChild(nextLi);
}

async function toggleStatus(id) {
    const tx = allTransactions.find(t => t._id === id);
    if (!tx) return;

    if (tx._status === 'completed') {
        showToast('This sale is already finalized.', 'info');
        return;
    }

    if (!confirm('Mark this sale as Paid / Completed? This cannot be undone.')) return;

    try {
        const res = await sessionFetch(route('salesUpdate', tx._saleId), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ payment_status: 'Paid' }),
        }).then(r => r.json());

        if (res.success) {
            allTransactions
                .filter(t => t._saleId === tx._saleId)
                .forEach(t => (t._status = 'completed'));

            renderTable();
            updateStatusKPIs();
            updateRevenueChart();
            loadStats();
            showToast('Sale marked as completed.', 'success');
        } else {
            showToast(res.error || 'Update failed.', 'error');
        }
    } catch (e) {
        showToast('Failed to update status.', 'error');
    }
}

async function deleteSale(saleId) {
    if (!confirm('Delete this sale and all its items? This cannot be undone.')) return;

    try {
        const res = await sessionFetch(route('salesDelete', saleId), {
            method: 'DELETE',
        }).then(r => r.json());

        if (res.success) {
            showToast('Sale deleted.', 'success');
            loadTransactions(currentPage);
            loadStats();
        } else {
            showToast(res.error || 'Could not delete sale.', 'error');
        }
    } catch (e) {
        showToast('Network error while deleting.', 'error');
    }
}

function bindFilters() {
    const typeSelect  = document.getElementById('filter-type');
    const searchInput = document.getElementById('filter-search');
    const startDate   = document.getElementById('filter-start-date');
    const endDate     = document.getElementById('filter-end-date');
    const clearBtn    = document.getElementById('btn-clear-filters');

    typeSelect?.addEventListener('change', e => {
        filterType = e.target.value;
        renderTable(); 
    });

    let searchTimer;
    searchInput?.addEventListener('input', e => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            filterSearch = e.target.value.trim();
            loadTransactions(1);
        }, 400);
    });

    startDate?.addEventListener('change', e => {
        filterStartDate = e.target.value;
        loadTransactions(1);
    });

    endDate?.addEventListener('change', e => {
        filterEndDate = e.target.value;
        loadTransactions(1);
    });

    clearBtn?.addEventListener('click', () => {
        filterType      = 'all';
        filterSearch    = '';
        filterStartDate = '';
        filterEndDate   = '';
        if (typeSelect)  typeSelect.value  = 'all';
        if (searchInput) searchInput.value = '';
        if (startDate)   startDate.value   = '';
        if (endDate)     endDate.value     = '';
        loadTransactions(1);
    });
}

function initRevenueChart() {
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not loaded.');
        return;
    }
    const container = document.getElementById('chart-container');
    if (!container) return;

    const canvas = document.createElement('canvas');
    canvas.id = 'revenueChart';
    container.innerHTML = '';
    container.classList.remove('bg-light', 'p-5');
    container.appendChild(canvas);

    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    revenueChart = new Chart(canvas.getContext('2d'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'Completed',
                    data: new Array(12).fill(0),
                    backgroundColor: '#388087cc',
                    borderRadius: 6,
                },
                {
                    label: 'Pending',
                    data: new Array(12).fill(0),
                    backgroundColor: '#102E4A55',
                    borderRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: { callbacks: { label: ctx => ` ${fmt(ctx.raw)}` } },
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) },
                },
            },
        },
    });
}

function updateRevenueChart() {
    if (!revenueChart) return;

    const completed = new Array(12).fill(0);
    const pending   = new Array(12).fill(0);
    const year      = new Date().getFullYear();

    allTransactions.forEach(t => {
        if (!t.date) return;
        const d = new Date(t.date);
        if (d.getFullYear() !== year) return;
        const m = d.getMonth();
        if (t._status === 'completed') completed[m] += parseFloat(t._amount || 0);
        else                           pending[m]   += parseFloat(t._amount || 0);
    });

    revenueChart.data.datasets[0].data = completed;
    revenueChart.data.datasets[1].data = pending;
    revenueChart.update();
}

async function loadClientsForModal() {
    try {
        const url = buildUrl(route('clientsList'), { per_page: 200 });
        const res = await sessionFetch(url).then(r => r.json());
        const select = document.getElementById('sale-client-id');
        if (!select) return;

        select.innerHTML = '<option value="">— Select client —</option>';

        const list = res.data ?? res.clients ?? [];
        list.forEach(c => {
            const opt = document.createElement('option');
            opt.value       = c.id;
            opt.textContent = c.client_name ?? c.name ?? `Client #${c.id}`;
            select.appendChild(opt);
        });
    } catch (e) {
        console.warn('Could not load clients for modal:', e);
    }
}

function bindModal() {
    document.getElementById('btn-add-product')?.addEventListener('click', addProductRow);
    document.getElementById('btn-add-service')?.addEventListener('click', addServiceRow);
    document.getElementById('btn-save-sale')?.addEventListener('click', createSale);
    document.getElementById('sale-discount')?.addEventListener('input', updateTotalsPreview);

    document.getElementById('newSaleModal')?.addEventListener('hidden.bs.modal', resetModal);
}

function addProductRow() {
    const container = document.getElementById('product-items-container');
    const idx = container.children.length;
    const div = document.createElement('div');
    div.className = 'item-row d-flex gap-2 align-items-center';
    div.innerHTML = `
        <input type="text"   class="form-control form-control-sm" placeholder="Product name"  data-field="product_name" required>
        <input type="number" class="form-control form-control-sm" placeholder="Qty"           data-field="quantity"     min="1" step="1"  value="1" style="width:75px;">
        <input type="number" class="form-control form-control-sm" placeholder="Unit price $"  data-field="unit_price"   min="0" step="0.01" value="0" style="width:110px;">
        <button type="button" class="btn-remove" title="Remove"><i class="bi bi-x-circle-fill"></i></button>
    `;
    div.querySelector('.btn-remove').addEventListener('click', () => { div.remove(); updateTotalsPreview(); });
    div.querySelectorAll('input').forEach(i => i.addEventListener('input', updateTotalsPreview));
    container.appendChild(div);
    updateTotalsPreview();
}

function addServiceRow() {
    const container = document.getElementById('service-items-container');
    const div = document.createElement('div');
    div.className = 'item-row d-flex gap-2 align-items-center';
    div.innerHTML = `
        <input type="text"   class="form-control form-control-sm" placeholder="Service name"  data-field="service_name"    required>
        <input type="number" class="form-control form-control-sm" placeholder="Hours"         data-field="quantity_hours"  min="0.1" step="0.1" value="1" style="width:85px;">
        <input type="number" class="form-control form-control-sm" placeholder="Rate $/hr"     data-field="unit_price"      min="0"   step="0.01" value="0" style="width:110px;">
        <button type="button" class="btn-remove" title="Remove"><i class="bi bi-x-circle-fill"></i></button>
    `;
    div.querySelector('.btn-remove').addEventListener('click', () => { div.remove(); updateTotalsPreview(); });
    div.querySelectorAll('input').forEach(i => i.addEventListener('input', updateTotalsPreview));
    container.appendChild(div);
    updateTotalsPreview();
}

function updateTotalsPreview() {
    let productSub = 0;
    document.querySelectorAll('#product-items-container .item-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('[data-field="quantity"]')?.value   || 0);
        const price = parseFloat(row.querySelector('[data-field="unit_price"]')?.value || 0);
        productSub += qty * price;
    });

    let serviceSub = 0;
    document.querySelectorAll('#service-items-container .item-row').forEach(row => {
        const hrs   = parseFloat(row.querySelector('[data-field="quantity_hours"]')?.value || 0);
        const price = parseFloat(row.querySelector('[data-field="unit_price"]')?.value      || 0);
        serviceSub += hrs * price;
    });

    const subtotal  = productSub + serviceSub;
    const discount  = parseFloat(document.getElementById('sale-discount')?.value || 0);
    const tax       = subtotal * 0.1;
    const total     = subtotal - discount + tax;

    document.getElementById('preview-subtotal').textContent = fmt(subtotal);
    document.getElementById('preview-discount').textContent = `-${fmt(discount)}`;
    document.getElementById('preview-tax').textContent      = fmt(tax);
    document.getElementById('preview-total').textContent    = fmt(total);
}

function resetModal() {
    document.getElementById('sale-client-id').value        = '';
    document.getElementById('sale-date').value             = new Date().toISOString().split('T')[0];
    document.getElementById('sale-payment-method').value   = '';
    document.getElementById('sale-payment-status').value   = 'Pending';
    document.getElementById('sale-discount').value         = '0';
    document.getElementById('sale-notes').value            = '';
    document.getElementById('product-items-container').innerHTML = '';
    document.getElementById('service-items-container').innerHTML = '';
    updateTotalsPreview();
}

async function createSale() {
    const clientId     = document.getElementById('sale-client-id').value;
    const saleDate     = document.getElementById('sale-date').value;
    const payMethod    = document.getElementById('sale-payment-method').value;
    const payStatus    = document.getElementById('sale-payment-status').value;
    const discount     = parseFloat(document.getElementById('sale-discount').value || 0);
    const notes        = document.getElementById('sale-notes').value.trim();

    if (!clientId)  { showToast('Please select a client.',         'warning'); return; }
    if (!saleDate)  { showToast('Please enter a sale date.',       'warning'); return; }
    if (!payMethod) { showToast('Please select a payment method.', 'warning'); return; }

    const productItems = [];
    let productValid = true;
    document.querySelectorAll('#product-items-container .item-row').forEach(row => {
        const name  = row.querySelector('[data-field="product_name"]')?.value.trim();
        const qty   = parseFloat(row.querySelector('[data-field="quantity"]')?.value   || 0);
        const price = parseFloat(row.querySelector('[data-field="unit_price"]')?.value || 0);
        if (!name || qty <= 0 || price < 0) { productValid = false; return; }
        productItems.push({ product_name: name, quantity: qty, unit_price: price });
    });

    const serviceItems = [];
    let serviceValid = true;
    document.querySelectorAll('#service-items-container .item-row').forEach(row => {
        const name  = row.querySelector('[data-field="service_name"]')?.value.trim();
        const hrs   = parseFloat(row.querySelector('[data-field="quantity_hours"]')?.value || 0);
        const price = parseFloat(row.querySelector('[data-field="unit_price"]')?.value      || 0);
        if (!name || hrs <= 0 || price < 0) { serviceValid = false; return; }
        serviceItems.push({ service_name: name, quantity_hours: hrs, unit_price: price });
    });

    if (!productValid) { showToast('Check product item fields — name, qty > 0, price ≥ 0.', 'warning'); return; }
    if (!serviceValid) { showToast('Check service item fields — name, hours > 0, rate ≥ 0.', 'warning'); return; }
    if (productItems.length === 0 && serviceItems.length === 0) {
        showToast('Add at least one product or service item.', 'warning');
        return;
    }

    const payload = {
        client_id:      parseInt(clientId),
        sale_date:      saleDate,
        payment_method: payMethod,
        payment_status: payStatus,
        discount,
        notes:          notes || null,
        product_items:  productItems,
        service_items:  serviceItems,
    };

    const saveBtn = document.getElementById('btn-save-sale');
    saveBtn.disabled     = true;
    saveBtn.textContent  = 'Saving…';

    try {
        const res = await sessionFetch(route('salesCreate'), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        }).then(r => r.json());

        if (res.success) {
            showToast(`Sale created! TX: ${res.transaction_id}`, 'success');
            bootstrap.Modal.getInstance(document.getElementById('newSaleModal'))?.hide();
            resetModal();
            loadTransactions(1);
            loadStats();
        } else {
            showToast(res.error || 'Failed to create sale.', 'error');
        }
    } catch (e) {
        console.error('Create sale error:', e);
        showToast('Network error. Please try again.', 'error');
    } finally {
        saveBtn.disabled    = false;
        saveBtn.innerHTML   = '<i class="bi bi-check-lg me-1"></i> Save Sale';
    }
}

async function generateInvoice(saleId) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    try {
        const result = await sessionFetch(route('salesGet', saleId)).then(r => r.json());
        if (!result.success) throw new Error(result.error);

        const data = result.data;

        doc.setFillColor(56, 128, 135);
        doc.rect(0, 0, 210, 40, 'F');

        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('ENTREPRISA', 20, 25);

        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('SALES INVOICE', 20, 33);
        doc.text(`Transaction: ${data.transaction_id}`, 130, 20);
        doc.text(`Date: ${data.sale_date}`,             130, 26);
        doc.text(`Method: ${data.payment_method}`,      130, 32);

        doc.setTextColor(0, 0, 0);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('BILL TO:', 20, 55);
        doc.setFont('helvetica', 'normal');
        doc.text(data.client_name,  20, 62);
        if (data.client_email) doc.text(data.client_email, 20, 68);
        if (data.client_phone) doc.text(data.client_phone, 20, 74);

        let yPos = 90;
        doc.setFillColor(240, 240, 240);
        doc.rect(20, yPos, 170, 8, 'F');
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(10);
        doc.text('Description', 25, yPos + 6);
        doc.text('Qty / Hrs',   110, yPos + 6);
        doc.text('Unit Price',  140, yPos + 6);
        doc.text('Total',       170, yPos + 6);

        doc.setFont('helvetica', 'normal');
        yPos += 15;

        (data.product_items ?? []).forEach(item => {
            doc.text(item.product_name,                          25,  yPos);
            doc.text(item.quantity.toString(),                   115, yPos);
            doc.text(parseFloat(item.unit_price).toFixed(2),    140, yPos);
            doc.text(parseFloat(item.total_price).toFixed(2),   170, yPos);
            yPos += 8;
        });

        (data.service_items ?? []).forEach(svc => {
            doc.text(svc.service_name,                            25,  yPos);
            doc.text(parseFloat(svc.quantity_hours).toFixed(1),  115, yPos);
            doc.text(parseFloat(svc.unit_price).toFixed(2),      140, yPos);
            doc.text(parseFloat(svc.total_price).toFixed(2),     170, yPos);
            yPos += 8;
        });

        yPos += 10;
        doc.line(130, yPos, 190, yPos);
        yPos += 10;
        doc.text('Subtotal:', 130, yPos);
        doc.text(parseFloat(data.subtotal).toFixed(2), 170, yPos);
        yPos += 7;
        doc.text('Discount:', 130, yPos);
        doc.text(`-${parseFloat(data.discount).toFixed(2)}`, 170, yPos);
        yPos += 7;
        doc.text('Tax:', 130, yPos);
        doc.text(parseFloat(data.tax).toFixed(2), 170, yPos);
        yPos += 12;
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('TOTAL PAID:', 130, yPos);
        doc.text(`$${parseFloat(data.total_amount).toFixed(2)}`, 170, yPos);

        doc.setFontSize(9);
        doc.setFont('helvetica', 'italic');
        doc.setTextColor(150);
        doc.text('This is a computer-generated document.', 105, 280, { align: 'center' });

        doc.save(`Invoice_${data.transaction_id}.pdf`);
        showToast('Invoice downloaded.', 'success');

    } catch (error) {
        console.error('PDF Generation Error:', error);
        showToast('Could not generate invoice: ' + error.message, 'error');
    }
}