<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div data-vue-role="cart-main" data-v-bind_remote-cart-id="'cart'" data-v-on_delete="requestItemDelete($event, 'cart')" data-v-slot="vm">
  <a href="/cart/" class="cart-main" rel="nofollow" data-v-bind_class="{ 'cart-main_active': vm.dataLoaded }">
    <span class="cart-main__amount" data-v-if="vm.amount > 0" data-v-html="vm.amount"></span>
    <span class="cart-main__text">
      <span class="cart-main__title"><?php echo CART?></span>
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum" data-v-html="vm.formatPrice(sum)"></span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
  <div data-vue-role="cart-main-list" data-v-if="vm.listActive" data-v-bind_items="vm.items" data-v-bind_amount="vm.amount" data-v-bind_sum="vm.sum" data-v-on_close="vm.closeList()" data-v-on_delete="vm.emit('delete', $event)"></div>
</div>
<!--/noindex-->
