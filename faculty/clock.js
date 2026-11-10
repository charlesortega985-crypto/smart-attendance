// Real-Time Clock Function
function updateClock() {
    const now = new Date();
    
    let hours = now.getHours();
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; 
    const formattedTime = `${String(hours).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
    
    const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
    const formattedDate = now.toLocaleDateString('en-US', options);
    
    const clockEl = document.getElementById('liveClock');
    const dateEl = document.getElementById('liveDate');
    
    if (clockEl) clockEl.innerText = formattedTime;
    if (dateEl) dateEl.innerText = formattedDate;
}

// Paganahin ang orasan bawat segundo
setInterval(updateClock, 1000);

document.addEventListener("DOMContentLoaded", () => {
    updateClock();
    
    // I-load ang saved theme sa localStorage
    const savedTheme = localStorage.getItem("theme") || "light";
    applyTheme(savedTheme);

    // QR Code Generation gamit ang variable na galing sa PHP
    if (typeof studentId !== 'undefined' && document.getElementById("qrcode")) {
        new QRCode(document.getElementById("qrcode"), {
            text: studentId,
            width: 150,
            height: 150,
            colorDark : "#4f46e5",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    }
});

// Function para sa Dark / Light Mode
function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    const icon = document.getElementById("themeIcon");
    const text = document.getElementById("themeText"); // Opsyonal na kung may text label
    
    if (icon) {
        if (theme === "dark") {
            icon.className = "bi bi-sun-fill text-warning";
        } else {
            icon.className = "bi bi-moon-stars-fill";
        }
    }

    if (text) {
        if (theme === "dark") {
            text.textContent = "Light Mode";
        } else {
            text.textContent = "Dark Mode";
        }
    }
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute("data-theme") || "light";
    const newTheme = currentTheme === "dark" ? "light" : "dark";
    localStorage.setItem("theme", newTheme);
    applyTheme(newTheme);
}