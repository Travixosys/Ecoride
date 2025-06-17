// public/js/register.js

document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("registerForm");

  form.addEventListener("submit", async function (event) {
    event.preventDefault();

    // 1) Build your payload exactly as before
    const roleDropdown = document.getElementById("role").value;
    const role =
      roleDropdown.toLowerCase() === "passenger"
        ? "user"
        : roleDropdown.toLowerCase();

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

    console.log("Final Role Sent:", payload.role);

    // 2) Grab the CSRF keys & values from the hidden inputs
    //    (Assuming your Twig used the default names: csrf_name & csrf_value)
    const nameKeyInput = form.elements["csrf_name"];
    const valueKeyInput = form.elements["csrf_value"];

    payload[nameKeyInput.name] = nameKeyInput.value;
    payload[valueKeyInput.name] = valueKeyInput.value;

    try {
      // 3) Send JSON *and* include cookies so Slim-Csrf sees it
      const response = await fetch("/register", {
        method: "POST",
        credentials: "include", // send session & CSRF cookie
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      // 4) Parse and handle the JSON response (or error text)
      const text = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (e) {
        data = text;
      }

      if (!response.ok) {
        // server will reply "Failed CSRF check!" or your own error
        alert("Registration failed: " + (data.error || data));
        return;
      }

      alert("Registration successful!");
      window.location.href = "/login";
    } catch (error) {
      console.error("Registration Error:", error);
      alert("Something went wrong. Try again!");
    }
  });
});
