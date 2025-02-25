{strip}
    {if $free_shipping_threshold > 0}
        <div class="free-shipping-progress alert alert-info">
            {if $remaining > 0}
                <p>Ajoutez encore <strong>{$remaining|number_format:2:'.':' '}€</strong> pour bénéficier de la livraison gratuite
                    !&nbsp;
                    🚚</p>
                <progress value="{$cart_total_products}" max="{$free_shipping_threshold}"></progress>
            {else}
                <p>🎉 Félicitations ! Vous bénéficiez de la livraison gratuite ! 🚀</p>
            {/if}
        </div>
    {/if}
{/strip}