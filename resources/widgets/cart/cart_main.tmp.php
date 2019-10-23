<?php
/**
 * Виджет информера корзины
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<a href="/cart/" data-role="cart-block" class="cart-main" style="display: none" rel="nofollow">
  <span class="cart-main__amount" data-role="cart-block-amount"></span>
  <span class="cart-main__text">
    <span class="cart-main__title"><?php echo CART?></span>
    <span class="cart-main__sum-outer">
      <span class="cart-main__sum" data-role="cart-block-sum"></span>
      <span class="cart-main__sum-currency">
        ₽
      </span>
    </span>
  </span>
</a>
<!--/noindex-->
