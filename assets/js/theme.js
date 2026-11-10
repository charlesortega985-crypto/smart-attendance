// assets/js/theme.js
function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    const icon = document.getElementById("themeIcon");
    const text = document.getElementById("themeText");
    
    if (icon) {
        if (theme === "dark") {
            icon.className = "bi bi-sun-fill text-warning";
        } else {
            icon.className = "bi bi-moon-stars-fill";
        }
    }

    if (text) {
        text.textContent = theme === "dark" ? "Light Mode" : "Dark Mode";
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    localStorage.setItem("theme", newTheme);
    applyTheme(newTheme);
}

document.addEventListener("DOMContentLoaded", () => {
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);
});