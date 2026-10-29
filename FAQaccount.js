document.addEventListener("DOMContentLoaded", function () {
  // === Logo click ===
  document.querySelector(".logo").addEventListener("click", function () {
    window.location.href = "index.php";
  });

  // === Sign Up Now click ===
  const signUpLink = document.querySelector(".announcement-text span");
  if (signUpLink) {
    signUpLink.addEventListener("click", function () {
      window.location.href = "signup.php";
    });
  }

  // === Close announcement ===
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

  // === Shop Now Button ===
  const shopNowBtn = document.querySelector(".shop-now-btn");
  if (shopNowBtn) {
    shopNowBtn.addEventListener("click", function () {
      alert("Taking you to our shop collection!");
    });
  }

  // === Product click (to localStorage) ===
  const productCards = document.querySelectorAll(".product-card");
  productCards.forEach((card) => {
    card.addEventListener("click", function () {
      const productId = this.getAttribute("data-id");
      const productImage = this.querySelector("img")?.getAttribute("src");
      const productPrice =
        this.nextElementSibling?.querySelector(".current-price")?.textContent ||
        "";

      const clickedProduct = {
        id: productId,
        image: productImage,
        price: productPrice,
      };

      localStorage.setItem("product-clicked", JSON.stringify(clickedProduct));
      window.location.href = "product_detail.php";
    });
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

  // === Dropdown Toggle ===
  const dropdown = document.querySelector(".dropdown");
  const dropdownLink = dropdown?.querySelector(".nav-item");

  if (dropdown && dropdownLink) {
    dropdownLink.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("open");
    });

    document.addEventListener("click", function (e) {
      if (!dropdown.contains(e.target)) {
        dropdown.classList.remove("open");
      }
    });
  }

  // === Cart Icon Logic ===
  const cartIcon = document.querySelector(".cart-icon");
  const cartContent = document.getElementById("cartContent");

  cartIcon?.addEventListener("click", (e) => {
    e.stopPropagation();
    cartContent?.classList.toggle("active");
  });

  cartContent?.addEventListener("mouseleave", () => {
    cartContent.classList.remove("active");
  });

  document.addEventListener("click", function (event) {
    if (
      !cartIcon?.contains(event.target) &&
      !cartContent?.contains(event.target)
    ) {
      cartContent?.classList.remove("active");
    }
  });

  // === Testimonial Carousel ===
  const testimonialGrid = document.querySelector(".testimonial-grid");
  const testimonials = document.querySelectorAll(".testimonial-card");
  const prevButton = document.querySelector(".prev-testimonial");
  const nextButton = document.querySelector(".next-testimonial");
  let currentIndex = 0;

  function updateTestimonialPosition() {
    const cardWidth = testimonials[0]?.offsetWidth + 20;
    testimonialGrid.style.transform = `translateX(-${
      currentIndex * cardWidth
    }px)`;
    testimonialGrid.style.transition = "transform 0.5s ease";
  }

  prevButton?.addEventListener("click", function () {
    if (currentIndex > 0) {
      currentIndex--;
      updateTestimonialPosition();
    }
  });

  nextButton?.addEventListener("click", function () {
    if (currentIndex < testimonials.length - 3) {
      currentIndex++;
      updateTestimonialPosition();
    }
  });
});

  document.querySelectorAll('.accordion-toggle').forEach(button => {
    button.addEventListener('click', () => {
      const content = button.nextElementSibling;

      // Optional: close others
      document.querySelectorAll('.accordion-content').forEach(c => {
        if (c !== content) c.style.display = 'none';
      });

      // Toggle
      content.style.display = content.style.display === 'block' ? 'none' : 'block';
    });
  });