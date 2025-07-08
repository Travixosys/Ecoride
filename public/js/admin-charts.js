
document.addEventListener("DOMContentLoaded", function () {
    fetch("/admin/graph-data")
        .then(response => response.json())
        .then(data => {
            const carpoolDates = data.carpoolsPerDay.map(item => item.date);
            const carpoolCounts = data.carpoolsPerDay.map(item => item.count);

            const creditDates = data.creditsPerDay.map(item => item.date);
            const creditEarnings = data.creditsPerDay.map(item => item.credits_earned);

            new Chart(document.getElementById("carpoolsChart"), {
                type: "bar",
                data: {
                    labels: carpoolDates,
                    datasets: [{
                        label: "Carpools per Day",
                        data: carpoolCounts,
                        backgroundColor: "rgba(34,197,94,0.6)"
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });

            new Chart(document.getElementById("creditsChart"), {
                type: "line",
                data: {
                    labels: creditDates,
                    datasets: [{
                        label: "Credits Earned",
                        data: creditEarnings,
                        borderColor: "rgba(59,130,246,1)",
                        backgroundColor: "rgba(59,130,246,0.1)",
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        })
        .catch(error => {
            console.error("Failed to load chart data:", error);
        });
});
