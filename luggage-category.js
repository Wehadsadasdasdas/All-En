//when logo is clicked, redirect to homepage.html
document.querySelector(".logo").addEventListener("click", function () {
  window.location.href = "index.php"; // Redirect to homepage
});

const cartIcon = document.querySelector(".cart-icons");
const cartContent = document.getElementById("cartContent");

// Saat mouse masuk ke icon, tampilkan cart
cartIcon.addEventListener("mouseenter", () => {
  cartContent.classList.add("active");
});

// Saat mouse keluar dari kotak cart, sembunyikan
cartContent.addEventListener("mouseleave", () => {
  cartContent.classList.remove("active");
});

  const closeBtn = document.querySelector(".close-btn");
  const announcementBar = document.querySelector(".announcement-bar");
  if (closeBtn && announcementBar) {
    closeBtn.addEventListener("click", function () {
      announcementBar.style.display = "none";
      document.body.style.paddingTop = "5px";
    });
  } else {
    document.body.style.paddingTop = "5px";
  }

const signUpLink = document.querySelector(".announcement-text span");
  if (signUpLink) {
    signUpLink.addEventListener("click", function () {
      window.location.href = "signup.php";
    });
  }


// Price range slider
// const priceRange = document.getElementById('priceRange');
// priceRange.addEventListener('input', function() {
//     // You could update a price display here if needed
// });

// Filter options toggle
const filterOptions = document.querySelectorAll(".filter-option");
filterOptions.forEach((option) => {
  option.addEventListener("click", function () {
    // Toggle active class for styling
    this.classList.toggle("active");
  });
});

// Apply filter button
// document.querySelector(".apply-filter").addEventListener("click", function () {
//   alert("Filters applied!");
//   // In a real application, this would trigger filtering of products
// });

// Product card click
const productCards = document.querySelectorAll(".product-card");
productCards.forEach((card) => {
  card.addEventListener("click", function () {
    window.location.href = "product_detail.php"; // Redirect to product detail page
    // In a real application, this would navigate to product detail page
  });
});

// Pagination
const pageNumbers = document.querySelectorAll(".page-number");
pageNumbers.forEach((page) => {
  page.addEventListener("click", function () {
    // Remove active class from all pages
    pageNumbers.forEach((p) => p.classList.remove("active"));
    // Add active class to clicked page
    this.classList.add("active");

    // In a real application, this would load the corresponding page of products
  });
});

// Previous/Next pagination buttons
document.querySelectorAll(".pagination-button").forEach((button) => {
  button.addEventListener("click", function () {
    const currentPage = document.querySelector(".page-number.active");
    const currentIndex = Array.from(pageNumbers).indexOf(currentPage);

    if (this.textContent.includes("Previous") && currentIndex > 0) {
      pageNumbers[currentIndex].classList.remove("active");
      pageNumbers[currentIndex - 1].classList.add("active");
    } else if (
      this.textContent.includes("Next") &&
      currentIndex < pageNumbers.length - 1
    ) {
      pageNumbers[currentIndex].classList.remove("active");
      pageNumbers[currentIndex + 1].classList.add("active");
    }

    // In a real application, this would load the corresponding page of products
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

const productList = document.getElementById("productList");

function showInfo(product) {
  document.getElementById("modalTitle").textContent = `${product.id}`;
  document.getElementById("modalStock").textContent = `Stock: ${product.stock}`;
  document.getElementById("modalPrice").textContent = `Price: ${product.price}`;
  //document.getElementById('modalDiscount').textContent = `Discount: ${product.discount}`;
  document.getElementById("infoModal").style.display = "flex";
  document.getElementById("overlay").style.display = "flex";

  // Save ID (for example, in localStorage or a variable)
  localStorage.setItem(
    "selectedProductId",
    JSON.stringify({
      id: product.id,
      size: product.size,
      color: product.color,
    })
  );
}

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
