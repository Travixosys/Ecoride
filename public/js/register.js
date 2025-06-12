document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("registerForm")
    .addEventListener("submit", async function (event) {
      event.preventDefault();

      const roleDropdown = document.getElementById("role").value;
      let role =
        roleDropdown.toLowerCase() === "passenger"
          ? "user"
          : roleDropdown.toLowerCase();

      let formData = {
        name: document.getElementById("name").value,
        email: document.getElementById("email").value,
        password: document.getElementById("password").value,
        phone_number: document.getElementById("phone_number").value,
        role: role, // Ensure correct role is assigned
      };

      if (role === "driver") {
        formData.make = document.getElementById("make").value || null;
        formData.model = document.getElementById("model").value || null;
        formData.year = document.getElementById("year").value || null;
        formData.plate = document.getElementById("plate").value || null;
        formData.seats = document.getElementById("seats").value || null;
        formData.energy_type =
          document.getElementById("energy_type").value || null; // Add this line
      }

      console.log("Final Role Sent:", formData.role); // 

      try {
        const response = await fetch("http://localhost:8000/register", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData),
        });

        const data = await response.json();
        if (response.ok) {
          alert("Registration successful!");
          window.location.href = "/login";
        } else {
          alert("Error: " + data.error);
        }
      } catch (error) {
        console.error("Registration Error:", error);
        alert("Something went wrong. Try again!");
      }
    });
});
