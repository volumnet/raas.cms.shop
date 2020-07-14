<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>

<script type="text/html" id="cart-main-template" data-v-pre>
  <a href="/cart/" class="cart-main" rel="nofollow" :class="{ 'cart-main_active': dataLoaded }">
    <span class="cart-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="cart-main__text">
      <span class="cart-main__title">{{title}}</span>
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum">
          {{ formatPrice(sum) }}
        </span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
</script>
<!--noindex-->
<div data-vue-role="cart-main" data-v-bind_title="'<?php echo CART?>'" data-v-bind_remote-cart-id="'cart'"></div>
<!--/noindex-->
