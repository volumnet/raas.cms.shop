<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div class="cart-main__outer" data-vue-role="cart-main" data-v-bind_cart="cart" data-v-bind_title="'<?php echo CART?>'" data-v-bind_href="'/cart/'">
  <span class="cart-main">
    <span class="cart-main__text">
      <span class="cart-main__sum-outer"></span>
    </span>
  </span>
</div>
<!--/noindex-->
