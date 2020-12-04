<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div data-vue-role="cart-main" data-vue-inline-template data-v-bind_title="''" data-v-bind_remote-cart-id="'cart'" data-v-on_delete="requestItemDelete($event, 'cart')">
  <div>
    <a href="/cart/" class="cart-main" rel="nofollow" data-v-bind_class="{ 'cart-main_active': dataLoaded }">
      <span class="cart-main__amount" data-v-if="amount > 0" data-v-html="amount"></span>
      <span class="cart-main__text">
        <span class="cart-main__title"><?php echo CART?></span>
        <span class="cart-main__sum-outer">
          <span class="cart-main__sum" data-v-html="formatPrice(sum)"></span>
          <span class="cart-main__sum-currency">₽</span>
        </span>
      </span>
    </a>
    <!-- <cart-main-list data-v-if="listActive" data-v-bind_items="items" data-v-bind_amount="amount" data-v-bind_sum="sum" data-v-on_close="listActive = false;" data-v-on_delete="$emit('delete', $event)"></cart-main-list> -->
  </div>
</div>
<!--/noindex-->
