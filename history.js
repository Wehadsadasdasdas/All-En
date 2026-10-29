// Search functionality
const searchInput = document.querySelector(".search-bar input");
searchInput.addEventListener("keypress", function (e) {
  if (e.key === "Enter") {
    alert("Searching for: " + searchInput.value);
  }
});

document.querySelector(".logo").addEventListener("click", function () {
  window.location.href = "index.php";
});

// Order card hover effect
const orderCards = document.querySelectorAll(".order-card");
orderCards.forEach((card) => {
  card.addEventListener("mouseenter", function () {
    this.style.backgroundColor = "#f9f9f9";
    this.style.cursor = "pointer";
  });

  card.addEventListener("mouseleave", function () {
    this.style.backgroundColor = "transparent";
  });

  card.addEventListener("click", function () {
    window.location.href = 'order_detail.php';
  });
});

const cartIcon = document.querySelector(".cart-icon");
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

const header = document.querySelector(".header");
if (header) {
  document.addEventListener("click", (e) => {
    if (!header.contains(e.target) && !cartIcon.contains(e.target)) {
      header.classList.remove("expand");
    }
  });
}

//when i click outside the cart icon, the cart will close
document.addEventListener("click", function (event) {
  if (!cartIcon.contains(event.target) && !cartContent.contains(event.target)) {
    cartContent.classList.remove("active");
  }
});

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


