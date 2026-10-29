// When logo is clicked, redirect to home page
document.querySelector(".logo").addEventListener("click", function () {
    window.location.href = "index.php";
});

// When cart icon is clicked, toggle cart content visibility
const cartIcon = document.querySelector(".cart-icons");
const cartContent = document.getElementById("cartContent");

cartIcon.addEventListener("click", (e) => {
    e.stopPropagation(); // Prevent document click from immediately closing it
    cartContent.classList.toggle("active");
});

// When mouse leaves cart content, hide it
cartContent.addEventListener("mouseleave", () => {
    cartContent.classList.remove("active");
});

// Handle announcement bar sign up link and close button
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
        document.body.style.paddingTop = "5px"; // Adjust body padding after closing
    });
} else {
    document.body.style.paddingTop = "5px"; // Ensure padding if bar is not present or session is logged in
}

// Quantity Control
const quantityInput = document.querySelector(".quantity-input");
const decreaseBtn = document.querySelector(".decrease");
const increaseBtn = document.querySelector(".increase");

decreaseBtn.addEventListener("click", function () {
    let quantity = parseInt(quantityInput.value);
    if (quantity > 1) {
        quantityInput.value = quantity - 1;
    }
});

increaseBtn.addEventListener("click", function () {
    let quantity = parseInt(quantityInput.value);
    quantityInput.value = quantity + 1;
});

// Add to Cart
// The add-to-cart logic from product_detail.php script block is preferred,
// so this commented out section from product_detail.js is still kept commented.
// const addToCartBtn = document.querySelector(".add-to-cart-btn");
// addToCartBtn.addEventListener("click", function () { /* ... */ });


// Tab Navigation
const tabs = document.querySelectorAll(".tab");
const dynamicContentContainer = document.getElementById("reviews-content");

// Base HTML for the reviews section
const reviewsInnerHtmlBase = `
    <div class="reviews-header">
        <div class="reviews-title">
            <h2>All Reviews</h2>
            <span class="reviews-count">(0)</span>
        </div>
        <div class="reviews-actions">
            <button class="filter-btn">
                <img src="images/filter.png" alt="Filter">
            </button>
            <div class="sort-dropdown">
                <button class="sort-btn">
                    Latest <img src="images/shop-dropdown.png" alt="Dropdown">
                </button>
                <div class="sort-options" style="display: none;">
                    <a href="#" data-sort="latest">Latest</a>
                    <a href="#" data-sort="earliest">Earliest</a>
                </div>
            </div>
            <button class="write-review-btn">Write a Review</button>
        </div>
    </div>
    <div class="review-list"></div>`;

// There's no longer a separate "Details" tab content as the main description is now always visible.
// If you want to add other details (e.g., specifications) to a "Details" tab, you'll need to define it here.

