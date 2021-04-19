<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div class="cart-main__outer">
  <a href="/cart/" class="cart-main" rel="nofollow" data-v-bind_class="{ 'cart-main_active': cart.dataLoaded }" title="<?php echo CART?>">
    <span class="cart-main__amount" data-v-if="cart.count > 0" data-v-html="cart.count"></span>
    <span class="cart-main__text">
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum" data-v-html="formatPrice(cart.sum)"></span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
  <?php /*<div data-vue-role="cart-main-list" data-v-if="vm.listActive" data-v-bind_items="vm.items" data-v-bind_amount="vm.amount" data-v-bind_sum="vm.sum" data-v-on_close="vm.closeList()" data-v-on_delete="vm.emit('delete', $event)"></div>*/?>
</div>
<!--/noindex-->
