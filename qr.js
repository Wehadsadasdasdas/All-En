function closeBanner() {
  document.querySelector(".promo-banner").style.display = "none";
}

// // Shop dropdown functionality
// document.addEventListener("DOMContentLoaded", function () {
//   const shopLink = document.querySelector(".nav-link");

  

//   // Search functionality
//   const searchInput = document.querySelector(".search-input");

//   searchInput.addEventListener("keypress", function (e) {
//     if (e.key === "Enter") {
//       alert("Searching for: " + searchInput.value);
//     }
//   });

//   // Cart and profile icons
//   const cartIcon = document.querySelector(".cart-icon");
//   const profileIcon = document.querySelector(".profile-icon");

  

//   // Footer links
//   const footerLinks = document.querySelectorAll(".footer-link");

//   footerLinks.forEach((link) => {
//     link.addEventListener("click", function (e) {
//       e.preventDefault();
//       alert("Navigating to: " + link.textContent);
//     });
//   });

//   // Social media icons
//   const socialIcons = document.querySelectorAll(".social-icon");

//   socialIcons.forEach((icon) => {
//     icon.addEventListener("click", function () {
//       alert("Opening social media page");
//     });
//   });
// });

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
});
