<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<div data-vue-role="favorites-main" data-vue-inline-template data-v-bind_remote-cart-id="'favorites'">
  <a href="/favorites/" class="favorites-main" data-v-bind_class="{ 'favorites-main_active': dataLoaded }">
    <span class="favorites-main__amount" data-v-if="amount > 0" data-v-html="amount"></span>
    <span class="favorites-main__text">
      <span class="favorites-main__title"><?php echo FAVORITES?></span>
    </span>
  </a>
</div>
<!--/noindex-->
