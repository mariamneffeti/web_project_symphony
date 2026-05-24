let receiptModal = null;
let salesChart   = null;
let PRODUCT_DATA = [];
let SERVICE_DATA = [];
let currentMode  = 'sales';

document.addEventListener('DOMContentLoaded', function () {

    const receiptModalEl = document.getElementById('receiptModal');
    if (receiptModalEl) receiptModal = new bootstrap.Modal(receiptModalEl);

    document.getElementById('btn-process-transaction')
        ?.addEventListener('click', processTransaction);

    initClientSearch();

    document.getElementById('discount-input')
        ?.addEventListener('input', updateTotals);

    loadDashboardData();
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

/** Replace __ID__ placeholder in a route template */
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

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
}

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

async function loadDashboardData() {
    try {
        const [statsRes, prodRes, servRes] = await Promise.all([
            sessionFetch(route('salesStats')),
            sessionFetch(buildUrl(route('productsList'), { per_page: 200 })),
            sessionFetch(buildUrl(route('servicesList'), { per_page: 200 })),
        ]);

        const statsResult = await statsRes.json();
        const prodResult  = await prodRes.json();
        const servResult  = await servRes.json();

        PRODUCT_DATA = prodResult.success ? (prodResult.data ?? []) : [];
        SERVICE_DATA = servResult.success ? (servResult.data ?? []) : [];

        if (statsResult.success) {
            updateStatsUI(statsResult.data);
            if (statsResult.data.recent_sales) {
                updateSalesChart(statsResult.data.recent_sales);
            }
        }

        const tbody = document.getElementById('services-tbody');
        if (tbody) {
            tbody.innerHTML = '';
            addRow();
        }

    } catch (error) {
        console.error('Initial load failed:', error);
        showToast('Failed to load dashboard data.', 'error');
    }
}

function updateStatsUI(data) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('stat-today-count',  data.today?.count       ?? '—');
    set('stat-today-amount', formatCurrency(data.today?.total));
    set('stat-month-count',  data.this_month?.count  ?? '—');
    set('stat-month-amount', formatCurrency(data.this_month?.total));
    set('stat-clients',      data.total_clients      ?? '—');
}


function updateSalesChart(salesData) {
    const ctx = document.getElementById('salesChart');
    if (!ctx || typeof Chart === 'undefined') return;

    const labels = [];
    const data   = [];

    for (let i = 6; i >= 0; i--) {
        const date    = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().split('T')[0];
        labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
        const sale = (salesData ?? []).find(s => s.date === dateStr);
        data.push(sale ? parseFloat(sale.total) : 0);
    }

    if (salesChart) salesChart.destroy();

    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label:           'Daily Sales',
                data,
                borderColor:     '#388087',
                backgroundColor: 'rgba(56,128,135,0.10)',
                tension:         0.4,
                fill:            true,
                pointBackgroundColor: '#388087',
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: { label: ctx => formatCurrency(ctx.parsed.y) },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '$' + v.toLocaleString() },
                },
                x: { grid: { display: false } },
            },
        },
    });
}

function initClientSearch() {
    const searchInput   = document.getElementById('client-search-input');
    const resultsDiv    = document.getElementById('search-results');
    const clientIdInput = document.getElementById('client-id');
    if (!searchInput) return;

    let allClients = [];

    sessionFetch(buildUrl(route('clientsList'), { per_page: 500 }))
        .then(r => r.json())
        .then(result => { if (result.success) allClients = result.data ?? []; })
        .catch(e => console.warn('Could not preload clients:', e));

    searchInput.addEventListener('input', function () {
        const val = this.value.toLowerCase().trim();
        resultsDiv.innerHTML = '';

        if (val.length < 1) { resultsDiv.classList.add('d-none'); return; }

        const filtered = allClients.filter(c =>
            (c.client_name ?? '').toLowerCase().includes(val)
        );

        if (filtered.length > 0) {
            filtered.slice(0, 10).forEach(client => {
                const item = document.createElement('div');
                item.className = 'list-group-item list-group-item-action';
                item.innerHTML = `<i class="bi bi-person me-2"></i>${client.client_name}`;
                item.onclick = () => selectClient(client);
                resultsDiv.appendChild(item);
            });
            resultsDiv.classList.remove('d-none');
        } else {
            resultsDiv.innerHTML = `<div class="list-group-item text-muted small">No clients found.</div>`;
            resultsDiv.classList.remove('d-none');
        }
    });

    document.addEventListener('click', e => {
        if (!searchInput.contains(e.target)) resultsDiv.classList.add('d-none');
    });
}