tabs.forEach((tab) => {
    tab.addEventListener("click", function () {
        // Remove 'active' class from all tabs
        tabs.forEach((t) => t.classList.remove("active"));
        // Add 'active' class to the clicked tab
        this.classList.add("active");

        if (dynamicContentContainer) {
            dynamicContentContainer.innerHTML = ""; // Clear previous content

            const clickedProduct = JSON.parse(localStorage.getItem('product-clicked'));

            // Since we're removing the dedicated "Details" tab handling in JS
            // (assuming description is always visible), we only handle "Reviews" here.
            if (this.id === "tab-reviews") {
                dynamicContentContainer.innerHTML = reviewsInnerHtmlBase;

                // Attach event listener for "Write a Review" button
                const writeReviewBtn = dynamicContentContainer.querySelector('.write-review-btn');
                if (writeReviewBtn) {
                    writeReviewBtn.addEventListener('click', showReviewModal);
                }

                // Attach event listeners for sort options
                const sortBtn = dynamicContentContainer.querySelector('.sort-btn');
                const sortOptions = dynamicContentContainer.querySelector('.sort-options');
                if (sortBtn && sortOptions) {
                    sortBtn.addEventListener('click', (e) => {
                        e.stopPropagation(); // Prevent document click from immediately closing
                        sortOptions.style.display = sortOptions.style.display === 'block' ? 'none' : 'block';
                    });

                    sortOptions.querySelectorAll('a').forEach(option => {
                        option.addEventListener('click', (e) => {
                            e.preventDefault();
                            const sortOrder = e.target.dataset.sort;
                            sortOptions.style.display = 'none'; // Hide dropdown
                            // Update button text, preserving the dropdown arrow image
                            const img = sortBtn.querySelector('img'); // Get the current image element
                            sortBtn.innerHTML = `${e.target.textContent} `; // Set text
                            sortBtn.appendChild(img); // Append the image back

                            // Fetch and display reviews with the new sort order
                            const selectedSizeEl = document.querySelector('.size-option.selected');
                            const selectedColorEl = document.querySelector('.colors-option.selected');
                            const selectedSizeName = selectedSizeEl ? selectedSizeEl.textContent.trim() : null;
                            const selectedColorName = selectedColorEl ? selectedColorEl.textContent.trim() : null;
                            fetchAndDisplayReviews(clickedProduct.id, selectedSizeName, selectedColorName, sortOrder);
                        });
                    });
                }

                // Initial load of reviews when reviews tab is clicked (or made default)
                const selectedSizeEl = document.querySelector('.size-option.selected');
                const selectedColorEl = document.querySelector('.colors-option.selected');

                if (clickedProduct && clickedProduct.id) {
                     // Pass selected size and color if available, otherwise they'll be null
                    fetchAndDisplayReviews(
                        clickedProduct.id,
                        selectedSizeEl ? selectedSizeEl.textContent.trim() : null,
                        selectedColorEl ? selectedColorEl.textContent.trim() : null
                    );
                } else {
                    const reviewList = dynamicContentContainer.querySelector('.review-list');
                    if (reviewList) reviewList.innerHTML = '<p>Select a product and its options to view reviews.</p>';
                }
            }
        } else {
            console.error("The content container with ID 'reviews-content' was not found.");
        }
    });
});

// Set the "Reviews" tab as the default active tab on page load
function setDefaultActiveTab() {
    const defaultTab = document.getElementById("tab-reviews"); // Changed to "tab-reviews"
    if (defaultTab) {
        defaultTab.click();
    } else {
        if (tabs.length > 0) {
            tabs[0].click(); // Fallback to the first tab if "tab-reviews" isn't found
        }
        console.warn(
            "Default tab 'tab-reviews' not found. Falling back to the first tab if available."
        );
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // ... your existing DOMContentLoaded logic ...

    // Ensure the reviews tab is clicked after product data is loaded,
    // so fetchAndDisplayReviews has the product ID available.
    // The previous setDefaultActiveTab() call should now be inside this block
    // or called after the clickedProduct is guaranteed to be set.
    const clickedProduct = JSON.parse(localStorage.getItem('product-clicked'));

    if (clickedProduct && clickedProduct.id) {
        // ... existing calls like populateSizeOptions(productId, previouslySelectedSize);
        // ... existing call to fetchAndDisplayProductRating(productId);

        // Call the function to set the default active tab, which will also fetch reviews
        setDefaultActiveTab();

    } else {
        // Handle case where no product is selected if necessary
        const sizeContainer = document.getElementById('size');
        if (sizeContainer) {
            sizeContainer.innerHTML = '<div class="size-option">Please select a product.</div>';
        }
        // Also ensure reviews section shows appropriate message
        const reviewListContainer = document.querySelector('.review-list');
        if (reviewListContainer) {
             reviewListContainer.innerHTML = '<p>Select a product to view reviews.</p>';
        }
        updateProductDisplay(null, null, true);
        console.log('No product selected.');
    }
});

setDefaultActiveTab();



