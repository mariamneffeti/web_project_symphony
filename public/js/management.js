const ROWS_STEP   = 5;
let allMeetings   = [];   
let filteredRows  = [];  
let visibleCount  = ROWS_STEP;

let allClients    = [];   
let allEmployees  = [];  

document.addEventListener("DOMContentLoaded", () => {
  loadMeetings();
  loadClients();
  loadEmployees();
});

// Loading and rendering meetings with pagination
async function loadMeetings() {
  try {
    const res = await fetch("/api/meetings");
    allMeetings = await res.json();
    filteredRows = [...allMeetings];
    renderMeetings();
  } catch (e) {
    document.getElementById("meetingsBody").innerHTML =
      `<tr><td colspan="7" class="text-center text-danger py-4">Failed to load meetings.</td></tr>`;
  }
}

function renderMeetings() {
  const tbody = document.getElementById("meetingsBody");
  visibleCount = ROWS_STEP;

  if (!filteredRows.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No meetings found.</td></tr>`;
    toggleShowButtons();
    return;
  }

  tbody.innerHTML = filteredRows.map((m, i) => buildRow(m, i)).join("");
  applyVisibility();
  toggleShowButtons();
}

function buildRow(m, i) {
  const badgeClass = { scheduled:"badge-scheduled", done:"badge-done", cancelled:"badge-cancelled" }[m.status] || "";

  let linkCell = "—";
  if (m.meet_link) {
    if (m.meet_link.startsWith("http")) {
      linkCell = `<a href="${escHtml(m.meet_link)}" target="_blank" class="text-decoration-none small"><i class="bi bi-box-arrow-up-right me-1"></i>${escHtml(m.meet_link.replace(/^https?:\/\//, "").slice(0,30))}…</a>`;
    } else {
      linkCell = `<code class="small">${escHtml(m.meet_link)}</code>`;
    }
  }

  const actions = `
    <div class="d-flex gap-1">
      <button class="btn btn-sm btn-outline-secondary" title="Details" onclick="openDetails(${m.id})">
        <i class="bi bi-info-circle"></i>
      </button>
      <button class="btn btn-sm btn-outline-primary" title="Reschedule" onclick="openReschedule(${m.id},'${m.meeting_date}','${m.meeting_time}','${m.status}')">
        <i class="bi bi-calendar-event"></i>
      </button>
      <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="openDelete(${m.id})">
        <i class="bi bi-trash"></i>
      </button>
    </div>`;

  return `<tr class="meet-row" data-index="${i}">
    <td class="px-4">${escHtml(m.meeting_date)}</td>
    <td class="fw-bold">${m.meeting_time.slice(0,5)}</td>
    <td>${escHtml(m.title)}</td>
    <td><span class="badge rounded-pill px-3 py-1 ${badgeClass}">${escHtml(m.status)}</span></td>
    <td>${linkCell}</td>
    <td class="text-muted small">${escHtml(m.notes || "")}</td>
    <td>${actions}</td>
  </tr>`;
}

function applyVisibility() {
  const rows = document.querySelectorAll("#meetingsBody .meet-row");
  rows.forEach((r, i) => r.classList.toggle("row-hidden", i >= visibleCount));
  toggleShowButtons();
}

function showMoreRows() {
  visibleCount = Math.min(visibleCount + ROWS_STEP, filteredRows.length);
  applyVisibility();
}

function showLessRows() {
  visibleCount = ROWS_STEP;
  applyVisibility();
  document.getElementById("calendar").scrollIntoView({ behavior: "smooth" });
}

function toggleShowButtons() {
  const more = document.getElementById("showMoreBtn");
  const less = document.getElementById("showLessBtn");
  more.classList.toggle("d-none", visibleCount >= filteredRows.length);
  less.classList.toggle("d-none", visibleCount <= ROWS_STEP);
}

// Filtering meetings
function applyMeetFilters() {
  const search   = document.getElementById("meetSearch").value.toLowerCase();
  const status   = document.getElementById("meetStatusFilter").value;
  const dateFrom = document.getElementById("meetDateFrom").value;
  const dateTo   = document.getElementById("meetDateTo").value;

  filteredRows = allMeetings.filter(m => {
    const matchSearch = !search ||
      m.title.toLowerCase().includes(search) ||
      (m.notes || "").toLowerCase().includes(search);
    const matchStatus = !status || m.status === status;
    const matchFrom   = !dateFrom || m.meeting_date >= dateFrom;
    const matchTo     = !dateTo   || m.meeting_date <= dateTo;
    return matchSearch && matchStatus && matchFrom && matchTo;
  });

  renderMeetings();
}

function clearMeetFilters() {
  ["meetSearch","meetDateFrom","meetDateTo"].forEach(id => document.getElementById(id).value = "");
  document.getElementById("meetStatusFilter").value = "";
  filteredRows = [...allMeetings];
  renderMeetings();
}

// Getting and rendering employee list for meeting assignment
async function loadEmployees() {
  try {
    const res = await fetch("/api/employees");
    allEmployees = await res.json();
    renderEmployeeCheckboxes("");
  } catch (e) {
    document.getElementById("employeeCheckboxList").innerHTML =
      `<em class="text-danger small">Failed to load employees.</em>`;
  }
}

function renderEmployeeCheckboxes(filter) {
  const list = document.getElementById("employeeCheckboxList");
  const q    = filter.toLowerCase();
  const emp  = allEmployees.filter(e =>
    `${e.first_name} ${e.last_name}`.toLowerCase().includes(q) ||
    (e.department || "").toLowerCase().includes(q)
  );

  if (!emp.length) { list.innerHTML = `<em class="text-muted small">No employees found.</em>`; return; }

  list.innerHTML = emp.map(e => `
    <label class="d-flex align-items-center gap-2 p-1 mb-1">
      <input type="checkbox" class="form-check-input emp-check" value="${e.id}">
      <span class="small"><strong>${escHtml(e.first_name)} ${escHtml(e.last_name)}</strong>
        <span class="text-muted"> — ${escHtml(e.position || "")} ${e.department ? "("+escHtml(e.department)+")" : ""}</span>
      </span>
    </label>`).join("");
}

function filterEmployeeList() {
  renderEmployeeCheckboxes(document.getElementById("empSearchInput").value);
}

// Adding a new meeting
async function saveMeeting() {
  const titleEl = document.getElementById("meeting_title");
  const dateEl  = document.getElementById("meeting_meeting_date");
  const timeEl  = document.getElementById("meeting_meeting_time");
  const status_msg_el = document.getElementById("addMeetStatus");

  const title  = titleEl ? titleEl.value.trim() : "";
  const date   = dateEl ? dateEl.value : "";
  const time   = timeEl ? timeEl.value : "";

  if (!title || !date || !time) {
    status_msg_el.innerHTML = `<div class="alert alert-warning py-2">Please fill in title, date, and time.</div>`;
    return;
  }

  status_msg_el.innerHTML = `<div class="alert alert-info py-2">Saving...</div>`;

  const formElement = document.getElementById("addMeetingForm");
  const formData = new FormData(formElement);

  const empIds = Array.from(document.querySelectorAll(".emp-check:checked")).map(c => c.value);
  empIds.forEach(id => {
    formData.append("employee_ids[]", id);
  });

  try {
    const res  = await fetch("/api/meetings/add", {
      method: "POST",
      body: formData
    });
    const data = await res.json();
    if (data.success) {
      status_msg_el.innerHTML = `<div class="alert alert-success py-2">Meeting added! Notifications sent.</div>`;
      setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById("addMeetingModal")).hide();
        status_msg_el.innerHTML = "";
        
        formElement.reset();
        document.getElementById("empSearchInput").value = "";
        renderEmployeeCheckboxes("");
        loadMeetings();
      }, 1200);
    } else {
      status_msg_el.innerHTML = `<div class="alert alert-danger py-2">${escHtml(data.error || "Error saving meeting.")}</div>`;
    }
  } catch (e) {
    status_msg_el.innerHTML = `<div class="alert alert-danger py-2">Network error.</div>`;
  }
}

