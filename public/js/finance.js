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

    result.data.expenses.forEach(e => {
      expenseData[e.month - 1] = parseFloat(e.total);
    });

    if (financeChart) financeChart.destroy();

    financeChart = new Chart(ctx, {
      type: "line",
      data: {
        labels,
        datasets: [{
          label: "Expenses",
          data: expenseData,
          borderColor: "#102E4A",
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false
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

  // Table filters
  const tableBody = document.getElementById("transaction-list");
  if (!tableBody) return;

  const allRows = Array.from(tableBody.querySelectorAll("tr"));

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