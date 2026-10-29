document.querySelector(".logo").addEventListener("click", function () {
  window.location.href = "index.php";
});

// Search functionality
document
  .querySelector(".search-bar input")
  .addEventListener("keypress", function (e) {
    if (e.key === "Enter") {
      alert("Searching for: " + this.value);
    }
  });

//image with class remove-item if clicked will go to warranty.php
    const removeItemButtons = document.querySelectorAll('.remove-item');
    removeItemButtons.forEach(button => {
        button.addEventListener('click', () => {
            window.location.href = 'warranty.php';
        });
    });
    
    //image with class edit-item if clicked will go to refund.php
    const editItemButtons = document.querySelectorAll('.edit-item');
    editItemButtons.forEach(button => {
        button.addEventListener('click', () => {
            window.location.href = 'refund.php';
        });
    });

// Update total when items are removed
function updateTotal() {
  const items = document.querySelectorAll(".product-item");
  if (items.length === 0) {
    document.querySelector(".status-value:last-child").textContent = "$0";
  }
}

// Footer social icons
document.querySelectorAll(".social-icon").forEach(function (icon) {
  icon.addEventListener("click", function () {
    const platform = this.alt;
    alert(`Redirecting to ${platform}`);
  });
});

// Footer links
document.querySelectorAll(".footer-link").forEach(function (link) {
  link.addEventListener("click", function (e) {
    e.preventDefault();
    alert(`Navigating to: ${this.textContent}`);
  });
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

//when card icon is clicked, redirect to cart page
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
