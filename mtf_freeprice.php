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

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mtf_Freeprice extends Module
{
    public function __construct()
    {
        $this->name = 'mtf_freeprice';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'MTFibertech';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('MTF - Free Shipping Progress Bar');
        $this->description = $this->l('Display a progress bar in cart showing how close customers are to free shipping');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        Configuration::updateValue('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT', 50);

        return parent::install() &&
            $this->installTab() &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT');

        return parent::uninstall() &&
            $this->uninstallTab();
    }

    /**
     * Install Tab
     */
    public function installTab()
    {
        // First check if mtf_tabs module exists and is installed
        if (!Module::isInstalled('mtf_tabs')) {
            // Fallback to IMPROVE tab
            $tabRepository = $this->get('prestashop.core.admin.tab.repository');
            $improveTab = $tabRepository->findOneByClassName('IMPROVE');
            $parentId = $improveTab ? $improveTab->getId() : 0;
        } else {
            // Instancier le module mtf_tabs pour accéder à ses méthodes
            $mtfTabsModule = Module::getInstanceByName('mtf_tabs');

            // Vérifier si la classe est disponible avant d'essayer d'utiliser ses méthodes
            if (method_exists('Mtf_Tabs', 'getConfigureTabId')) {
                $configureId = Mtf_Tabs::getConfigureTabId();

                if (!$configureId && method_exists('Mtf_Tabs', 'getParentTabId')) {
                    $configureId = Mtf_Tabs::getParentTabId();
                }

                $parentId = $configureId ?: 0;
            } else {
                // Fallback: récupérer directement l'ID depuis la base de données
                $sql = 'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE class_name = "AdminMTFConfigure"';
                $configureId = Db::getInstance()->getValue($sql);

                if (!$configureId) {
                    $sql = 'SELECT id_tab FROM ' . _DB_PREFIX_ . 'tab WHERE class_name = "AdminMTFModules"';
                    $configureId = Db::getInstance()->getValue($sql);
                }

                $parentId = $configureId ?: 0;
            }
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminMtfFreePrice';
        $tab->name = [];

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Free Price';
        }

        $tab->id_parent = $parentId;
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Uninstall Tab
     */
    public function uninstallTab()
    {
        $tabRepository = $this->get('prestashop.core.admin.tab.repository');

        try {
            $tab = $tabRepository->findOneByClassName('AdminMtfFreePrice');
            if ($tab) {
                $tabPS = new Tab($tab->getId());
                return $tabPS->delete();
            }
        } catch (Exception $e) {
            // Tab not found, nothing to delete
        }

        return true;
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        if (!$this->active) {
            return;
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return;
        }

        // Check if cart is empty
        if ($cart->nbProducts() <= 0) {
            return; // Don't display anything for empty carts
        }

        $cartTotal = $cart->getOrderTotal(true, Cart::BOTH_WITHOUT_SHIPPING);
        $freeShippingAmount = (float) Configuration::get('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT');

        $remainingAmount = max(0, $freeShippingAmount - $cartTotal);
        $progress = min(100, ($cartTotal / $freeShippingAmount) * 100);

        $this->context->smarty->assign([
            'free_shipping_amount' => $freeShippingAmount,
            'cart_total' => $cartTotal,
            'remaining_amount' => $remainingAmount,
            'progress' => $progress,
            'currency_sign' => $this->context->currency->sign,
            'has_free_shipping' => $remainingAmount <= 0,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/free_shipping.tpl');
    }

    public function hookHeader()
    {
        if (!$this->active) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'mtf-freeprice-style',
            'modules/' . $this->name . '/assets/css/style.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'mtf-freeprice-js',
            'modules/' . $this->name . '/assets/js/free_shipping.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        // Pass translations to JavaScript
        Media::addJsDef([
            'mtf_freeprice_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajaxFreeShipping'),
            'free_shipping_amount' => (float) Configuration::get('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT'),
            'mtf_freeprice_translations' => [
                'congratulations' => $this->l('Congratulations! You get FREE shipping!'),
                'add' => $this->l('Add'),
                'more_to_get' => $this->l('more to get FREE shipping!'),
                'free_shipping' => $this->l('FREE shipping'),
                'percent_complete' => $this->l('% complete')
            ]
        ]);
    }
}
