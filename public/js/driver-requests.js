document.addEventListener("DOMContentLoaded", () => {
    const forms = document.querySelectorAll("form[action^='/driver/']");

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
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(requestData)
                });

                const result = await response.json();

                if (response.ok) {
                    let actionType = "";
                    if (form.action.includes("accept")) actionType = "accepted";
                    else if (form.action.includes("complete")) actionType = "completed";
                    else if (form.action.includes("cancel")) actionType = "cancelled";

                    alert(`Ride successfully ${actionType}.`);
                    location.reload();
                } else {
                    alert("Failed: " + (result.message || result.error || "Unknown error"));
                }

            } catch (error) {
                console.error("AJAX error:", error);
                alert("A network error occurred.");
            }
        });
    });
});
