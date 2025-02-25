# PrestaShop Module – MTF FreePrice  

This is a PrestaShop module (created for v8.2) to encourage customers to have a shopping cart without delivery charges before finalizing their order.  

## Directory Structure  

```
mtf_freeprice/
├─ assets/
│  ├─ css/
│  │  └─ style.css
│  └─ js/
│     └─ free_shipping.js
├─ controllers/
│  └─ front/
│     └─ ajaxFreeShipping.php
├─ mtf_freeprice.php
├─ translations/
│  └─ fr.php
└─ views/
   └─ templates/
      └─ hook/
         └─ free_shipping.tpl
```

## Features  

- Displays a progress bar showing how close customers are to free shipping
- Dynamic updates when cart contents change without page refresh
- Clean, mobile-responsive design with customizable styling
- Animated indicators and success message when free shipping threshold is achieved
- Automatically hides when cart is empty
- Supports multiple languages through PrestaShop's translation system
- Works with electrical products theme (blue color scheme)
- AJAX updates for seamless user experience
- Compatible with PrestaShop 8.0 and higher

## Installation  

1. Upload the module to your PrestaShop installation.  
2. Install and activate it from the **Modules Manager**.  
3. Configure the module settings if needed.
4. The module will automatically display in the shopping cart.

## Configuration
1. Go to the module configuration page in your PrestaShop back office
2. Set the minimum cart amount to qualify for free shipping
3. Save your configuration

## Translations
The module supports multiple languages through PrestaShop's translation system:
1. Go to International > Translations in your PrestaShop back office
2. Select "Modify translations" and choose your desired language
3. Select "Installed modules translations"
4. Find "MTF FreePrice" in the dropdown list and translate all strings

## Notes  
- The module can be only used with displayShoppingCartFooter hook
- Requires JavaScript to be enabled in the browser
- Uses Material Icons from PrestaShop's theme
- Compatible with all standard PrestaShop themes
- Tested with PrestaShop 8.0 - 8.2

## Support
For support, bug reports, or feature requests, please open an issue on the GitHub repository.

## Credits
Developed by <b>Kevin GRISCHKO</b> for [Elecie Store](https://elecie.store/)
