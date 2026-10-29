//logo click will redirect to home page
document.querySelector(".logo").addEventListener("click", function () {
  window.location.href = "index.php";
});

// Form validation
const checkoutBtn = document.getElementById("checkoutBtn");
const formInputs = document.querySelectorAll(".form-control");

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

checkoutBtn.addEventListener("click", function () {
  let isValid = true;

  formInputs.forEach((input) => {
    if (!input.value.trim()) {
      input.style.boxShadow = "0 0 0 2px rgba(255, 0, 0, 0.3)";
      isValid = false;
    } else {
      input.style.boxShadow = "";
    }
  });

  if (isValid) {
    // Ambil payment_method_id dari radio button
    const paymentMethod = document.querySelector(
      'input[name="paymentMethod"]:checked'
    );
    const payment_method_id = paymentMethod ? paymentMethod.value : null;
    // Ambil address
    const address = document.getElementById("recipientAddress").value;

    // Kirim ke backend pakai fetch
    fetch("checkout_order.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        payment_method_id,
        recipientAddress: address,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          window.location.href = "qr.php?payment_method=" + payment_method_id;
        } else {
          // Debug: show server message and sent data
          alert(
            (data.message || "Order failed") +
              "\nAddress: " +
              address +
              "\nPayment method: " +
              payment_method_id
          );
        }
      })
      .catch((err) => {
        alert("Order failed (network or server error)");
      });
  } else {
    alert("Please fill in all required fields.");
  }
});

// Reset validation styling on input
formInputs.forEach((input) => {
  input.addEventListener("input", function () {
    this.style.boxShadow = "";
  });
});

// Navigation functionality
// const navLinks = document.querySelectorAll('.nav-link, .breadcrumb a, .footer-link');
//
// navLinks.forEach(link => {
//     link.addEventListener('click', function(e) {
//         e.preventDefault();
//         alert(`Navigating to: ${this.textContent.trim()}`);
//     });
// });

// Search functionality
const searchInput = document.querySelector(".search-bar input");

searchInput.addEventListener("keypress", function (e) {
  if (e.key === "Enter") {
    alert(`Searching for: ${this.value}`);
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
});
