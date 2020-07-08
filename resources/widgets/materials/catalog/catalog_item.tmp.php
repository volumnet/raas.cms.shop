<?php
/**
 * Виджет товара в списке
 * @param Material $item Товар для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Text;

?>
<div data-vue-role="raas-shop-catalog-item" data-inline-template class="catalog-item" data-v-bind_id="<?php echo (int)$item->id?>" data-v-bind_price="<?php echo (float)$item->price?>" data-v-bind_priceold="<?php echo (float)($item->price_old ?: $item->price)?>" data-v-bind_min="<?php echo $item->min || 1?>" data-v-bind_step="<?php echo $item->step || 1?>" data-v-bind_image="<?php echo htmlspecialchars($item->visImages ? ('/' . $item->visImages[0]->smallURL) : '')?>">
  <div>
    <a href="<?php echo $item->url?>" class="catalog-item__image<?php echo !$item->visImages ? ' catalog-item__image_nophoto' : ''?>">
      <?php if ($item->visImages) { ?>
          <img src="/<?php echo htmlspecialchars(addslashes($item->visImages[0]->smallURL))?>" alt="<?php echo htmlspecialchars($item->visImages[0]->name ?: $item->name)?>" />
      <?php } ?>
    </a>
    <div class="catalog-item__title">
      <a href="<?php echo $item->url?>">
        <?php echo htmlspecialchars($item->name)?>
      </a>
    </div>
    <div class="catalog-item__price-container">
      <span class="catalog-item__price <?php echo ($item->price_old && ($item->price_old != $item->price)) ? ' catalog-item__price_new' : ''?>">
        <span data-v-html="formatPrice(price * amount)">
          <?php echo Text::formatPrice((float)$item->price)?>
        </span> ₽
      </span>
      <?php if ($item->price_old && ($item->price_old != $item->price)) { ?>
          <span class="catalog-item__price catalog-item__price_old" data-v-html="formatPrice(priceold * amount)">
            <?php echo Text::formatPrice((float)$item->price_old)?>
          </span>
      <?php } ?>
    </div>
    <div class="catalog-item__controls-outer">
      <div class="catalog-item__available catalog-item__available_<?php echo $item->available ? '' : 'not-'?>available">
        <?php echo $item->available ? 'В наличии' : 'Под заказ'?>
      </div>
      <div class="catalog-item__controls">
        <!--noindex-->
        <?php if ($item->available) { ?>
            <div class="catalog-item__amount-block">
              <a class="catalog-item__decrement" data-v-on_click="amount -= step; checkAmount();">–</a>
              <input type="number" class="catalog-item__amount" autocomplete="off" name="amount" min="<?php echo (int)$item->min ?: 1?>" step="<?php echo (int)$item->step ?: 1?>" value="<?php echo (int)$item->min ?: 1?>" data-v-model="amount" data-v-on_change="checkAmount()" />
              <a class="catalog-item__increment" data-v-on_click="amount = parseInt(amount) + parseInt(step); checkAmount();">+</a>
            </div>
            <button type="button" data-v-on_click="addToCart()" class="catalog-item__add-to-cart" title="<?php echo TO_CART?>"></button>
            <!-- <button type="button" data-v-on_click="toggleCart()" class="catalog-item__add-to-cart" data-v-bind_class="{ 'catalog-item__add-to-cart_active': inCart}" data-v-bind_title="inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo TO_CART?>'"></button> -->
        <?php } ?>
        <button type="button" data-v-on_click="toggleFavorites()" class="catalog-item__add-to-favorites" data-v-bind_class="{ 'catalog-item__add-to-favorites_active': inFavorites}" data-v-bind_title="inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'"></button>
        <!--/noindex-->
      </div>
    </div>
  </div>
</div>
