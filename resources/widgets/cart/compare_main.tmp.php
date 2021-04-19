<?php
/**
 * Виджет информера сравнения
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<a href="/compare/" class="compare-main" data-v-bind_class="{ 'compare-main_active': compare.dataLoaded }" title="<?php echo COMPARE?>">
  <span class="compare-main__amount" data-v-if="compare.count > 0" data-v-html="compare.count"></span>
</a>
<!--/noindex-->
