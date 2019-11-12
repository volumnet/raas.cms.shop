<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<script type="text/html" id="raas-favorites-main-template">
  <a href="/favorites/" class="favorites-main" rel="nofollow" :class="{ 'favorites-main_active': dataLoaded }">
    <span class="favorites-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="favorites-main__text">
      <span class="favorites-main__title"><?php echo FAVORITES?></span>
    </span>
  </a>
</script>

<div data-vue-role="raas-favorites-main"></div>
<!--/noindex-->
