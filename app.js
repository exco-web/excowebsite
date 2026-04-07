const menu = document.querySelector("#mobile-menu");
const menuLinks = document.querySelector(".navbar__menu");

function closeMenu() {
    menu.classList.remove("is-active");
    menuLinks.classList.remove("active");
}

menu.addEventListener("click", function(e) {
    e.stopPropagation();
    menu.classList.toggle("is-active");
    menuLinks.classList.toggle("active");
});

// Close when clicking a nav link
menuLinks.querySelectorAll(".navbar__links").forEach(link => {
    link.addEventListener("click", closeMenu);
});

// Close when clicking outside
document.addEventListener("click", function(e) {
    if (menuLinks.classList.contains("active") && !menuLinks.contains(e.target)) {
        closeMenu();
    }
});

// Make user dropdown work on tap in mobile menu
const dropbtn = document.querySelector(".dropbtn");
const dropdownContent = document.querySelector(".dropdown-content");
if (dropbtn && dropdownContent) {
    dropbtn.addEventListener("click", function(e) {
        e.stopPropagation();
        const isOpen = dropdownContent.style.display === "block";
        dropdownContent.style.display = isOpen ? "none" : "block";
    });

    document.addEventListener("click", function() {
        // Only hide on desktop where the dropbtn is visible
        if (window.getComputedStyle(dropbtn).display !== "none") {
            dropdownContent.style.display = "";
        }
    });
}

// Scroll handler — only runs on pages that have .highlight
document.addEventListener("scroll", function() {
    const highlight = document.querySelector(".highlight");
    if (!highlight) return;
    highlight.style.opacity = window.scrollY >= 750 ? "1" : "0";
});
