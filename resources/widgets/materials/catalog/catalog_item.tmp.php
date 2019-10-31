<?php
/**
 * Виджет товара в списке
 * @param Material $item Товар для отображения
 */
namespace RAAS\CMS\Shop;

$formatPrice = function($price) {
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
};

?>
<div class="catalog-item">
  <a href="<?php echo $item->url?>" class="catalog-item__image<?php echo !$item->visImages ? ' catalog-item__image_nophoto' : ''?>">
    <?php if ($item->visImages) { ?>
        <img src="/<?php echo htmlspecialchars(addslashes($item->visImages[0]->smallURL))?>" alt="<?php echo htmlspecialchars($item->visImages[0]->name ?: $item->name)?>" />
    <?php } ?>
  </a>
  <div class="catalog-item__title">
    <a href="<?php echo $item->url?>"><?php echo htmlspecialchars($item->name)?></a>
  </div>
  <div class="catalog-item__price-container" data-price="<?php echo (float)$item->price?>">
    <span class="catalog-item__price <?php echo ($item->price_old && ($item->price_old != $item->price)) ? ' catalog-item__price_new' : ''?>">
      <span data-role="price-container">
        <?php echo $formatPrice((float)$item->price)?>
      </span> ₽
    </span>
    <?php if ($item->price_old && ($item->price_old != $item->price)) { ?>
        <span class="catalog-item__price catalog-item__price_old"><?php echo $formatPrice((float)$item->price_old)?></span>
    <?php } ?>
  </div>
  <div class="catalog-item__controls-outer">
    <div class="catalog-item__available catalog-item__available_<?php echo $item->available ? '' : 'not-'?>available">
      <?php echo $item->available ? 'В наличии' : 'Под заказ'?>
    </div>
    <form action="/cart/" class="catalog-item__controls" data-role="add-to-cart-form" data-id="<?php echo (int)$item->id?>" data-price="<?php echo (int)$item->price?>">
      <!--noindex-->
      <?php if ($item->available) { ?>
          <input type="hidden" name="action" value="add" />
          <input type="hidden" name="id" value="<?php echo (int)$item->id?>" />
          <input type="hidden" name="back" value="1" />
          <div class="catalog-item__amount-block">
            <a class="catalog-item__decrement" data-role="amount-decrement">–</a>
            <input type="number" class="catalog-item__amount" autocomplete="off" name="amount" min="<?php echo (int)$item->min ?: 1?>" step="<?php echo (int)$item->step ?: 1?>" value="<?php echo (int)$item->min ?: 1?>" />
            <a class="catalog-item__increment" data-role="amount-increment">+</a>
          </div>
          <button type="submit" class="catalog-item__add-to-cart" title="<?php echo TO_CART?>"></button>
      <?php } ?>
      <a href="/favorites/?action=add&id=<?php echo (int)$item->id?>" class="catalog-item__add-to-favorites" data-role="add-to-favorites-trigger" data-id="<?php echo (int)$item->id?>" title="<?php echo TO_FAVORITES?>" data-active-title="<?php echo DELETE_FROM_FAVORITES?>" rel="nofollow"></a>
      <!--/noindex-->
    </form>
  </div>
</div>