function selectClient(client) {
    document.getElementById('client-search-input').value = client.client_name;
    document.getElementById('client-id').value           = client.id;
    document.getElementById('search-results').classList.add('d-none');

    const emailEl = document.getElementById('client-email');
    if (emailEl) emailEl.value = client.email ?? '';

    updateClientStats(client.id);
}

async function updateClientStats(clientId) {
    try {
        const res    = await sessionFetch(route('clientsGet', clientId));
        const result = await res.json();
        if (!result.success) return;

        const client = result.data;
        const lpd    = document.getElementById('last-purchase-date');
        const ts     = document.getElementById('client-total-spent');
        if (lpd) lpd.textContent = client.last_purchase_date || 'No purchases yet';
        if (ts)  ts.textContent  = formatCurrency(client.total_spent);

    } catch (e) {
        console.error('Error loading client stats:', e);
    }
}

function setMode(mode) {
    const tbody = document.getElementById('services-tbody');
    if (tbody.children.length > 0) {
        if (!confirm('Switching modes will clear current selections. Continue?')) return;
    }

    currentMode = mode;
    tbody.innerHTML = '';

    document.getElementById('mode-sales')?.classList.toggle('active', mode === 'sales');
    document.getElementById('mode-services')?.classList.toggle('active', mode === 'services');

    const header = document.getElementById('type-header');
    if (header) header.textContent = (mode === 'sales') ? 'Product Name' : 'Service Name';

    addRow();
    updateTotals();
}

