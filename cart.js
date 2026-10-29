document.addEventListener("DOMContentLoaded", function () {
  //when i click the logo, it goes to home page
  document.querySelector(".logo").addEventListener("click", function () {
    window.location.href = "index.php";
  });

  // Quantity adjustment
  const quantityValues = document.querySelectorAll(".quantity-value");
  const removeButtons = document.querySelectorAll(".remove-item");
  const cartItems = document.querySelectorAll(".cart-item");

  document.querySelectorAll(".quantity-btn").forEach((button) => {
    button.addEventListener("click", function () {
      const productId = this.getAttribute("data-product-id");
      const sizeId = this.getAttribute("data-size-id");
      const color = this.getAttribute("data-color");
      const action = this.getAttribute("data-action");
      // Debug log
      console.log({ productId, sizeId, color, action });
      if (!productId || !sizeId || !color || !action) {
        alert(
          "Error: Data attribute missing. Please refresh the page or contact support."
        );
        return;
      }
      const quantityValue = this.parentElement.querySelector(".quantity-value");

      fetch("cart_update_quantity.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
          product_id: productId,
          size_id: sizeId,
          color: color,
          action: action,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Temukan elemen quantity-value dan item-price di dalam .cart-item yang sama
            const cartItem = button.closest(".cart-item");
            const quantitySpan = cartItem.querySelector(".quantity-value");
            if (quantitySpan) {
              quantitySpan.textContent = data.new_quantity;
            }
            // Update item price (total for this item)
            const itemPriceSpan = cartItem.querySelector(".item-price");
            if (itemPriceSpan && data.item_total_price !== undefined) {
              itemPriceSpan.textContent =
                "$" + parseFloat(data.item_total_price).toFixed(2);
            }
            // Update order summary
            if (
              data.subtotal !== undefined &&
              data.discount !== undefined &&
              data.delivery !== undefined &&
              data.total !== undefined
            ) {
              document.querySelector(
                ".summary-row:nth-child(2) .summary-value"
              ).textContent = "$" + parseFloat(data.subtotal).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(3) .summary-value"
              ).textContent = "-$" + parseFloat(data.discount).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(4) .summary-value"
              ).textContent = "$" + parseFloat(data.delivery).toFixed(2);
              document.querySelector(".total-row .summary-value").textContent =
                "$" + parseFloat(data.total).toFixed(2);
            }
          } else {
            alert(data.message || "Failed to update quantity");
          }
        });
    });
  });

  // Add event listeners to remove buttons
  removeButtons.forEach((button, index) => {
    button.addEventListener("click", function () {
      const cartItem = button.closest(".cart-item");
      const productId = button.parentElement
        .querySelector(".quantity-btn")
        .getAttribute("data-product-id");
      const sizeId = button.parentElement
        .querySelector(".quantity-btn")
        .getAttribute("data-size-id");
      const color = button.parentElement
        .querySelector(".quantity-btn")
        .getAttribute("data-color");
      if (!productId || !sizeId || !color) {
        alert(
          "Error: Data attribute missing. Please refresh the page or contact support."
        );
        return;
      }
      if (!confirm("Remove this item from cart?")) return;
      fetch("cart_remove_item.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          product_id: productId,
          size_id: sizeId,
          color: color,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            cartItem.remove();
            // Update order summary after remove
            if (
              data.subtotal !== undefined &&
              data.discount !== undefined &&
              data.delivery !== undefined &&
              data.total !== undefined
            ) {
              document.querySelector(
                ".summary-row:nth-child(2) .summary-value"
              ).textContent = "$" + parseFloat(data.subtotal).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(3) .summary-value"
              ).textContent = "-$" + parseFloat(data.discount).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(4) .summary-value"
              ).textContent = "$" + parseFloat(data.delivery).toFixed(2);
              document.querySelector(".total-row .summary-value").textContent =
                "$" + parseFloat(data.total).toFixed(2);
            }

            // Check if this was the last item in the cart
            const remainingItems =
              document.querySelectorAll(".cart-item").length;
            if (remainingItems <= 1) {
              // If this was the last item (1 because the current item is still in the DOM)
              // Reload the page to show the empty cart message
              window.location.reload();
            }
          } else {
            alert(data.message || "Failed to remove item");
          }
        });
    });
  });
  // Promo code functionality
  const applyButton = document.querySelector(".apply-btn");
  const promoInput = document.querySelector(".promo-input input");

  // Check if these elements exist before adding event listeners
  if (applyButton && promoInput) {
    applyButton.addEventListener("click", function () {
      const promoCode = promoInput.value.trim();
      if (promoCode !== "") {
        fetch("cart_apply_discount.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({ promo_code: promoCode }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.success) {
              // Update summary values
              document.querySelector(
                ".summary-row:nth-child(2) .summary-value"
              ).textContent = "$" + parseFloat(data.subtotal).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(3) .summary-value"
              ).textContent = "-$" + parseFloat(data.discount).toFixed(2);
              document.querySelector(
                ".summary-row:nth-child(4) .summary-value"
              ).textContent = "$" + parseFloat(data.delivery).toFixed(2);
              document.querySelector(".total-row .summary-value").textContent =
                "$" + parseFloat(data.total).toFixed(2);
              alert("Promo code applied successfully!");
              promoInput.value = "";
            } else {
              alert(data.message || "Invalid promo code");
            }
          });
      } else {
        alert("Please enter a valid promo code");
      }
    });
  } // Close the if (applyButton && promoInput) block

  // Remove discount functionality
  const removeDiscountBtn = document.getElementById("rmv");
  if (removeDiscountBtn) {
    removeDiscountBtn.addEventListener("click", function () {
      fetch("cart_remove_discount.php", {
        method: "POST",
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Update summary values
            document.querySelector(
              ".summary-row:nth-child(2) .summary-value"
            ).textContent = "$" + parseFloat(data.subtotal).toFixed(2);
            document.querySelector(
              ".summary-row:nth-child(3) .summary-value"
            ).textContent = "-$" + parseFloat(data.discount).toFixed(2);
            document.querySelector(
              ".summary-row:nth-child(4) .summary-value"
            ).textContent = "$" + parseFloat(data.delivery).toFixed(2);
            document.querySelector(".total-row .summary-value").textContent =
              "$" + parseFloat(data.total).toFixed(2);
          } else {
            alert("Failed to remove discount");
          }
        });
    });
  }

  // Checkout button functionality
  const checkoutBtn = document.querySelector(".checkout-btn");
  if (checkoutBtn) {
    checkoutBtn.addEventListener("click", function () {
      //go to checkout page
      window.location.href = "checkout.php";
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
