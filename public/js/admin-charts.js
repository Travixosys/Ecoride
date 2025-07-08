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
      const carpoolDates = data.carpoolsPerDay.map((item) => item.date);
      const carpoolCounts = data.carpoolsPerDay.map((item) => item.count);

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
            y: { beginAtZero: true },
          },
        },
      });

      // 2) Driver Net Payouts per Day (Line chart)
      //    creditsPerDay now holds the net amount paid to drivers
      const payoutDates = data.creditsPerDay.map((item) => item.date);
      const payoutAmounts = data.creditsPerDay.map(
        (item) => item.credits_earned
      );

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
            y: { beginAtZero: true },
          },
        },
      });

      // 3) Platform Commission per Day (Line chart)
      //    commissionPerDay holds the 2-credit cut × seats
      const commissionDates = data.commissionPerDay.map((item) => item.date);
      const commissionAmounts = data.commissionPerDay.map(
        (item) => item.commission_earned
      );

      new Chart(document.getElementById("commissionChart").getContext("2d"), {
        type: "line",
        data: {
          labels: commissionDates,
          datasets: [
            {
              label: "Platform Commission",
              data: commissionAmounts,
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
            y: { beginAtZero: true },
          },
        },
      });
    })
    .catch((error) => {
      console.error("Failed to load chart data:", error);
    });
});
