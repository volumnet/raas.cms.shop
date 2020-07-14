<?php
/**
 * Виджет информера избранного
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS\Shop;

?>
<script type="text/html" id="favorites-main-template" data-v-pre>
  <a href="/favorites/" class="favorites-main" rel="nofollow" :class="{ 'favorites-main_active': dataLoaded }">
    <span class="favorites-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="favorites-main__text">
      <span class="favorites-main__title">{{title}}</span>
    </span>
  </a>
</script>
<!--noindex-->
<div data-vue-role="favorites-main" data-v-bind_title="'<?php echo FAVORITES?>'" data-v-bind_remote-cart-id="'favorites'"></div>
<!--/noindex-->
