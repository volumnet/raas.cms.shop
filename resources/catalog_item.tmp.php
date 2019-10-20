<?php
/**
 * Виджет товара в списке
 * @param Material $item Материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Text;

?>
<div class="catalog-item">
  <div class="catalog-item__title">
    <a href="<?php echo $item->url?>">
      <?php echo htmlspecialchars($item->name)?>
    </a>
  </div>
  <?php if ($item->article) { ?>
      <div class="catalog-item__article">
        <?php echo ARTICLE_SHORT?>
        <a href="<?php echo $item->url?>">
          <?php echo htmlspecialchars($item->article)?>
        </a>
      </div>
  <?php } ?>
  <a href="<?php echo $item->url?>" class="catalog-item__image<?php echo !$item->visImages ? ' catalog-item__image_nophoto' : ''?>">
    <?php if ($item->visImages) { ?>
        <img src="/<?php echo htmlspecialchars(addslashes($item->visImages[0]->smallURL))?>" alt="<?php echo htmlspecialchars($item->visImages[0]->name ?: $item->name)?>" />
    <?php } ?>
  </a>
  <div class="catalog-item__text">
    <div class="catalog-item__price-container">
      <?php if ($item->price_old && ($item->price_old != $item->price)) { ?>
          <span class="catalog-item__price catalog-item__price_old">
            <?php echo Text::formatPrice((float)$item->price_old)?>
          </span>
      <?php } ?>
      <span class="catalog-item__price <?php echo ($item->price_old && ($item->price_old != $item->price)) ? ' catalog-item__price_new' : ''?>">
        <span data-role="price-container">
          <?php echo Text::formatPrice((float)$item->price)?>
        </span>
        ₽
      </span>
    </div>
    <div class="catalog-item__available catalog-item__available_<?php echo $item->available ? '' : 'not-'?>available">
      <?php echo $item->available ? 'В наличии' : 'Под заказ'?>
    </div>
    <form action="/cart/" class="catalog-item__controls" data-role="add-to-cart-form" data-id="<?php echo (int)$item->id?>" data-price="<?php echo (int)$item->price?>">
      <?php if ($item->available) { ?>
          <input type="hidden" name="action" value="add" />
          <input type="hidden" name="id" value="<?php echo (int)$item->id?>" />
          <input type="hidden" name="back" value="1" />
          <?php /* <input type="hidden" name="amount" value="1" /> */?>
      <?php } ?>
      <!--noindex-->
      <?php if ($item->available) { ?>
          <input type="number" class="catalog-item__amount" autocomplete="off" name="amount" min="<?php echo (int)$item->min ?: 1?>" step="<?php echo (int)$item->step ?: 1?>" value="<?php echo (int)$item->min ?: 1?>" />
          <button type="submit" class="catalog-item__add-to-cart" title="<?php echo TO_CART?>"></button>
          <?php /* <a href="/cart/?action=add&id=<?php echo (int)$item->id?>" class="catalog-item__add-to-cart" data-role="add-to-cart-trigger" data-id="<?php echo (int)$item->id?>" data-price="<?php echo (int)$item->price?>" title="<?php echo TO_CART?>" data-active-title="<?php echo DELETE_FROM_CART?>"></a> */ ?>
      <?php } ?>
      <a href="/favorites/?action=add&id=<?php echo (int)$item->id?>" class="catalog-item__add-to-favorites" data-role="add-to-favorites-trigger" data-id="<?php echo (int)$item->id?>" title="<?php echo TO_FAVORITES?>" data-active-title="<?php echo DELETE_FROM_FAVORITES?>" rel="nofollow"></a>
      <!--/noindex-->
    </form>
  </div>
</div>
