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
      window.location.href = "all-category-product.php";
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



  // const clickedProduct = JSON.parse(localStorage.getItem("product-clicked"));

  // if (clickedProduct && clickedProduct.id) {
  //   // gunakan clickedProduct.id untuk ambil detail dari DB via fetch
  //   // tampilkan clickedProduct.name dan clickedProduct.image juga di awal

  //   document.getElementById("name").textContent = clickedProduct.name;
  //   document.getElementById("image").src =
  //     "Product_images/" + clickedProduct.image;
  //   document.getElementById("image").alt = clickedProduct.name;

  //   // Lanjut fetch size/color/price
  //   populateSizeOptions(clickedProduct.id);
  // } else {
  //   alert("No product selected");
  //   window.location.href = "index.php";
  // }

  // // Hide suggestion box when click outside
  // document.addEventListener("click", function (e) {
  //   if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
  //     suggestionBox.style.display = "none";
  //   }
  // });

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

  // Calculate how many cards can be visible at once
  const getVisibleCards = () => {
    const containerWidth = document.querySelector(".testimonials").offsetWidth - 200; // subtract padding
    const cardWidth = 400 + 20; // card width + gap
    return Math.floor(containerWidth / cardWidth);
  };

  // Calculate maximum scroll index
  const getMaxIndex = () => {
    const visibleCards = getVisibleCards();
    return Math.max(0, testimonials.length - visibleCards);
  };

  function updateTestimonialPosition() {
    if (testimonials.length === 0) return;
    
    const cardWidth = 400 + 20; // card width + gap
    testimonialGrid.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
    testimonialGrid.style.transition = "transform 0.5s ease";
    
    // Update button states
    prevButton.style.opacity = currentIndex === 0 ? "0.5" : "1";
    prevButton.style.pointerEvents = currentIndex === 0 ? "none" : "auto";
    
    const maxIndex = getMaxIndex();
    nextButton.style.opacity = currentIndex >= maxIndex ? "0.5" : "1";
    nextButton.style.pointerEvents = currentIndex >= maxIndex ? "none" : "auto";
  }

  prevButton?.addEventListener("click", function () {
    if (currentIndex > 0) {
      currentIndex--;
      updateTestimonialPosition();
    }
  });

  nextButton?.addEventListener("click", function () {
    const maxIndex = getMaxIndex();
    if (currentIndex < maxIndex) {
      currentIndex++;
      updateTestimonialPosition();
    }
  });

  // Initialize testimonial position
  if (testimonials.length > 0) {
    updateTestimonialPosition();
  }

  // Handle window resize
  window.addEventListener('resize', function() {
    if (testimonials.length > 0) {
      // Reset to safe position if current index is now out of bounds
      const maxIndex = getMaxIndex();
      if (currentIndex > maxIndex) {
        currentIndex = maxIndex;
      }
      updateTestimonialPosition();
    }
  });
});


