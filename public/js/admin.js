document.addEventListener("DOMContentLoaded", function () {
    // ✅ Update User Role & License Number
    document.querySelectorAll(".update-user").forEach(button => {
        button.addEventListener("click", async function () {
            const userId = this.dataset.userId;
            const role = document.querySelector(`.role-select[data-user-id='${userId}']`).value;
            const licenseInput = document.querySelector(`.license-input[data-user-id='${userId}']`);

            let license = null;
            if (role === "driver") {
                license = licenseInput.value.trim();
            }

            const response = await fetch(`/admin/update-user/${userId}`, {
                method: "PUT",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ role, license_number: license })
            });

            const data = await response.json();
            alert(data.message || "User updated successfully");

            if (role !== "driver") {
                licenseInput.value = "";
                licenseInput.disabled = true;
            } else {
                licenseInput.disabled = false;
            }
        });
    });

    // ✅ Search Users
    document.getElementById("searchUser").addEventListener("input", function () {
        const searchTerm = this.value.toLowerCase();
        document.querySelectorAll("#userTable tr").forEach(row => {
            const name = row.children[0].textContent.toLowerCase();
            row.style.display = name.includes(searchTerm) ? "" : "none";
        });
    });

    // ✅ Handle Role Change (Enable License Field for Drivers)
    document.querySelectorAll(".role-select").forEach(select => {
        select.addEventListener("change", function () {
            const userId = this.dataset.userId;
            const licenseInput = document.querySelector(`.license-input[data-user-id='${userId}']`);
            if (this.value === "driver") {
                licenseInput.disabled = false;
            } else {
                licenseInput.value = "";
                licenseInput.disabled = true;
            }
        });
    });
});