// Rescheduling a meeting
function openReschedule(id, date, time, status) {
  document.getElementById("editMeetId").value   = id;
  document.getElementById("editDate").value     = date;
  document.getElementById("editTime").value     = time.slice(0,5);
  document.getElementById("editStatus").value   = status;
  document.getElementById("editMeetStatus").innerHTML = "";
  new bootstrap.Modal(document.getElementById("rescheduleModal")).show();
}

async function updateMeeting() {
  const status_el = document.getElementById("editMeetStatus");
  status_el.innerHTML = `<div class="alert alert-info py-2">Updating...</div>`;

  // Capture the form structure layout container directly
  const formElement = document.getElementById("updateMeetingForm");
  const formData = new FormData(formElement);

  try {
    const res = await fetch("/api/meetings/update", {
      method: "POST",
      body: formData 
    });
    const data = await res.json();
    if (data.success) {
      status_el.innerHTML = `<div class="alert alert-success py-2">Meeting updated and synchronized!</div>`;
      setTimeout(() => {
        bootstrap.Modal.getInstance(document.getElementById("rescheduleModal")).hide();
        status_el.innerHTML = "";
        loadMeetings();
      }, 1200);
    } else {
      status_el.innerHTML = `<div class="alert alert-danger py-2">${escHtml(data.error || "Error updating.")}</div>`;
    }
  } catch (e) {
    status_el.innerHTML = `<div class="alert alert-danger py-2">Network communication error.</div>`;
  }
}

