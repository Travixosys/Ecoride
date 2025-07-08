// public/js/admin-chart.js

document.addEventListener("DOMContentLoaded", function () {
  fetch("/admin/graph-data")
    .then((response) => {
      if (!response.ok) {
        throw new Error("Network response was not OK");
      }
      return response.json();
    })
    .then((data) => {
      // 1) Carpools per Day (Bar chart)
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

      // 2) Driver Net Payouts per Day (Line chart)
      const payoutDates = data.creditsPerDay.map((d) => d.date);
      const payoutAmounts = data.creditsPerDay.map((d) => d.credits_earned);

      new Chart(document.getElementById("creditsChart").getContext("2d"), {
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
            y: { beginAtZero: true, title: { display: true, text: "Credits" } },
          },
        },
      });

      // 3) Platform Commission per Day (Line chart + cumulative total)
      const commissionDates = data.commissionPerDay.map((d) => d.date);
      const commissionAmounts = data.commissionPerDay.map(
        (d) => d.commission_earned
      );

      // Calculate cumulative total commissions
      const cumulativeCommissions = [];
      commissionAmounts.reduce((sum, curr) => {
        sum += curr;
        cumulativeCommissions.push(sum);
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
              borderColor: "rgba(220,38,38,1)",
              backgroundColor: "rgba(220,38,38,0.1)",
              fill: true,
              tension: 0.3,
            },
            {
              label: "Total Commission",
              data: cumulativeCommissions,
              borderColor: "rgba(220,38,38,0.6)",
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
            y: { beginAtZero: true, title: { display: true, text: "Credits" } },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Failed to load admin chart data:", error);
    });
});
