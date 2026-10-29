document.querySelector(".logo").addEventListener("click", function () {
    window.location.href = "index.php";
});

// Close announcement bar
document.querySelector(".close-btn").addEventListener("click", function () {
    document.querySelector(".announcement-bar").style.display = "none";
    document.body.style.paddingTop = "5px"; // kembali ke 20px
});

// Form submission handling (client-side validation improved)
document.getElementById("signUpForm").addEventListener("submit", function (e) {
    // Prevent default form submission to handle client-side validation
    e.preventDefault(); 

    const form = this;
    // Get all required input/select elements
    const requiredFields = form.querySelectorAll('[required]');
    let allFieldsFilled = true;

    // Check each required field
    requiredFields.forEach(field => {
        if (field.value.trim() === '') {
            allFieldsFilled = false;
            // Optionally, add a visual indicator for empty fields
            field.style.borderColor = 'red'; 
        } else {
            field.style.borderColor = ''; // Reset border if filled
        }
    });

    if (allFieldsFilled) {
        // If all fields are filled, submit the form programmatically
        form.submit();
    } else {
        // Use a custom message box instead of alert()
        showCustomAlert("Please fill in all required fields.", "error");
    }
});

// Add hover effects to navigation items
const navItems = document.querySelectorAll(".nav-item");
navItems.forEach((item) => {
    item.addEventListener("mouseenter", function () {
        this.style.opacity = "0.7";
    });
    item.addEventListener("mouseleave", function () {
        this.style.opacity = "1";
    });
});

// Add hover effects to social icons
const socialIcons = document.querySelectorAll(".social-icon");
socialIcons.forEach((icon) => {
    icon.addEventListener("mouseenter", function () {
        this.style.transform = "scale(1.1)";
    });
    icon.addEventListener("mouseleave", function () {
        this.style.transform = "scale(1)";
    });
});

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

// Dropdown navigation
const dropdown = document.querySelector(".dropdown");
const dropdownLink = dropdown.querySelector(".nav-item");

dropdownLink.addEventListener("click", function (e) {
    e.preventDefault();
    dropdown.classList.toggle("open");
});

document.addEventListener("click", function (e) {
    // .contains() will be true if the click is inside the dropdown element
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

        fetch(`signup.php?ajax_search=${encodeURIComponent(keyword)}`)
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
                                    image: item.image_link, // this must come from PHP
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

// When clicking outside the input, hide the suggestion box
document.addEventListener("click", (e) => {
    if (!input.contains(e.target) && !suggestionBox.contains(e.target)) {
        suggestionBox.style.display = "none";
    }
});

// Function to populate country dropdown
function populateCountries() {
    const countrySelect = document.getElementById('countrySelect');
    if (!countrySelect) return; // Exit if the element doesn't exist

    // A comprehensive list of countries
    const countries = [
        "Afghanistan", "Albania", "Algeria", "Andorra", "Angola", "Antigua and Barbuda", "Argentina", "Armenia", "Australia", "Austria", "Azerbaijan",
        "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize", "Benin", "Bhutan", "Bolivia", "Bosnia and Herzegovina", "Botswana", "Brazil", "Brunei", "Bulgaria", "Burkina Faso", "Burundi",
        "Cabo Verde", "Cambodia", "Cameroon", "Canada", "Central African Republic", "Chad", "Chile", "China", "Colombia", "Comoros", "Congo (Brazzaville)", "Congo (Kinshasa)", "Costa Rica", "Croatia", "Cuba", "Cyprus", "Czechia",
        "Denmark", "Djibouti", "Dominica", "Dominican Republic",
        "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea", "Eritrea", "Estonia", "Eswatini", "Ethiopia",
        "Fiji", "Finland", "France",
        "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Greece", "Grenada", "Guatemala", "Guinea", "Guinea-Bissau", "Guyana",
        "Haiti", "Honduras", "Hungary",
        "Iceland", "India", "Indonesia", "Iran", "Iraq", "Ireland", "Israel", "Italy", "Ivory Coast",
        "Jamaica", "Japan", "Jordan",
        "Kazakhstan", "Kenya", "Kiribati", "Kosovo", "Kuwait", "Kyrgyzstan",
        "Laos", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libya", "Liechtenstein", "Lithuania", "Luxembourg",
        "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Mauritania", "Mauritius", "Mexico", "Micronesia", "Moldova", "Monaco", "Mongolia", "Montenegro", "Morocco", "Mozambique", "Myanmar",
        "Namibia", "Nauru", "Nepal", "Netherlands", "New Zealand", "Nicaragua", "Niger", "Nigeria", "North Korea", "North Macedonia", "Norway",
        "Oman",
        "Pakistan", "Palau", "Palestine", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Poland", "Portugal",
        "Qatar",
        "Romania", "Russia", "Rwanda",
        "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Serbia", "Seychelles", "Sierra Leone", "Singapore", "Slovakia", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Korea", "South Sudan", "Spain", "Sri Lanka", "Sudan", "Suriname", "Sweden", "Switzerland", "Syria",
        "Taiwan", "Tajikistan", "Tanzania", "Thailand", "Timor-Leste", "Togo", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Tuvalu",
        "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "Uruguay", "Uzbekistan",
        "Vanuatu", "Vatican City", "Venezuela", "Vietnam",
        "Yemen",
        "Zambia", "Zimbabwe"
    ];

    countries.forEach(country => {
        const option = document.createElement('option');
        option.value = country;
        option.textContent = country;
        countrySelect.appendChild(option);
    });
}

// Call populateCountries when the DOM content is loaded
document.addEventListener('DOMContentLoaded', populateCountries);

// Custom alert function (replaces native alert)
function showCustomAlert(message, type) {
    const alertBox = document.getElementById("customAlert");
    const alertMessage = document.getElementById("alertMessage");
    const alertButton = document.getElementById("alertButton");

    alertMessage.textContent = message;

    if (type === "error") {
        alertButton.textContent = "Try Again";
        alertButton.onclick = () => alertBox.style.display = "none"; // Just close for now
    } else if (type === "success") {
        alertButton.textContent = "Go Signin";
        alertButton.onclick = () => (window.location.href = "signin.php");
    }

    alertBox.style.display = "flex";
}
