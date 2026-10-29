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

// === Search Input + AJAX Suggestion ===
const input = document.getElementById("searchInput");
const suggestionBox = document.getElementById("search-suggestions");

if (input) {
  input.addEventListener("input", function () {
    const keyword = this.value.trim();

    if (keyword.length === 0) {
      suggestionBox.style.display = "none";
      return;
    }

    fetch(`index.php?ajax_search=${encodeURIComponent(keyword)}`)
      .then((res) => res.json())
      .then((data) => {
        suggestionBox.innerHTML = "";

        if (data.length === 0) {
          suggestionBox.innerHTML =
            "<li style='padding: 10px;'>No results</li>";
        } else {
          data.forEach((item) => {
            const li = document.createElement("li");
            li.textContent = item.name;
            li.style.padding = "10px";
            li.style.cursor = "pointer";
            li.addEventListener("click", () => {
              localStorage.setItem(
                "product-clicked",
                JSON.stringify({
                  id: item.id,
                  name: item.name,
                  image: item.image_link, // ini harus ada dari PHP
                })
              );
              window.location.href = "product_detail.php";
            });
            suggestionBox.appendChild(li);
          });
        }

        suggestionBox.style.display = "block";
      })
      .catch((err) => {
        console.error("Search error:", err);
        suggestionBox.style.display = "none";
      });
  });
}

//saat click di luar input, sembunyikan suggestion box
document.addEventListener("click", (e) => {
  if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
    suggestionBox.style.display = "none";
  }
}
);