const reviewModalHtml = `
    <div id="reviewModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Write Your Review</h3>
            <div class="rating-selection">
                <span class="star" data-value="1">☆</span>
                <span class="star" data-value="2">☆</span>
                <span class="star" data-value="3">☆</span>
                <span class="star" data-value="4">☆</span>
                <span class="star" data-value="5">☆</span>
            </div>
            <textarea id="reviewContent" placeholder="Your review..." rows="5"></textarea>
            <button id="submitReviewBtn">Submit Review</button>
        </div>
    </div>
    <style>
        /* Basic Modal Styles (can be moved to product_detail.css) */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .modal-content .close-button {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }
        .rating-selection {
            font-size: 30px;
            color: #ccc; /* Default empty star color */
            cursor: pointer;
            margin-bottom: 15px;
        }
        .rating-selection .star.selected {
            color: gold; /* Filled star color */
        }
        #reviewContent {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }
        #submitReviewBtn {
            background-color: black;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        #submitReviewBtn:hover {
            background-color: #333;
        }
        /* Styles for sort dropdown */
        .sort-dropdown {
            position: relative;
            display: inline-block;
        }
        .sort-options {
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            padding: 8px 0;
            right: 0; /* Align dropdown to the right of its parent button */
        }
        .sort-options a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 14px;
        }
        .sort-options a:hover {
            background-color: #ddd;
        }
        /* Reviews Grid for better layout */
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .review-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .review-card .star {
            color: gold; /* Filled stars in review display */
        }
        .review-card .reviewer-name {
            font-weight: bold;
            margin-right: 5px;
        }
        .review-card .verified-icon {
            width: 16px; /* Adjust size as needed */
            height: 16px;
            vertical-align: middle;
            margin-right: 5px;
        }
        .review-card .review-date {
            font-size: 0.9em;
            color: #777;
        }
        .review-card .review-content {
            margin-top: 10px;
            color: #333;
        }
        .review-card .review-product-variant {
            font-size: 0.9em;
            color: #555;
            margin-top: 10px;
        }
    </style>
`;

document.body.insertAdjacentHTML('beforeend', reviewModalHtml);

let currentRating = 0; // To store the selected star rating

function showReviewModal() {
    const modal = document.getElementById('reviewModal');
    const stars = modal.querySelectorAll('.rating-selection .star');
    const reviewContentTextarea = document.getElementById('reviewContent');

    // Reset modal state
    currentRating = 0;
    stars.forEach(star => {
        star.classList.remove('selected');
        star.textContent = '☆'; // Ensure stars are reset to empty
    });
    reviewContentTextarea.value = '';

    // Add event listeners for stars
    stars.forEach(star => {
        star.onclick = function() {
            const value = parseInt(this.dataset.value);
            currentRating = value;
            stars.forEach((s, i) => {
                if (i < value) {
                    s.classList.add('selected');
                    s.textContent = '★';
                } else {
                    s.classList.remove('selected');
                    s.textContent = '☆';
                }
            });
        };
    });

    // Add event listener for submit button
    const submitBtn = document.getElementById('submitReviewBtn');
    if (submitBtn) {
        submitBtn.onclick = submitReview;
    }

    // Add event listener for close button
    const closeBtn = modal.querySelector('.close-button');
    if (closeBtn) {
        closeBtn.onclick = hideReviewModal;
    }

    // Close modal if clicked outside
    modal.onclick = function(event) {
        if (event.target === modal) {
            hideReviewModal();
        }
    };

    modal.style.display = 'flex'; // Show the modal
}

function hideReviewModal() {
    const modal = document.getElementById('reviewModal');
    modal.style.display = 'none'; // Hide the modal
}

async function submitReview() {
    const reviewContent = document.getElementById('reviewContent').value.trim();
    const product = JSON.parse(localStorage.getItem('product-clicked'));
    const selectedSizeEl = document.querySelector('.size-option.selected');
    const selectedColorEl = document.querySelector('.colors-option.selected');

    if (!product || !product.id || !selectedSizeEl || !selectedColorEl) {
        alert("Product, size, or color information is missing. Cannot submit review.");
        return;
    }

    if (currentRating === 0) {
        alert("Please select a rating (1-5 stars).");
        return;
    }

    if (!reviewContent) {
        alert("Please write your review content.");
        return;
    }

    const productId = product.id;
    // Assuming you can get size_id from the size option's dataset. If not, PHP will have to resolve it by size name.
    const sizeId = selectedSizeEl.dataset.sizeId || null;
    const sizeName = selectedSizeEl.textContent.trim();
    const color = selectedColorEl.textContent.trim();
    const rating = currentRating;
    const submitDate = new Date().toISOString().slice(0, 10); // YYYY-MM-DD format

    try {
        const response = await fetch('product-detail-submit-review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                size_id: sizeId, // Pass size_id if available, otherwise backend resolves by name
                size_name: sizeName, // Always pass size_name as a fallback/redundancy
                color: color,
                content: reviewContent,
                rating: rating,
                submit_date: submitDate
            })
        });

        const result = await response.json();

        if (result.success) {
            alert("Review submitted successfully!");
            hideReviewModal();
            // Re-fetch and display reviews to show the new one, sorted by latest by default
            fetchAndDisplayReviews(productId, sizeName, color, 'latest');
            fetchAndDisplayProductRating(productId); // Also update the overall product rating
        } else {
            alert(result.message || "Failed to submit review.");
        }
    } catch (error) {
        console.error('Error submitting review:', error);
        alert('An error occurred while submitting your review.');
    }
}

