document.addEventListener("DOMContentLoaded", async function () {
    const container = document.getElementById("ride-history-container");

    try {
        const response = await fetch("/ride-history");
        const data = await response.json();

        if (data.error) {
            container.innerHTML = `<p class="text-red-500">${data.error}</p>`;
            return;
        }

        if (data.rides.length === 0) {
            container.innerHTML = `<p class="text-gray-600">No past rides found.</p>`;
            return;
        }

        container.innerHTML = data.rides.map(ride => `
            <div class="bg-white p-4 shadow-md rounded-lg">
                <p class="font-semibold">${ride.pickup_location} → ${ride.dropoff_location}</p>
                <p class="text-gray-500 text-sm">${ride.status} - ${ride.created_at}</p>
            </div>
        `).join("");
    } catch (error) {
        container.innerHTML = `<p class="text-red-500">Error loading ride history.</p>`;
    }
});
