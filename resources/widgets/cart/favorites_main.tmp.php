<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<a href="/favorites/" class="favorites-main" data-v-bind_class="{ 'favorites-main_active': favorites.dataLoaded }" title="<?php echo FAVORITES?>">
  <span class="favorites-main__amount" data-v-if="favorites.count > 0" data-v-html="favorites.count"></span>
</a>
<!--/noindex-->