async function fetchAndDisplayReviews(productId, sizeName = null, colorName = null, sortOrder = 'latest') {
    const reviewListContainer = dynamicContentContainer.querySelector('.review-list');
    const reviewsCountSpan = dynamicContentContainer.querySelector('.reviews-count');

    if (!reviewListContainer || !reviewsCountSpan) {
        console.error('Review list container or count span not found.');
        return;
    }

    reviewListContainer.innerHTML = '<p>Loading reviews...</p>';
    reviewsCountSpan.textContent = '(...)';

    try {
        const response = await fetch('product-detail-fetch-reviews.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                product_id: productId,
                size_name: sizeName, // Pass size_name for filtering
                color_name: colorName, // Pass color_name for filtering
                sort_order: sortOrder
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success && result.data) {
            reviewListContainer.innerHTML = ''; // Clear loading message
            reviewsCountSpan.textContent = `(${result.data.length})`;

            if (result.data.length === 0) {
                reviewListContainer.innerHTML = '<p>No reviews yet. Be the first to review this product!</p>';
            } else {
                const reviewsGrid = document.createElement('div'); // Use reviewsGrid instead of outer
                reviewsGrid.classList.add('reviews-grid');

                result.data.forEach(review => {
                    const reviewDiv = document.createElement('div');
                    reviewDiv.classList.add('review-card');

                    const starsHtml = Array(5).fill('☆').map((star, index) => {
                        return index < review.rating ? '★' : '☆';
                    }).join('');

                    reviewDiv.innerHTML = `
                        <div class="review-meta">
                            <span class="star">${starsHtml}</span>
                        </div>
                        <span class="reviewer-name">${review.name}</span>
                        <img src="images/checklist.png" alt="Verified" class="verified-icon" style="transform: translateY(-1px);">
                        <span class="review-date">${review.Submit_date}</span>
                        <p class="review-content" style="margin-top: 5px;">${review.Content}</p>
                        <p class="review-product-variant" style="font-weight: bold; margin-top: 10px;">Size: ${review.size || 'N/A'}, Color: ${review.color || 'N/A'}</p>
                    `;
                    reviewsGrid.appendChild(reviewDiv);
                });
                reviewListContainer.appendChild(reviewsGrid); // Append the grid of reviews
            }
        } else {
            reviewListContainer.innerHTML = '<p>Error loading reviews.</p>';
            reviewsCountSpan.textContent = '(Error)';
            console.error('Error fetching reviews:', result.error || 'Unknown error');
        }
    } catch (error) {
        reviewListContainer.innerHTML = '<p>Failed to load reviews due to a network error.</p>';
        reviewsCountSpan.textContent = '(Error)';
        console.error('Network or parsing error fetching reviews:', error);
    }
}


// Dropdown for header
const dropdown = document.querySelector(".dropdown");
const dropdownLink = dropdown.querySelector(".nav-item");

dropdownLink.addEventListener("click", function (e) {
    e.preventDefault();
    dropdown.classList.toggle("open");
});

document.addEventListener("click", function (e) {
    // .contains() will be true if the click is inside the dropdown
    // Exclude sort dropdown clicks to prevent it from closing the main dropdown
    if (!dropdown.contains(e.target) && !e.target.closest('.sort-dropdown')) {
        dropdown.classList.remove("open");
    }
});

// Hide sort dropdown when clicking outside
document.addEventListener('click', function(event) {
    const sortDropdown = document.querySelector('.sort-dropdown');
    if (sortDropdown && !sortDropdown.contains(event.target)) {
        const sortOptions = sortDropdown.querySelector('.sort-options');
        if (sortOptions) {
            sortOptions.style.display = 'none';
        }
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
                                    image: item.image_link,
                                    // Make sure description is also stored if you want to display it on product_detail.php
                                    // You might need to add 'description' to your AJAX search query in index.php
                                    // description: item.description // <-- Add this if your AJAX search returns it
                                })
                            );
                            window.location.href = "product_detail.php";
                        });
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

// Hide suggestion box when clicking outside input and suggestion box
document.addEventListener("click", (e) => {
    if (input && suggestionBox && !input.contains(e.target) && !suggestionBox.contains(e.target)) {
        suggestionBox.style.display = "none";
    }
});