<?php

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

class Mtf_FreepriceAjaxFreeShippingModuleFrontController extends ModuleFrontController
{
    public function init()
    {
        parent::init();

        // Disable caching for AJAX responses
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        if (!$this->module->active) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Module not active'
            ]));
        }
    }

    public function postProcess()
    {
        try {
            $cart = $this->context->cart;
            if (!Validate::isLoadedObject($cart)) {
                // Return valid JSON even if cart is invalid
                $this->ajaxDie(json_encode([
                    'success' => false,
                    'message' => 'Invalid cart',
                    'data' => [
                        'free_shipping_amount' => 0,
                        'cart_total' => 0,
                        'remaining_amount' => 0,
                        'progress' => 0,
                        'currency_sign' => $this->context->currency->sign,
                        'has_free_shipping' => false,
                        'cart_empty' => true,
                    ]
                ]));
                return;
            }

            // Check if cart is empty
            $nbProducts = $cart->nbProducts();
            $cartTotal = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
            $freeShippingAmount = (float) Configuration::get('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT');

            if ($freeShippingAmount <= 0) {
                $freeShippingAmount = 50; // Default value if not set
            }

            $remainingAmount = max(0, $freeShippingAmount - $cartTotal);
            $progress = ($freeShippingAmount > 0) ? min(100, ($cartTotal / $freeShippingAmount) * 100) : 100;

            $this->ajaxDie(json_encode([
                'success' => true,
                'data' => [
                    'free_shipping_amount' => $freeShippingAmount,
                    'cart_total' => $cartTotal,
                    'remaining_amount' => $remainingAmount,
                    'progress' => $progress,
                    'currency_sign' => $this->context->currency->sign,
                    'has_free_shipping' => $remainingAmount <= 0,
                    'cart_empty' => ($nbProducts <= 0),
                ]
            ]));
        } catch (Exception $e) {
            // Return valid JSON even if an exception occurs
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
                'data' => [
                    'free_shipping_amount' => 0,
                    'cart_total' => 0,
                    'remaining_amount' => 0,
                    'progress' => 0,
                    'currency_sign' => $this->context->currency->sign,
                    'has_free_shipping' => false,
                    'cart_empty' => true,
                ]
            ]));
        }
    }

    public function display()
    {
        // This is an AJAX request, so nothing to display
    }
}
