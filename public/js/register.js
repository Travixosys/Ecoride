// public/js/register.js

// Wait for the DOM to be fully loaded
document.addEventListener("DOMContentLoaded", function () {
  // Grab the registration form
  var form = document.getElementById("registerForm");

  form.addEventListener("submit", function (event) {
    event.preventDefault();

    // Build a FormData object from the form (includes CSRF tokens)
    var formData = new FormData(form);

    // Normalize the role select
    var rawRole = document.getElementById("role").value.toLowerCase();
    var normalizedRole = rawRole === "passenger" ? "user" : rawRole;
    formData.set("role", normalizedRole);

    // Send the form as multipart/form-data
    fetch("/register", {
      method: "POST",
      credentials: "include", // include cookies for CSRF
      body: formData,
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (text) {
        if (
          !text ||
          text.indexOf("Failed CSRF") === 0 ||
          text.indexOf("error") > -1
        ) {
          // If the server returned an error string, show it
          alert("Registration failed: " + text);
        } else {
          // Success → redirect or inform
          alert("Registration successful! You can now log in.");
          window.location.href = "/login";
        }
      })
      .catch(function (err) {
        console.error("Registration Error:", err);
        alert("An unexpected error occurred. Please try again.");
      });
  });
});
