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
    const iconEl = themeToggle.querySelector(".theme-toggle-icon");
    const textEl = themeToggle.querySelector(".theme-toggle-text");

    function setThemeLabel(isDark) {
      if (iconEl) {
        iconEl.classList.toggle("bi-moon-fill", !isDark);
        iconEl.classList.toggle("bi-sun-fill", isDark);
      }

      if (textEl) {
        textEl.textContent = isDark ? lightLabel : nightLabel;
      } else {
        themeToggle.textContent = isDark ? lightLabel : nightLabel;
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
