/**
 * 2007-2023 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2023 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

document.addEventListener("DOMContentLoaded", function () {
    // Initialize free shipping progress bar
    initFreeShippingBar();

    // Add class for electrical products theme
    addElectricalThemeClass();

    // Listen for prestashop cart events
    listenToCartEvents();

    // Force an initial AJAX update
    setTimeout(updateFreeShippingBar, 500);
});

/**
 * Initialize the free shipping progress bar
 */
function initFreeShippingBar() {
    // Add initial animation to progress bar
    animateProgressBar();
}

/**
 * Add electrical theme class if we're on an electrical products store
 */
function addElectricalThemeClass() {
    // This function checks if we're on an electrical products store
    // For this example, we're just adding the class to showcase the electrical theme
    const container = document.querySelector(".mtf-free-shipping-container");
    if (container) {
        container.classList.add("electrical-theme");
    }
}

/**
 * Listen to PrestaShop cart update events
 */
function listenToCartEvents() {
    // Listen for all possible cart update events from PrestaShop
    const cartEvents = [
        "updateCart",
        "updateCartAfter",
        "updatedCart",
        "updateProduct",
        "updatedProduct",
        "deleteProductFromCart",
        "productDeleted",
        "updateProductInCart",
        "updatedProductInCart",
        "updateProductQuantity",
        "updatedProductQuantity",
    ];

    cartEvents.forEach(function (eventName) {
        document.addEventListener(eventName, function (event) {
            console.log("Cart event detected:", eventName);
            setTimeout(updateFreeShippingBar, 400);
        });
    });

    // Add event listeners to quantity inputs and update buttons
    addQuantityChangeListeners();

    // Set up a periodic refresh to ensure we're always up to date
    setInterval(updateFreeShippingBar, 3000);

    // Also update on page load
    updateFreeShippingBar();
}

/**
 * Add listeners to cart quantity inputs and update buttons
 */
function addQuantityChangeListeners() {
    // Use MutationObserver to watch for changes in the cart quantity
    const cartContainer = document.querySelector("#cart");

    if (cartContainer) {
        // Set up observer to watch for DOM changes in the cart
        const observer = new MutationObserver(function (mutations) {
            // After any DOM change in the cart, update the shipping bar
            setTimeout(updateFreeShippingBar, 500);
        });

        // Start observing the cart container
        observer.observe(cartContainer, {
            childList: true,
            subtree: true,
            attributes: true,
            characterData: true,
        });
    }

    // Also add direct event listeners to inputs and buttons
    document.addEventListener("click", function (event) {
        // Check if the click was on a quantity button or relevant cart element
        if (
            event.target.closest(".js-update-product-quantity-up") ||
            event.target.closest(".js-update-product-quantity-down") ||
            event.target.closest(".js-cart-line-product-quantity") ||
            event.target.closest(".js-cart-action-button")
        ) {
            setTimeout(updateFreeShippingBar, 500);
        }
    });

    // Add a change event listener to the entire document to catch quantity input changes
    document.addEventListener("change", function (event) {
        if (event.target.classList.contains("js-cart-line-product-quantity")) {
            setTimeout(updateFreeShippingBar, 700);
        }
    });

    // Also listen for PrestaShop's custom events for quantity updates
    document.addEventListener("updateProductInCart", function () {
        setTimeout(updateFreeShippingBar, 500);
    });

    document.addEventListener("updatedProductInCart", function () {
        setTimeout(updateFreeShippingBar, 500);
    });
}

// Track the last update time to prevent too frequent requests
let lastUpdateTime = 0;

/**
 * Update free shipping progress bar via AJAX
 */
function updateFreeShippingBar() {
    // Prevent updating too frequently (at least 300ms between updates)
    const now = Date.now();
    if (now - lastUpdateTime < 300) {
        return;
    }
    lastUpdateTime = now;

    if (typeof mtf_freeprice_ajax_url === "undefined") {
        console.error("Free shipping module AJAX URL not defined");
        return;
    }

    // Add a timestamp parameter to prevent caching
    const ajaxUrl = mtf_freeprice_ajax_url + "?t=" + now;

    fetch(ajaxUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Cache-Control": "no-cache, no-store, must-revalidate",
            Pragma: "no-cache",
            Expires: "0",
        },
        cache: "no-store",
    })
        .then((response) => response.json())
        .then((response) => {
            if (response.success) {
                updateProgressBar(response.data);
            } else {
                console.error(
                    "Error updating free shipping progress bar:",
                    response.message
                );
            }
        })
        .catch((error) => {
            console.error("Error updating free shipping progress bar:", error);
        });
}

/**
 * Update the progress bar with new data
 */
function updateProgressBar(data) {
    const container = document.querySelector(".mtf-free-shipping-container");
    if (!container) return;

    // Hide the container if cart is empty (total is 0)
    if (data.cart_total <= 0) {
        container.style.display = "none";
        return;
    } else {
        container.style.display = "block";
    }

    // Update progress bar percentage
    const progressBar = container.querySelector(".mtf-progress-bar");
    if (progressBar) {
        progressBar.style.width = data.progress + "%";
    }

    // Update progress value position
    const progressValue = container.querySelector(".mtf-progress-value");
    if (progressValue) {
        progressValue.style.right = 100 - data.progress + "%";
        progressValue.textContent = Math.round(data.progress) + "%";
    }

    // Update message
    const messageElement = container.querySelector(
        ".mtf-free-shipping-message"
    );
    if (messageElement) {
        if (data.has_free_shipping) {
            messageElement.className = "mtf-free-shipping-message success";
            messageElement.innerHTML = `
                <i class="material-icons">local_shipping</i>
                <span>Congratulations! You get FREE shipping!</span>
            `;
        } else {
            messageElement.className = "mtf-free-shipping-message";
            const remainingFormatted = formatPrice(data.remaining_amount);
            messageElement.innerHTML = `
                <i class="material-icons">local_shipping</i>
                <span>Add <strong>${remainingFormatted}${data.currency_sign}</strong> more to get FREE shipping!</span>
            `;
        }
    }

    // Animate the progress bar update
    animateProgressBar();
}

/**
 * Format price number
 */
function formatPrice(price) {
    return parseFloat(price)
        .toFixed(2)
        .replace(/\d(?=(\d{3})+\.)/g, "$&,");
}

/**
 * Add animation to progress bar
 */
function animateProgressBar() {
    const progressBar = document.querySelector(".mtf-progress-bar");
    if (progressBar) {
        progressBar.classList.add("animated");
        setTimeout(function () {
            progressBar.classList.remove("animated");
        }, 500);
    }
}
