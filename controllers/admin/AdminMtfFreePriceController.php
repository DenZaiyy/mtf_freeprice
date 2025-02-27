<?php

/**
 * 2007-2025 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    Your Name <your.email@domain.com>
 * @copyright 2007-2025 Your Company
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMtfFreePriceController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;

        parent::__construct();

        $this->meta_title = $this->l('FreePrice Settings');
    }

    /**
     * Initialize the content
     */
    public function initContent()
    {
        $this->content = $this->renderForm();

        parent::initContent();
    }

    /**
     * Render the configuration form
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Free price shopping configuration'),
                    'icon' => 'icon-money'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Free shipping amount'),
                        'name' => 'MTF_FREEPRICE_FREE_SHIPPING_AMOUNT',
                        'suffix' => Context::getContext()->currency->sign,
                        'desc' => $this->l('Set the amount needed to reach free shipping'),
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitMtfFreePriceConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminMtfFreePrice', false);
        $helper->token = Tools::getAdminTokenLite('AdminMtfFreePrice');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Get config form values
     */
    protected function getConfigFormValues()
    {
        return [
            'MTF_FREEPRICE_FREE_SHIPPING_AMOUNT' => Configuration::get('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT', 50),
            // Add more fields as needed
        ];
    }

    /**
     * Process the form submission
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitMtfFreePriceConfig')) {
            // Save configuration values
            Configuration::updateValue('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT', (int)Tools::getValue('MTF_FREEPRICE_FREE_SHIPPING_AMOUNT'));
            // Add more fields as needed

            $this->confirmations[] = $this->l('Settings updated successfully.');

            // Instead of using _clearCache, use a different approach
            // Option 1: Call the module's public cache clearance methods if available
            if (method_exists($this->module, 'clearCache')) {
                $this->module->clearCache();
            }

            // Option 2: Use Cache::clean if you know the pattern
            elseif (class_exists('Cache')) {
                Cache::clean('mtf_freeprice_*');
            }
        }

        parent::postProcess();
    }
}
