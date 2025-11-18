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

  const initialIsDark = savedTheme === "dark";
  if (initialIsDark) {
    document.body.classList.add("dark-mode");
  }

  if (themeToggle) {
    const nightLabel = themeToggle.dataset.labelNight || "Night";
    const lightLabel = themeToggle.dataset.labelLight || "Light";

    function setThemeLabel(isDark) {
      if (isDark) {
        themeToggle.textContent = "☀️ " + lightLabel;
      } else {
        themeToggle.textContent = "🌙 " + nightLabel;
      }
    }

    setThemeLabel(initialIsDark);

    themeToggle.addEventListener("click", function () {
      const isDark = document.body.classList.toggle("dark-mode");
      localStorage.setItem("theme", isDark ? "dark" : "light");
      setThemeLabel(isDark);
    });
  }
});
