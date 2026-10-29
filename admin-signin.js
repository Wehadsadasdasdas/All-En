document.querySelector(".logo").addEventListener("click", function () {
  window.location.href = "index.php";
});

// Close banner functionality
function closeBanner() {
  document.querySelector(".promo-banner").style.display = "none";
}

// Form submission handling
document.querySelector("form").addEventListener("submit", function (e) {
  const form = this;
  const username = form.querySelector('input[type="text"]').value.trim();
  const password = form.querySelector('input[type="password"]').value.trim();

  if (!username || !password) {

    e.preventDefault(); // hanya prevent kalau kosong
    alert("Please fill in all fields");
  }
});


// Add hover effects to navigation items
const navItems = document.querySelectorAll(".nav-item");
navItems.forEach((item) => {
  item.addEventListener("mouseenter", function () {
    this.style.opacity = "0.7";
  });
  item.addEventListener("mouseleave", function () {
    this.style.opacity = "1";
  });
});

// Add hover effects to social icons
const socialIcons = document.querySelectorAll(".social-icon");
socialIcons.forEach((icon) => {
  icon.addEventListener("mouseenter", function () {
    this.style.transform = "scale(1.1)";
  });
  icon.addEventListener("mouseleave", function () {
    this.style.transform = "scale(1)";
  });
});

const cartIcon = document.querySelector(".cart-icons");
const cartContent = document.getElementById("cartContent");

// Saat mouse masuk ke icon, tampilkan cart
cartIcon.addEventListener("click", (e) => {
  e.stopPropagation(); // agar tidak ikut tertangkap oleh event document
  cartContent.classList.toggle("active");
});

// Saat mouse keluar dari kotak cart, sembunyikan
cartContent.addEventListener("mouseleave", () => {
  cartContent.classList.remove("active");
});

//dropdown
const dropdown = document.querySelector(".dropdown");
const dropdownLink = dropdown.querySelector(".nav-item");

dropdownLink.addEventListener("click", function (e) {
  e.preventDefault();
  dropdown.classList.toggle("open");
});

document.addEventListener("click", function (e) {
  // .contains() akan true kalau klik ada di dalam elemen dropdown
  if (!dropdown.contains(e.target)) {
    dropdown.classList.remove("open");
  }
});
