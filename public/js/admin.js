document.addEventListener("DOMContentLoaded", function () {
  // Update User Role & License Number
  document.querySelectorAll(".update-user").forEach((button) => {
    button.addEventListener("click", async function () {
      const userId = this.dataset.userId;
      const role = document.querySelector(
        `.role-select[data-user-id='${userId}']`
      ).value;
      const licenseInput = document.querySelector(
        `.license-input[data-user-id='${userId}']`
      );

      let license = null;
      if (role === "driver") {
        license = licenseInput.value.trim();
      }

      const response = await fetch(`/admin/update-user/${userId}`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ role, license_number: license }),
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

  // Search Users - Both table and mobile cards
  const searchInput = document.getElementById("searchUser");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      const searchTerm = this.value.toLowerCase();

      // Filter table rows
      document.querySelectorAll("#userTable tr").forEach((row) => {
        const name = row.children[0]?.textContent.toLowerCase() || "";
        const email = row.children[1]?.textContent.toLowerCase() || "";
        row.style.display =
          name.includes(searchTerm) || email.includes(searchTerm) ? "" : "none";
      });

      // Filter mobile card view
      document.querySelectorAll(".sm\\:hidden .bg-gray-50").forEach((card) => {
        const name =
          card.querySelector("p:nth-of-type(1)")?.textContent.toLowerCase() ||
          "";
        const email =
          card.querySelector("p:nth-of-type(2)")?.textContent.toLowerCase() ||
          "";
        card.style.display =
          name.includes(searchTerm) || email.includes(searchTerm)
            ? "block"
            : "none";
      });
    });
  }

  // Enable/Disable license field on role change
  document.querySelectorAll(".role-select").forEach((select) => {
    select.addEventListener("change", function () {
      const userId = this.dataset.userId;
      const licenseInput = document.querySelector(
        `.license-input[data-user-id='${userId}']`
      );
      if (licenseInput) {
        if (this.value === "driver") {
          licenseInput.disabled = false;
        } else {
          licenseInput.value = "";
          licenseInput.disabled = true;
        }
      }
    });
  });
});
