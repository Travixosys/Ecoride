// public/js/admin-chart.js

document.addEventListener("DOMContentLoaded", function () {
  fetch("/admin/graph-data")
    .then((response) => {
      if (!response.ok) throw new Error("Network response was not OK");
      return response.json();
    })
    .then((data) => {
      //
      // 1) BAR CHART → Carpools per Day (green)
      //
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

      //
      // 2) LINE CHART → Driver Net vs Platform Commission
      //
      // Build a unified list of dates (so both series align)
      const allDates = Array.from(
        new Set([
          ...data.driverNetPerDay.map((d) => d.date),
          ...data.commissionPerDay.map((d) => d.date),
        ])
      ).sort();

      // Map each date to its value (or 0)
      const netMap = Object.fromEntries(
        data.driverNetPerDay.map((d) => [d.date, d.driver_net])
      );
      const commMap = Object.fromEntries(
        data.commissionPerDay.map((d) => [d.date, d.commission_earned])
      );

      // Map each date to its numeric value (or 0)
      const netSeries = allDates.map((d) => Number(netMap[d] || 0));
      const commSeries = allDates.map((d) => Number(commMap[d] || 0));

      new Chart(document.getElementById("creditsChart").getContext("2d"), {
        type: "line",
        data: {
          labels: allDates,
          datasets: [
            {
              label: "Driver Net Payouts",
              data: netSeries,
              borderColor: "rgba(245,130,32,1)", // orange
              backgroundColor: "rgba(245,130,32,0.1)", // light orange fill
              fill: true,
              tension: 0.3,
            },
            {
              label: "Platform Commission",
              data: commSeries,
              borderColor: "rgba(128,0,128,1)", // purple
              backgroundColor: "rgba(128,0,128,0.1)", // light purple fill
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

      //
      // 3) SUMMARY LINES under the line chart
      //
      const totalNet = netSeries.reduce((sum, v) => sum + v, 0);
      const totalComm = commSeries.reduce((sum, v) => sum + v, 0);

      const container = document.getElementById("creditsChart").parentNode;
      const netP = document.createElement("p");
      const commP = document.createElement("p");

      netP.style.marginTop = "0.5em";
      netP.style.fontWeight = "bold";
      netP.textContent = `Total Driver Net Payouts: ${totalNet} credits`;

      commP.style.marginTop = "0.25em";
      commP.style.fontWeight = "bold";
      commP.textContent = `Total Platform Commissions: ${totalComm} credits`;

      container.appendChild(netP);
      container.appendChild(commP);
    })
    .catch((error) => {
      console.error("Failed to load admin chart data:", error);
    });
});
