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
        $this->author = 'Your Name';
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
        Configuration::updateValue('MTF_FREE_SHIPPING_AMOUNT', 100);
        
        return parent::install() &&
            $this->registerHook('displayShoppingCartFooter') &&
            $this->registerHook('header');
    }

    public function uninstall()
    {
        Configuration::deleteByName('MTF_FREE_SHIPPING_AMOUNT');
        
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submit' . $this->name)) {
            $freeShippingAmount = (float) Tools::getValue('MTF_FREE_SHIPPING_AMOUNT');
            
            if ($freeShippingAmount <= 0) {
                $output .= $this->displayError($this->l('Invalid amount'));
            } else {
                Configuration::updateValue('MTF_FREE_SHIPPING_AMOUNT', $freeShippingAmount);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        
        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Free shipping amount'),
                        'name' => 'MTF_FREE_SHIPPING_AMOUNT',
                        'suffix' => Context::getContext()->currency->sign,
                        'desc' => $this->l('Set the amount needed to reach free shipping'),
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name .
            '&tab_module=' . $this->tab .
            '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => ['MTF_FREE_SHIPPING_AMOUNT' => Configuration::get('MTF_FREE_SHIPPING_AMOUNT')],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
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
        $freeShippingAmount = (float) Configuration::get('MTF_FREE_SHIPPING_AMOUNT');
        
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

        // Add more details to JavaScript definitions
        Media::addJsDef([
            'mtf_freeprice_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajaxFreeShipping'),
            'free_shipping_amount' => (float) Configuration::get('MTF_FREE_SHIPPING_AMOUNT'),
            'mtf_freeprice_translations' => [
                'congratulations' => $this->l('Congratulations! You get FREE shipping!'),
                'add' => $this->l('Add'),
                'more_to_get' => $this->l('more to get FREE shipping!')
            ]
        ]);
    }
}