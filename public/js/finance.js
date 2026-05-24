document.addEventListener("DOMContentLoaded", () => {

  // Chart 
  let financeChart;

  async function loadFinanceChart(year) {
  const ctx = document.getElementById("financeChart");
  if (!ctx) return;

  const res = await fetch(`/finance/chart?year=${year}`);
  const result = await res.json();

  const labels = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];

  const expenseData = new Array(12).fill(0);
  const salesData = new Array(12).fill(0);

  result.data.expenses.forEach(e => {
    expenseData[e.month - 1] = parseFloat(e.total);
  });

  result.data.sales.forEach(s => {
    salesData[s.month - 1] = parseFloat(s.total);
  });

  if (financeChart) financeChart.destroy();

  financeChart = new Chart(ctx, {
    type: "line",
    data: {
      labels,
      datasets: [
        {
          label: "Expenses",
          data: expenseData,
          borderColor: "#102E4A",
          fill: false,
          tension: 0.4
        },
        {
          label: "Sales",
          data: salesData,
          borderColor: "#388087",
          fill: false,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: "bottom" },
      },
    }
  });
}

  // KPIs
  async function loadFinanceKPIs() {
    try {
      const res = await fetch("/finance/kpis");
      const result = await res.json();

      if (result.status !== "success") return;

      const d = result.data;

      document.getElementById("revenue-kpi").innerText = d.revenue + " Dt";
      document.getElementById("expenses-kpi").innerText = d.expenses + " Dt";
      document.getElementById("net-profit-kpi").innerText = d.profit + " Dt";
      document.getElementById("salecnt-kpi").innerText = d.salesCount;

    } catch (e) {
      console.error("KPI error", e);
    }
  }
  loadFinanceKPIs();

  // Init chart with current year
  const yearFilter = document.getElementById("yearFilter");
  if (yearFilter) {
    loadFinanceChart(yearFilter.value);
    yearFilter.addEventListener("change", () => {
      loadFinanceChart(yearFilter.value);
    });
  }

  // Add transaction
  const form = document.getElementById("transaction-form");

  form?.addEventListener("submit", async (e) => {

    e.preventDefault();

    try {

      const res = await fetch("/finance/add", {
        method: "POST",
        body: new FormData(form)
      });

      const result = await res.json();

      if (result.status !== "success") {
        console.error(result.message);
        return;
      }

      const exp = result.expense;

      const row = document.createElement("tr");

      let badgeClass = "badge-other";

      if (exp.category === "Rent") {
        badgeClass = "badge-rent";
      }
      else if (exp.category === "Salary") {
        badgeClass = "badge-salary";
      }
      else if (exp.category === "Tools") {
        badgeClass = "badge-tools";
      }
      else if (exp.category === "Marketing") {
        badgeClass = "badge-marketing";
      }
      else if (exp.category === "Supply") {
        badgeClass = "badge-supplies";
      }

      row.innerHTML = `
        <td>${exp.date}</td>

        <td>
          <span class="badge ${badgeClass}">
            ${exp.category}
          </span>
        </td>

        <td class="fw-bold">
          ${exp.amount} Dt
        </td>

        <td>
          ${exp.description ?? ""}
        </td>
      `;

      tableBody.prepend(row);

      allRows.unshift(row);

      form.reset();

      loadFinanceKPIs();
      loadFinanceChart(yearFilter.value);

      visible = step;
      updateTable();

    } catch (e) {
      console.error("Add transaction error", e);
    }

  });

  // Table filters
  const tableBody = document.getElementById("transaction-list");
  if (!tableBody) return;

  let allRows = Array.from(tableBody.querySelectorAll("tr"));

  const filterType = document.getElementById("filter-type");
  const filterStart = document.getElementById("filter-date-start");
  const filterEnd = document.getElementById("filter-date-end");
  const resetBtn = document.getElementById("reset-filters");
  const showMoreBtn = document.getElementById("show-more-expenses");

  let step = 5;
  let visible = step;
  let filtered = [];

  function updateTable() {

    const type = filterType?.value || "all";
    const start = filterStart?.value ? new Date(filterStart.value) : null;
    const end = filterEnd?.value ? new Date(filterEnd.value) : null;

    filtered = allRows.filter(row => {
      const date = new Date(row.cells[0].innerText);
      const cat = row.cells[1].innerText.trim();

      return (type === "all" || cat.includes(type))
        && (!start || date >= start)
        && (!end || date <= end);
    });

    allRows.forEach(r => r.style.display = "none");

    filtered.slice(0, visible).forEach(r => r.style.display = "");

    if (!showMoreBtn) return;

    if (filtered.length <= step) {
      showMoreBtn.style.display = "none";
    } else {
      showMoreBtn.style.display = "inline-block";
      showMoreBtn.innerText =
        visible >= filtered.length
          ? "Show Less"
          : `Show More (${filtered.length - visible})`;
    }
  }

  function resetFilters() {
    if (filterType) filterType.value = "all";
    if (filterStart) filterStart.value = "";
    if (filterEnd) filterEnd.value = "";
    visible = step;
    updateTable();
  }

  filterType?.addEventListener("input", () => { visible = step; updateTable(); });
  filterStart?.addEventListener("input", () => { visible = step; updateTable(); });
  filterEnd?.addEventListener("input", () => { visible = step; updateTable(); });

  resetBtn?.addEventListener("click", resetFilters);

  showMoreBtn?.addEventListener("click", () => {
    const isShowingAll = visible >= filtered.length;

    if (isShowingAll) {
      visible = step;
    } else {
      visible += step;
    }

    updateTable();
  });

  updateTable();

});