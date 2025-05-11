document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form[action^='/driver/accept-ride/']");

    forms.forEach((form) => {
        form.addEventListener("submit", async (event) => {
            event.preventDefault();

            const formData = new FormData(form);
            const requestData = {};

            formData.forEach((value, key) => {
                requestData[key] = value;
            });

            try {
                const response = await fetch(form.action, {
                    method: "PUT", // ✅ Must match your Slim route
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(requestData),
                });

                if (response.ok) {
                    const result = await response.json();
                    alert("Ride accepted successfully!");
                    location.reload(); // Refresh to update the list
                } else {
                    const error = await response.json();
                    alert("Failed to accept ride: " + error.message);
                }
            } catch (err) {
                console.error("Error accepting ride:", err);
                alert("An error occurred while accepting the ride.");
            }
        });
    });
});