async function sendClientEmail() {
  const sel      = document.getElementById("clientSelect");
  const selected = Array.from(sel.selectedOptions);
  const status   = document.getElementById("mailStatus");

  if (!selected.length) {
    status.innerHTML = `<div class="alert alert-warning">Please select at least one client recipient.</div>`;
    return;
  }

  status.innerHTML = `<div class="alert alert-info">Validating fields...</div>`;

  const emailFormElement = document.getElementById("broadcastEmailForm");
  const formData = new FormData(emailFormElement);

  try {
    const checkRes = await fetch("/api/clients/validate-email", {
      method: "POST",
      body: formData
    });
    const checkData = await checkRes.json();

    if (!checkData.success) {
      status.innerHTML = `<div class="alert alert-danger">${escHtml(checkData.error)}</div>`;
      return;
    }

    const { subject, body } = checkData.data;
    status.innerHTML = `<div class="alert alert-info">📤 Sending...</div>`;

    const sends = selected.map(c =>
      fetch(ZAPIER_WEBHOOK, {
        method: "POST",
        headers: { "Content-Type": "application/json" }, 
        body: JSON.stringify({ to: c.value, clientName: c.text, subject, body })
      })
    );

    await Promise.all(sends);
    status.innerHTML = `<div class="alert alert-success">Email sent to ${selected.length} client(s)!</div>`;
    emailFormElement.reset();
  } catch (e) {
    status.innerHTML = `<div class="alert alert-danger">Failed to process request safely.</div>`;
  }
}

// Viewing meeting details and assigned employees
async function openDetails(id) {
  const meeting = allMeetings.find(m => m.id == id);
  if (!meeting) return;

  const badgeClass = { scheduled:"badge-scheduled", done:"badge-done", cancelled:"badge-cancelled" }[meeting.status] || "";

  document.getElementById("detailTitle").textContent  = meeting.title;
  document.getElementById("detailDate").textContent   = meeting.meeting_date;
  document.getElementById("detailTime").textContent   = meeting.meeting_time.slice(0,5);
  document.getElementById("detailNotes").textContent  = meeting.notes || "—";
  document.getElementById("detailStatus").innerHTML   = `<span class="badge rounded-pill px-3 py-1 ${badgeClass}">${escHtml(meeting.status)}</span>`;

  const linkEl = document.getElementById("detailLink");
  if (meeting.meet_link) {
    if (meeting.meet_link.startsWith("http")) {
      linkEl.innerHTML = `<a href="${escHtml(meeting.meet_link)}" target="_blank">${escHtml(meeting.meet_link)}</a>`;
    } else {
      linkEl.innerHTML = `<code>${escHtml(meeting.meet_link)}</code>`;
    }
  } else {
    linkEl.textContent = "—";
  }

  const empList = document.getElementById("detailEmployees");
  empList.innerHTML = `<li class="list-group-item text-muted small">Loading...</li>`;
  new bootstrap.Modal(document.getElementById("detailMeetModal")).show();

  try {
    const res  = await fetch(`/api/meetings/${id}/employees`);
    const emps = await res.json();
    if (!emps.length) {
      empList.innerHTML = `<li class="list-group-item text-muted small">No employees assigned.</li>`;
    } else {
      empList.innerHTML = emps.map(e => `
        <li class="list-group-item d-flex align-items-center gap-2 py-2">
          <i class="bi bi-person-circle text-secondary"></i>
          <span><strong>${escHtml(e.first_name)} ${escHtml(e.last_name)}</strong>
            <span class="text-muted small ms-1">${escHtml(e.position || "")}${e.department ? " · " + escHtml(e.department) : ""}</span>
          </span>
          ${e.email ? `<a href="mailto:${escHtml(e.email)}" class="ms-auto text-muted small"><i class="bi bi-envelope"></i></a>` : ""}
        </li>`).join("");
    }
  } catch {
    empList.innerHTML = `<li class="list-group-item text-danger small">Failed to load employees.</li>`;
  }
}

