// public/js/register.js

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerForm");

  form.addEventListener("submit", async function (event) {
    event.preventDefault();

    // Build payload as before
    const rawRole = document.getElementById("role").value.toLowerCase();
    const role = rawRole === "passenger" ? "user" : rawRole;
    const payload = {
      name: document.getElementById("name").value,
      email: document.getElementById("email").value,
      password: document.getElementById("password").value,
      phone_number: document.getElementById("phone_number").value,
      role: role,
    };
    if (role === "driver") {
      payload.make = document.getElementById("make").value || null;
      payload.model = document.getElementById("model").value || null;
      payload.year = document.getElementById("year").value || null;
      payload.plate = document.getElementById("plate").value || null;
      payload.seats = document.getElementById("seats").value || null;
      payload.energy_type =
        document.getElementById("energy_type").value || null;
    }

    console.group("Register Payload");
    console.log(payload);
    console.groupEnd();

    try {
      const response = await fetch("/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      // Log status & headers
      console.log(
        `%cResponse: ${response.status} ${response.statusText}`,
        "color: blue;"
      );
      response.headers.forEach((v, k) => console.log(`Header: ${k}: ${v}`));

      // Try to parse JSON, else fall back to text
      let data, raw;
      try {
        data = await response.json();
      } catch (e) {
        raw = await response.text();
        console.warn("Failed to parse JSON, raw response:", raw);
      }

      if (response.ok) {
        console.log("%cRegistration succeeded:", "color: green;", data || raw);
        alert("Registration successful!\nYou may now log in.");
        window.location.href = "/login";
      } else {
        // Build a user-friendly error
        const errMsg = data?.error || raw || `HTTP ${response.status}`;
        console.error("Registration failed:", errMsg);
        alert(
          `Registration failed\nStatus: ${response.status}\nDetails: ${errMsg}`
        );
      }
    } catch (networkError) {
      console.error("Network or JS error during registration:", networkError);
      alert("Network error. Please check your connection and try again.");
    }
  });
});
