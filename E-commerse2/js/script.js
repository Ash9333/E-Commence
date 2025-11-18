// Add any interactive features here
document.addEventListener("DOMContentLoaded", function () {
  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert");
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 300);
    }, 5000);
  });

  const themeToggle = document.getElementById("theme-toggle");
  const savedTheme = localStorage.getItem("theme");

  if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");
    if (themeToggle) {
      themeToggle.textContent = "☀️ Light";
    }
  } else {
    if (themeToggle) {
      themeToggle.textContent = "🌙 Night";
    }
  }

  if (themeToggle) {
    themeToggle.addEventListener("click", function () {
      const isDark = document.body.classList.toggle("dark-mode");
      if (isDark) {
        localStorage.setItem("theme", "dark");
        themeToggle.textContent = "☀️ Light";
      } else {
        localStorage.setItem("theme", "light");
        themeToggle.textContent = "🌙 Night";
      }
    });
  }
});
