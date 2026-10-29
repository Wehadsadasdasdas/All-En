const logo = document.querySelector(".logo");
logo.addEventListener("click", function () {
  window.location.href = "index.php";
}); // Form validation
const nextBtn = document.getElementById("nextBtn");
const warrantyId = document.getElementById("warranty-id");
const warrantyReason = document.getElementById("warranty-reason");

// Only add event listeners if the elements exist (user is logged in)
if (nextBtn && warrantyId && warrantyReason) {
  nextBtn.addEventListener("click", function (e) {
    e.preventDefault();
    let isValid = true;

    if (!warrantyId.value) {
      isValid = false;
      warrantyId.style.border = "1px solid red";
    } else {
      warrantyId.style.border = "none";
    }

    if (!warrantyReason.value) {
      isValid = false;
      warrantyReason.style.border = "1px solid red";
    } else {
      warrantyReason.style.border = "none";
    }

    if (isValid) {
      window.location.href = "warrant-process.php";
      warrantyId.value = "";
      warrantyReason.value = "";
    }
  });
}

// Add hover effects to navigation links
const navLinks = document.querySelectorAll(".nav-link");
navLinks.forEach((link) => {
  link.addEventListener("mouseenter", function () {
    this.style.color = "#000000";
    this.style.fontWeight = "500";
  });

  link.addEventListener("mouseleave", function () {
    this.style.color = "#000000";
    this.style.fontWeight = "400";
  });
}); // Add hover effect to Next button
const nextButton = document.querySelector(".next-button");
if (nextButton) {
  nextButton.addEventListener("mouseenter", function () {
    this.style.backgroundColor = "#333333";
  });

  nextButton.addEventListener("mouseleave", function () {
    this.style.backgroundColor = "#000000";
  });
}

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

const signUpLink = document.querySelector(".announcement-text span");
if (signUpLink) {
  signUpLink.addEventListener("click", function () {
    window.location.href = "signup.php";
  });
}

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
