<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<template id="raas-cart-main-template">
  <a href="/cart/" class="cart-main" rel="nofollow" v-bind:class="{ 'cart-main_active': dataLoaded }" rel="nofollow">
    <span class="cart-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="cart-main__text">
      <span class="cart-main__title"><?php echo CART?></span>
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum" data-role="cart-block-sum">
          {{ formatPrice(sum) }}
        </span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
</template>

<div data-vue-role="raas-cart-main"></div>
<!--/noindex-->

<script>
jQuery(document).ready(function($) {
    raasShopCartMain = new Vue({
        el: 'raas-cart-main',
        template: '#raas-cart-main-template',
        data: function () {
            return {
                dataLoaded: false,
                amount: 0,
                sum: 0,
            }
        },
        mounted: function () {
            var self = this;
            $(document).on('raas.shop.cart-updated', function (e, data) {
                if ((data.id == 'cart') && data.remote) {
                    self.sum = data.data.sum;
                    self.amount = data.data.count;
                    self.dataLoaded = true;
                }
            });
        },
        methods: {
            formatPrice: window.formatPrice,
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
