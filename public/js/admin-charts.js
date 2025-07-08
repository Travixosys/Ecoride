// public/js/admin-chart.js

document.addEventListener("DOMContentLoaded", function () {
  fetch("/admin/graph-data")
    .then((response) => {
      if (!response.ok) throw new Error("Network response was not OK");
      return response.json();
    })
    .then((data) => {
      // 1) Carpools per Day (green bar)
      const carpoolDates = data.carpoolsPerDay.map((d) => d.date);
      const carpoolCounts = data.carpoolsPerDay.map((d) => d.count);

      new Chart(document.getElementById("carpoolsChart").getContext("2d"), {
        type: "bar",
        data: {
          labels: carpoolDates,
          datasets: [
            {
              label: "Carpools per Day",
              data: carpoolCounts,
              backgroundColor: "rgba(34,197,94,0.6)",
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            x: { title: { display: true, text: "Date" } },
            y: {
              beginAtZero: true,
              title: { display: true, text: "Number of Carpools" },
            },
          },
        },
      });

      // 2) Driver Net Payouts per Day (orange line)
      const payoutDates = data.driverNetPerDay.map((d) => d.date);
      const payoutAmounts = data.driverNetPerDay.map((d) => d.driver_net);

      new Chart(document.getElementById("creditsChart").getContext("2d"), {
        type: "line",
        data: {
          labels: payoutDates,
          datasets: [
            {
              label: "Driver Net Payouts",
              data: payoutAmounts,
              borderColor: "rgba(245,130,32,1)", // orange
              backgroundColor: "rgba(245,130,32,0.1)", // light orange fill
              fill: true,
              tension: 0.3,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            x: { title: { display: true, text: "Date" } },
            y: {
              beginAtZero: true,
              title: { display: true, text: "Credits" },
            },
          },
        },
      });

      // 3) Platform Commission per Day (purple line + cumulative dashed)
      const commissionDates = data.commissionPerDay.map((d) => d.date);
      const commissionAmounts = data.commissionPerDay.map(
        (d) => d.commission_earned
      );

      // cumulative total
      const cumulative = [];
      commissionAmounts.reduce((sum, val) => {
        sum += val;
        cumulative.push(sum);
        return sum;
      }, 0);

      new Chart(document.getElementById("commissionChart").getContext("2d"), {
        type: "line",
        data: {
          labels: commissionDates,
          datasets: [
            {
              label: "Daily Commission",
              data: commissionAmounts,
              borderColor: "rgba(128,0,128,1)", // purple
              backgroundColor: "rgba(128,0,128,0.1)", // light purple fill
              fill: true,
              tension: 0.3,
            },
            {
              label: "Total Commission",
              data: cumulative,
              borderColor: "rgba(128,0,128,0.6)", // faded purple
              backgroundColor: "transparent",
              borderDash: [5, 5],
              fill: false,
              tension: 0.3,
            },
          ],
        },
        options: {
          responsive: true,
          scales: {
            x: { title: { display: true, text: "Date" } },
            y: {
              beginAtZero: true,
              title: { display: true, text: "Credits" },
            },
          },
        },
      });

      // 4) Summary line under commission chart
      const totalComm = commissionAmounts.reduce((s, v) => s + v, 0);
      const container = document.getElementById("commissionChart").parentNode;
      const p = document.createElement("p");
      p.style.marginTop = "0.5em";
      p.style.fontWeight = "bold";
      p.textContent = `Total Commissions: ${totalComm} credits`;
      container.appendChild(p);
    })
    .catch((error) => {
      console.error("Failed to load admin chart data:", error);
    });
});
