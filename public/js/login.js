document.getElementById("login-form").addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("email").value;
    const password = document.getElementById("password").value;

    try {
        console.log("📨 Sending:", { email, password });

        const response = await fetch("/login", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password })
        });

        const data = await response.json();

        console.log("📩 Response Status:", response.status);
        console.log("📩 Response Data:", data);

        if (!response.ok) {
            throw new Error(data.error || "Login failed");
        }

        // ✅ Store user session in local storage
        localStorage.setItem("user", JSON.stringify(data.user));

        alert("✅ Login Successful! Redirecting...");

        // ✅ Redirect Based on Role
        if (data.user.role === "driver") {
            window.location.href = "/menu";
        } else if (data.user.role === "/") {
            window.location.href = "/admin";
        } else {
            window.location.href = "/";
        }

    } catch (error) {
        console.error("❌ Login Error:", error.message);
        document.getElementById("login-error").textContent = error.message;
        document.getElementById("login-error").classList.remove("hidden");
    }
});
