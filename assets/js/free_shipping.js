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

// Track if we've already shown the celebration animation
let celebrationShown = false;

// Prevent multiple simultaneous AJAX requests
let ajaxInProgress = false;

// Track the last update time to prevent too frequent requests
let lastUpdateTime = 0;

document.addEventListener("DOMContentLoaded", function () {
    // Initialize free shipping progress bar
    initFreeShippingBar();

    // Add class for electrical products theme
    addElectricalThemeClass();

    // Listen for prestashop cart events
    listenToCartEvents();

    // Initial update on page load
    updateFreeShippingBar();

    // For debugging
    console.log("Free shipping progress bar initialized");
});

/**
 * Initialize the free shipping progress bar
 */
function initFreeShippingBar() {
    // Nothing specific needed for initialization
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
            updateFreeShippingBar();
        });
    });

    // Add event listeners to quantity inputs and update buttons
    addQuantityChangeListeners();
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
            updateFreeShippingBar();
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
            event.target.closest(".js-cart-action-button")
        ) {
            updateFreeShippingBar();
        }
    });

    // Add a change event listener to the entire document to catch quantity input changes
    document.addEventListener("change", function (event) {
        if (event.target.classList.contains("js-cart-line-product-quantity")) {
            updateFreeShippingBar();
        }
    });
}

/**
 * Update free shipping progress bar via AJAX
 */
function updateFreeShippingBar() {
    // Prevent updating too frequently (at least 1000ms between updates)
    const now = Date.now();
    if (now - lastUpdateTime < 1000) {
        console.log("Skipping update, too frequent");
        return;
    }

    // Prevent multiple simultaneous requests
    if (ajaxInProgress) {
        console.log("Skipping update, AJAX already in progress");
        return;
    }

    ajaxInProgress = true;
    lastUpdateTime = now;

    if (typeof mtf_freeprice_ajax_url === "undefined") {
        console.error("Free shipping module AJAX URL not defined");
        ajaxInProgress = false;
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
            ajaxInProgress = false;
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
            ajaxInProgress = false;
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

    // Get translations (with fallbacks)
    const translations = window.mtf_freeprice_translations || {};
    const congratsText =
        translations.congratulations ||
        "Congratulations! You get FREE shipping!";
    const addText = translations.add || "Add";
    const moreToGetText =
        translations.more_to_get || "more to get FREE shipping!";

    // Check if we have free shipping (100% progress)
    if (data.has_free_shipping) {
        // Hide progress bar elements when at 100%
        const progressContainer = container.querySelector(
            ".mtf-progress-container"
        );
        if (progressContainer) {
            progressContainer.style.display = "none";
        }

        const progressLabels = container.querySelector(".mtf-progress-labels");
        if (progressLabels) {
            progressLabels.style.display = "none";
        }

        // Show only the success message with larger styling
        const messageElement = container.querySelector(
            ".mtf-free-shipping-message"
        );
        if (messageElement) {
            // Only update the DOM if necessary
            if (!messageElement.classList.contains("success")) {
                messageElement.className = "mtf-free-shipping-message success";
                messageElement.style.fontSize = "1.2em";
                messageElement.style.padding = "10px 0";
                messageElement.style.justifyContent = "center";
                // Remove bottom margin since the progress bar is hidden
                messageElement.style.marginBottom = "0";
                messageElement.innerHTML = `
                    <i class="material-icons" style="font-size: 28px;">local_shipping</i>
                    <span>${congratsText}</span>
                `;

                // Only play celebration animation once per page load
                if (!celebrationShown) {
                    celebrationShown = true;

                    // Use requestAnimationFrame for smoother animations
                    requestAnimationFrame(function () {
                        messageElement.classList.add("celebrate");

                        // Remove animation class after it completes to prevent repeating
                        setTimeout(function () {
                            messageElement.classList.remove("celebrate");
                        }, 600);
                    });
                }
            }
        }
    } else {
        // Reset celebration flag when we're no longer at 100%
        celebrationShown = false;

        // Show progress bar when not at 100%
        const progressContainer = container.querySelector(
            ".mtf-progress-container"
        );
        if (progressContainer) {
            progressContainer.style.display = "block";
        }

        const progressLabels = container.querySelector(".mtf-progress-labels");
        if (progressLabels) {
            progressLabels.style.display = "flex";
        }

        // Use requestAnimationFrame for smoother updates
        requestAnimationFrame(function () {
            // Update progress bar percentage
            const progressBar = container.querySelector(".mtf-progress-bar");
            if (progressBar) {
                progressBar.style.width = data.progress + "%";
            }

            // Update progress value position and text in one operation
            const progressValue = container.querySelector(
                ".mtf-progress-value"
            );
            if (progressValue) {
                const progressPercent = Math.round(data.progress);
                if (progressValue.textContent !== progressPercent + "%") {
                    progressValue.style.right = 100 - data.progress + "%";
                    progressValue.textContent = progressPercent + "%";
                }
            }

            // Update the normal message
            const messageElement = container.querySelector(
                ".mtf-free-shipping-message"
            );
            if (
                messageElement &&
                !messageElement.classList.contains("normal-state-applied")
            ) {
                messageElement.className =
                    "mtf-free-shipping-message normal-state-applied";
                messageElement.style.fontSize = "";
                messageElement.style.padding = "";
                messageElement.style.justifyContent = "";
                // Restore the bottom margin for normal state
                messageElement.style.marginBottom = "15px";

                const remainingFormatted = formatPrice(data.remaining_amount);
                messageElement.innerHTML = `
                    <i class="material-icons">local_shipping</i>
                    <span>${addText} <strong>${remainingFormatted}${data.currency_sign}</strong> ${moreToGetText}</span>
                `;
            } else if (
                messageElement &&
                messageElement.classList.contains("normal-state-applied")
            ) {
                // If we're already in normal state, just update the amount text to avoid full DOM replacement
                const amountElement = messageElement.querySelector("strong");
                if (amountElement) {
                    const remainingFormatted = formatPrice(
                        data.remaining_amount
                    );
                    amountElement.textContent =
                        remainingFormatted + data.currency_sign;
                }
            }
        });
    }
}

/**
 * Format price number
 */
function formatPrice(price) {
    // Parse to ensure it's a number and round to 2 decimal places
    return parseFloat(price)
        .toFixed(2)
        .replace(/\d(?=(\d{3})+\.)/g, "$&,");
}