function addRow() {
    const tbody = document.getElementById('services-tbody');
    const row   = document.createElement('tr');
    row.setAttribute('data-type', currentMode === 'sales' ? 'product' : 'service');

    const items = (currentMode === 'sales') ? PRODUCT_DATA : SERVICE_DATA;

    let optionsHtml = `<option value="" data-price="0">Select item…</option>`;
    items.forEach(item => {
        const name  = (currentMode === 'sales') ? (item.product_name ?? item.name) : (item.service_name ?? item.name);
        const price = (currentMode === 'sales') ? (item.price ?? item.unit_price ?? 0) : (item.base_price ?? item.price ?? 0);
        optionsHtml += `<option value="${item.id}" data-name="${name}" data-price="${price}">${name}</option>`;
    });

    row.innerHTML = `
        <td class="ps-4">
            <select class="form-select form-select-sm border-0 bg-light item-select"
                    onchange="handleItemSelect(this)">
                ${optionsHtml}
            </select>
        </td>
        <td>
            <input type="number"
                   class="form-control form-control-sm text-center item-qty"
                   value="1" min="1" step="${currentMode === 'services' ? '0.5' : '1'}"
                   oninput="updateTotals()">
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-transparent border-0 text-muted small">$</span>
                <input type="number"
                       class="form-control form-control-sm border-0 bg-light price-input"
                       value="0" min="0" step="0.01"
                       oninput="updateTotals()">
            </div>
        </td>
        <td class="fw-bold text-dark row-total">$0.00</td>
        <td class="text-end pe-4">
            <button class="btn btn-link text-danger p-0"
                    onclick="this.closest('tr').remove(); updateTotals();"
                    title="Remove row">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(row);
}

function handleItemSelect(select) {
    const option = select.options[select.selectedIndex];
    const price  = option.getAttribute('data-price') || 0;
    select.closest('tr').querySelector('.price-input').value = price;
    updateTotals();
}

function updateTotals() {
    let subtotal = 0;

    document.querySelectorAll('#services-tbody tr').forEach(row => {
        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const total = qty * price;
        const el    = row.querySelector('.row-total');
        if (el) el.textContent = formatCurrency(total);
        subtotal += total;
    });

    const discount = parseFloat(document.getElementById('discount-input')?.value || 0);
    const tax      = (subtotal - discount) * 0.10;
    const total    = subtotal - discount + tax;

    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
    set('summary-subtotal', formatCurrency(subtotal));
    set('summary-discount', `-${formatCurrency(discount)}`);
    set('summary-tax',      formatCurrency(tax));
    set('summary-total',    formatCurrency(total));
}

async function processTransaction() {
    const clientId    = document.getElementById('client-id')?.value;
    const clientName  = document.getElementById('client-search-input')?.value;
    const payMethod   = document.getElementById('payment-method')?.value || 'Cash';
    const discountRaw = parseFloat(document.getElementById('discount-input')?.value || 0);
    const saleDate    = new Date().toISOString().split('T')[0];

    if (!clientId) {
        showToast('Please select a client first.', 'warning');
        return;
    }

    const payload = {
        client_id:      parseInt(clientId),
        sale_date:      saleDate,
        payment_method: payMethod,
        payment_status: 'Pending',
        discount:       discountRaw,
        notes:          'Transaction from Employee Dashboard',
        product_items:  [],
        service_items:  [],
    };

    let hasItems = false;
    document.querySelectorAll('#services-tbody tr').forEach(row => {
        const select = row.querySelector('.item-select');
        const id     = select?.value;
        if (!id) return;

        const qty   = parseFloat(row.querySelector('.item-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.price-input')?.value) || 0;
        const name  = select.options[select.selectedIndex]?.getAttribute('data-name') ?? '';
        const type  = row.getAttribute('data-type');

        if (qty <= 0) return;
        hasItems = true;

        if (type === 'product') {
            payload.product_items.push({
                product_id:   parseInt(id),
                product_name: name,
                quantity:     qty,
                unit_price:   price,
            });
        } else {
            payload.service_items.push({
                service_name:   name,
                quantity_hours: qty,
                unit_price:     price,
            });
        }
    });

    if (!hasItems) {
        showToast('Please add at least one item with quantity > 0.', 'warning');
        return;
    }

    const btn = document.getElementById('btn-process-transaction');
    if (btn) { btn.disabled = true; btn.textContent = 'Processing…'; }

    try {
        const response = await sessionFetch(route('salesCreate'), {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });

        const result = await response.json();

        if (result.success) {
            showToast('Transaction processed successfully!', 'success');
            populateReceipt(result, clientName, payload);
            if (receiptModal) receiptModal.show();
            resetForm();
            loadDashboardData(); 
        } else {
            showToast('Error: ' + (result.error || 'Unknown error'), 'error');
            console.error('Transaction error:', result);
        }

    } catch (error) {
        console.error('Fetch error:', error);
        showToast('Server connection failed. Try again.', 'error');
    } finally {
        if (btn) {
            btn.disabled    = false;
            btn.innerHTML   = '<i class="bi bi-check-circle me-2"></i>Process Transaction';
        }
    }
}

function populateReceipt(result, clientName, payload) {
    const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };

    set('receipt-id',          result.transaction_id ?? '—');
    set('receipt-date',        new Date().toLocaleDateString('en-US', { dateStyle: 'medium' }));
    set('receipt-client-name', clientName);

    const tbody = document.getElementById('receipt-items');
    if (tbody) {
        tbody.innerHTML = '';
        [...(payload.product_items ?? []), ...(payload.service_items ?? [])].forEach(item => {
            const name  = item.product_name ?? item.service_name ?? '—';
            const qty   = item.quantity     ?? item.quantity_hours ?? 1;
            const total = qty * item.unit_price;
            const tr    = document.createElement('tr');
            tr.innerHTML = `
                <td>${name}</td>
                <td class="text-center">${qty}</td>
                <td class="text-end">${formatCurrency(total)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    set('receipt-subtotal', document.getElementById('summary-subtotal')?.textContent ?? '—');
    set('receipt-discount', document.getElementById('summary-discount')?.textContent ?? '—');
    set('receipt-tax',      document.getElementById('summary-tax')?.textContent      ?? '—');
    set('receipt-total',    document.getElementById('summary-total')?.textContent    ?? '—');
}
function resetForm() {
    const searchInput = document.getElementById('client-search-input');
    const clientId    = document.getElementById('client-id');
    const email       = document.getElementById('client-email');
    const discount    = document.getElementById('discount-input');
    const lpd         = document.getElementById('last-purchase-date');
    const ts          = document.getElementById('client-total-spent');

    if (searchInput) searchInput.value = '';
    if (clientId)    clientId.value    = '';
    if (email)       email.value       = '';
    if (discount)    discount.value    = '0';
    if (lpd)         lpd.textContent   = '—';
    if (ts)          ts.textContent    = '$0';

    const tbody = document.getElementById('services-tbody');
    if (tbody) { tbody.innerHTML = ''; addRow(); }
    updateTotals();
}