// Deleting a meeting
function openDelete(id) {
  document.getElementById("deleteMeetId").value = id;
  new bootstrap.Modal(document.getElementById("deleteMeetModal")).show();
}

async function confirmDeleteMeeting() {
  const id  = document.getElementById("deleteMeetId").value;
  try {
    const res  = await fetch("/api/meetings/delete", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id })
    });
    await res.json();
    bootstrap.Modal.getInstance(document.getElementById("deleteMeetModal")).hide();
    loadMeetings();
  } catch (e) { }
}

// Loading and rendering clients for email sending
async function loadClients() {
  try {
    const res = await fetch("/api/clients");
    allClients = await res.json();
    renderClientSelect("");
  } catch (e) {
    console.error("Could not load clients:", e);
  }
}

function renderClientSelect(nameFilter, statusFilter) {
  const select = document.getElementById("clientSelect");
  const nf = (nameFilter || "").toLowerCase();
  const sf = statusFilter || "";
  const filtered = allClients.filter(c =>
    (!nf || c.client_name.toLowerCase().includes(nf)) &&
    (!sf || (c.status || "") === sf)
  );
  select.innerHTML = filtered.map(c =>
    `<option value="${escHtml(c.email)}" data-status="${escHtml(c.status||"")}">${escHtml(c.client_name)}</option>`
  ).join("");
}

function filterClientSelect() {
  const name   = document.getElementById("clientFilterInput").value;
  const status = document.getElementById("clientStatusFilter").value;
  renderClientSelect(name, status);
  document.getElementById("clientNameDisplay").value  = "";
  document.getElementById("clientEmailDisplay").value = "";
}

function fillClientInfo() {
  const sel    = document.getElementById("clientSelect");
  const opts   = Array.from(sel.selectedOptions);
  document.getElementById("clientNameDisplay").value  = opts.map(o => o.text).join(", ");
  document.getElementById("clientEmailDisplay").value = opts.map(o => o.value).join(", ");
}

// Sending emails to selected clients
async function sendClientEmail() {
  const sel      = document.getElementById("clientSelect");
  const selected = Array.from(sel.selectedOptions);
  const subject  = document.getElementById("mailSubject").value.trim();
  const body     = document.getElementById("mailPrompt").value.trim();
  const status   = document.getElementById("mailStatus");

  if (!selected.length || !subject || !body) {
    status.innerHTML = `<div class="alert alert-warning">Please fill in all fields and select at least one client.</div>`;
    return;
  }

  status.innerHTML = `<div class="alert alert-info">📤 Sending...</div>`;

  const sends = selected.map(c =>
    fetch(ZAPIER_WEBHOOK, {
      method: "POST",
      headers: { "Content-Type": "application/json" }, 
      body: JSON.stringify({ to: c.value, clientName: c.text, subject, body })
    })
  );

  try {
    await Promise.all(sends);
    status.innerHTML = `<div class="alert alert-success">Email sent to ${selected.length} client(s)!</div>`;
  } catch (e) {
    status.innerHTML = `<div class="alert alert-danger">Failed to send some emails.</div>`;
  }
}

// Simple AI assistant interaction
async function sendAiMessage() {
  const input = document.getElementById("aiInput");
  const resp  = document.getElementById("aiResponse");
  const q     = input.value.trim();
  if (!q) return;
  resp.innerHTML = `<em class="text-muted small">⏳ Thinking...</em>`;
  setTimeout(() => {
    resp.innerHTML = `<span class="small">🤖 AI Assistant is coming soon. Your question: "<strong>${escHtml(q)}</strong>"</span>`;
    input.value = "";
  }, 900);
}

document.getElementById("aiInput")?.addEventListener("keydown", e => {
  if (e.key === "Enter") sendAiMessage();
});

function escHtml(s) {
  if (s == null) return "";
  return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;");
}