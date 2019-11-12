<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<script type="text/html" id="raas-cart-main-template">
  <a href="/cart/" class="cart-main" rel="nofollow" :class="{ 'cart-main_active': dataLoaded }">
    <span class="cart-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="cart-main__text">
      <span class="cart-main__title"><?php echo CART?></span>
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum">
          {{ formatPrice(sum) }}
        </span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
</script>

<div data-vue-role="raas-cart-main"></div>
<!--/noindex-->
