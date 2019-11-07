<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<!--noindex-->
<template id="raas-favorites-main-template">
  <a href="/favorites/" class="favorites-main" rel="nofollow" v-bind:class="{ 'favorites-main_active': dataLoaded }" rel="nofollow">
    <span class="favorites-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="favorites-main__text">
      <span class="favorites-main__title"><?php echo FAVORITES?></span>
    </span>
  </a>
</template>

<div data-vue-role="raas-favorites-main"></div>
<!--/noindex-->
