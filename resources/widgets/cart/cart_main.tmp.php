<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div class="cart-main__outer" data-vue-role="cart-main" data-v-bind_cart="cart" data-v-slot="vm">
  <a href="/cart/" class="cart-main" rel="nofollow" data-v-bind_class="{ 'cart-main_active': cart.dataLoaded }" title="<?php echo CART?>" data-v-on_click="vm.clickInformer($event)">
    <span class="cart-main__amount" data-v-if="cart.count > 0" data-v-html="cart.count"></span>
    <span class="cart-main__text">
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum" data-v-html="formatPrice(cart.sum)"></span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
</div>
<!--/noindex-->
