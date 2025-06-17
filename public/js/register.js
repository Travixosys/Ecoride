// public/js/register.js

document.addEventListener("DOMContentLoaded", function () {
  var form = document.getElementById("registerForm");

  form.addEventListener("submit", async function (e) {
    e.preventDefault();

    // 1) Pull CSRF keys & values from the form's data-attributes
    var nameKey = form.dataset.csrfNamekey;
    var valueKey = form.dataset.csrfValuekey;
    var nameVal = form.dataset.csrfName;
    var valueVal = form.dataset.csrfValue;

    // 2) Build the JSON payload exactly as in your local version
    var rawRole = document.getElementById("role").value.toLowerCase();
    var role = rawRole === "passenger" ? "user" : rawRole;

    var payload = {
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

    // 3) Inject the CSRF fields into the payload
    payload[nameKey] = nameVal;
    payload[valueKey] = valueVal;

    console.log("Final Role Sent:", payload.role);
    console.log("CSRF Fields:", nameKey, nameVal, valueKey, valueVal);

    try {
      // 4) Send as JSON with cookies so Slim-Csrf sees the tokens
      var res = await fetch("/register", {
        method: "POST",
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      var text = await res.text();

      if (!res.ok) {
        alert("Registration failed: " + text);
        return;
      }

      alert("Registration successful!");
      window.location.href = "/login";
    } catch (err) {
      console.error("Registration Error:", err);
      alert("Unexpected error. Try again.");
    }
  });
});
