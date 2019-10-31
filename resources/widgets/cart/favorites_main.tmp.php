<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<template id="raas-favorites-main-template">
  <a href="/favorites/" class="favorites-main" rel="nofollow" v-bind:class="{ 'favorites-main_active': dataLoaded }" rel="nofollow">
    <span class="favorites-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="favorites-main__text">
      <span class="favorites-main__title"><?php echo FAVORITES?></span>
    </span>
  </a>
</template>

<div data-vue-role="raas-favorites-main"></div>
<!--/noindex-->

<script>
jQuery(document).ready(function($) {
    raasShopFavoritesMain = new Vue({
        el: 'raas-favorites-main',
        template: '#raas-favorites-main-template',
        data: function () {
            return {
                dataLoaded: false,
                amount: 0,
            }
        },
        mounted: function () {
            var self = this;
            $(document).on('raas.shop.cart-updated', function (e, data) {
                if ((data.id == 'favorites') && data.remote) {
                    self.amount = data.data.count;
                    self.dataLoaded = true;
                }
            });
        },
        methods: {
            numTxt: window.numTxt,
        },
        computed: {
            amountText: function () {
                return window.numTxt(
                    this.amount,
                    ['товаров', 'товар', 'товара']
                );
            },
        },
    });
});
</script>
