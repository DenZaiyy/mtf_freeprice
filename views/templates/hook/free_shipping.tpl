{*
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
*}

<div class="mtf-free-shipping-container card mb-3" id="mtf-free-shipping-bar">
    <div class="card-body">
        <div class="mtf-free-shipping-inner">
            {if $has_free_shipping}
                <div class="mtf-free-shipping-message success">
                    <i class="material-icons">local_shipping</i>
                    <span>{l s='Congratulations! You get FREE shipping!' mod='mtf_freeprice'}</span>
                </div>
            {else}
                <div class="mtf-free-shipping-message">
                    <i class="material-icons">local_shipping</i>
                    <span>{l s='Add' mod='mtf_freeprice'}
                        <strong>{$remaining_amount|number_format:2:'.':','}{$currency_sign}</strong>
                        {l s='more to get FREE shipping!' mod='mtf_freeprice'}</span>
                </div>
            {/if}

            <div class="mtf-progress-container">
                <div class="mtf-progress-bar" style="width: {$progress}%;">
                    <span class="mtf-progress-value" style="right: {100 - $progress}%;">
                        {$progress|number_format:0}%
                    </span>
                </div>
            </div>

            <div class="mtf-progress-labels">
                <span class="mtf-progress-start">0{$currency_sign}</span>
                <span class="mtf-progress-end">{$free_shipping_amount}{$currency_sign}</span>
            </div>
        </div>
    </div>
</div>