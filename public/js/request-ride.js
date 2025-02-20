document.addEventListener("DOMContentLoaded", function () {
    document.getElementById("rideRequestForm").addEventListener("submit", function (event) {
        event.preventDefault(); // ✅ Prevent page refresh

        const pickup = document.getElementById("pickup_location").value;
        const dropoff = document.getElementById("dropoff_location").value;

        fetch("/request-ride", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                pickup_location: pickup,
                dropoff_location: dropoff
            })
        })
        .then(response => response.json()) // ✅ Convert response to JSON
        .then(data => {
            console.log("🔍 Server Response:", data); // ✅ Debugging
            if (data.message) {
                alert("✅ " + data.message); // ✅ Show success
                window.location.href = "/menu"; // ✅ Redirect after success
            } else {
                alert("❌ " + (data.error || "Ride Request Failed")); // ✅ Show error message
            }
        })
        // .catch(error => {
        //     console.error("🔥 Request Error:", error);
        //     alert("❌ Network Error: Ride Request Failed");
        // });
    });
});
