<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Mtf_FreePrice extends Module
{
    public function __construct()
    {
        $this->name = 'mtf_freeprice';
        $this->version = '1.0.0';
        $this->author = 'MTFibertech';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Livraison Gratuite - Montant restant', [], 'Modules.MtfFreePrice.Admin');
        $this->description = $this->trans('Affiche le montant restant pour obtenir la livraison gratuite.');

        $this->confirmUninstall = $this->trans('Êtes-vous sûr de vouloir désinstaller ce module ?', [], 'Modules.MtfFreePrice.Admin');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayShoppingCartFooter') // Affichage dans le panier
            && $this->registerHook('displayHeader') // hook pour ajouter le fichier css dans le head
            && Configuration::updateValue('MTF_FREEPRICE_THRESHOLD', 50); // Seuil par défaut
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('MTF_FREEPRICE_THRESHOLD');
    }

    public function hookDisplayHeader()
    {
        if ($this->context->controller->php_self === 'cart') {
            $this->context->controller->registerStylesheet(
                'mtf_freeprice-css',
                'modules/'.$this->name.'/assets/css/style.css',
                ['media' => 'all', 'priority' => 150]
            );
        }
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMtfFreePrice')) {
            $threshold = (float)Tools::getValue('MTF_FREEPRICE_THRESHOLD');
            Configuration::updateValue('MTF_FREEPRICE_THRESHOLD', $threshold);
            $output .= $this->displayConfirmation($this->trans('Seuil mis à jour avec succès !', [], 'Admin.Notifications.Success'));
        }

        return $output . $this->renderForm();
    }

    private function renderForm()
    {
        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Paramètres', [], 'Admin.Global'),
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Seuil de livraison gratuite (€)', [], 'Admin.Global'),
                        'name' => 'MTF_FREEPRICE_THRESHOLD',
                        'size' => 5,
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Enregistrer', [], 'Admin.Actions'),
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->submit_action = 'submitMtfFreePrice';
        $helper->fields_value['MTF_FREEPRICE_THRESHOLD'] = Configuration::get('MTF_FREEPRICE_THRESHOLD');

        return $helper->generateForm([$fieldsForm]);
    }

    public function hookDisplayShoppingCartFooter($params)
    {
        $cart = $this->context->cart;
        $freeShippingThreshold = (float)Configuration::get('MTF_FREEPRICE_THRESHOLD');

        // Récupère le total TTC des produits uniquement (sans les frais de livraison)
        $cartTotalProducts = $cart->getOrderTotal(true, Cart::ONLY_PRODUCTS);
        $remaining = max(0, $freeShippingThreshold - $cartTotalProducts);

        $this->context->smarty->assign([
            'remaining' => $remaining,
            'cart_total_products' => $cartTotalProducts,
            'free_shipping_threshold' => $freeShippingThreshold
        ]);

        return $this->display(__FILE__, 'views/templates/hook/free_shipping.tpl');
    }
}