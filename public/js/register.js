// public/js/register.js
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("registerForm");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Build FormData from the <form>, so it includes your CSRF hidden inputs
    const formData = new FormData(form);

    // Grab the role directly from the select element
    const roleDropdown = document.getElementById("role").value;

    // Compute the normalized role
    const role =
      roleDropdown.toLowerCase() === "passenger"
        ? "user"
        : roleDropdown.toLowerCase();

    // Ensure the payload has the normalized role
    formData.set("role", role);

    // Convert FormData → plain object for JSON
    const payload = {};
    formData.forEach((value, key) => {
      // formData.get() returns strings for all fields, which is fine
      payload[key] = value;
    });

    try {
      const res = await fetch("/register", {
        method: "POST",
        credentials: "include", // ← send cookies (CSRF token)
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(payload),
      });

      const data = await res.json();
      if (res.ok) {
        alert("Registration successful!");
        window.location.href = "/login";
      } else {
        alert("Error: " + (data.error || JSON.stringify(data)));
      }
    } catch (err) {
      console.error("Registration Error:", err);
      alert("Something went wrong. Try again!");
    }
  });
});
