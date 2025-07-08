// public/js/admin-charts.js

document.addEventListener("DOMContentLoaded", function () {
  // Fetch the data once the page is loaded
  fetch("/admin/graph-data")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not OK");
      }
      return response.json();
    })
    .then((data) => {
      // 1) Carpools per Day (Bar Chart)
      const carpoolDates = data.carpoolsPerDay.map((d) => d.date);
      const carpoolCounts = data.carpoolsPerDay.map((d) => d.count);

      const carpoolsCtx = document
        .getElementById("carpoolsChart")
        .getContext("2d");
      new Chart(carpoolsCtx, {
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

      // 2) Driver Net Payouts per Day (Line Chart)
      const payoutDates = data.creditsPerDay.map((d) => d.date);
      const payoutAmounts = data.creditsPerDay.map((d) => d.credits_earned);

      const payoutsCtx = document
        .getElementById("payoutsChart")
        .getContext("2d");
      new Chart(payoutsCtx, {
        type: "line",
        data: {
          labels: payoutDates,
          datasets: [
            {
              label: "Driver Net Payouts",
              data: payoutAmounts,
              borderColor: "rgba(59,130,246,1)",
              backgroundColor: "rgba(59,130,246,0.1)",
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

      // 3) Platform Commission per Day (Line Chart)
      const commDates = data.commissionPerDay.map((d) => d.date);
      const commAmounts = data.commissionPerDay.map((d) => d.commission_earned);

      const commissionCtx = document
        .getElementById("commissionChart")
        .getContext("2d");
      new Chart(commissionCtx, {
        type: "line",
        data: {
          labels: commDates,
          datasets: [
            {
              label: "Platform Commission",
              data: commAmounts,
              borderColor: "rgba(220,38,38,1)",
              backgroundColor: "rgba(220,38,38,0.1)",
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
    })
    .catch((error) => {
      console.error("Failed to load admin chart data:", error);
    });
}); // <-- closes addEventListener callback
