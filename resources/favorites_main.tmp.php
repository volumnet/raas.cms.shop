<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<a href="/favorites/" data-role="favorites-block" class="favorites-main" style="display: none" rel="nofollow">
  <span class="favorites-main__amount" data-role="favorites-block-amount"></span>
  <span class="favorites-main__text">
    <span class="favorites-main__title"><?php echo FAVORITES?></span>
  </span>
</a>
<!--/noindex-->
