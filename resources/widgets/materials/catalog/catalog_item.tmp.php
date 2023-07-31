<?php
/**
 * Виджет товара в списке
 * @param Material|array $item Товар для отображения или его данные
 * @param Page? $page Страница, на которой отображается товар
 * @param int? $position Позиция товара в списке
 * @param bool $noVue Не указывать директивы Vue (статический вариант)
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\AssetManager;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;

if ($item instanceof Material) {
    $formatter = new CatalogItemArrayFormatter($item, (bool)(array)json_decode($item->cache_shop_props, true));
    $formatter->page = $page ?? null;
    $formatter->position = $position ?? null;
    $itemData = $formatter->format();
} else {
    $itemData = $item;
}
?>
<div
  class="catalog-item"
  data-id="<?php echo (int)$itemData['id']?>"
  <?php if (!$noVue) { ?>
      data-vue-role="catalog-item"
      data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>"
      data-v-bind_bind-amount-to-cart="true"
  <?php } ?>
>
  <div class="catalog-item__image">
    <a href="<?php echo $itemData['url']?>">
      <img
        loading="lazy"
        src="<?php echo htmlspecialchars($itemData['image'] ?: '/files/cms/common/image/design/nophoto.jpg')?>"
        alt="<?php echo htmlspecialchars($itemData['visImages'][0]['name'] ?: $itemData['name'])?>"
      />
    </a>
  </div>
  <div class="catalog-item__text">
    <div class="catalog-item__text-inner">
      <a class="catalog-item__title" href="<?php echo $itemData['url']?>">
        <?php echo htmlspecialchars($itemData['name'])?>
      </a>
      <?php if ($itemData['props']) { ?>
          <div class="catalog-item__props catalog-item-props-list">
            <?php foreach ($itemData['props'] as $propRow) { ?>
                <div class="catalog-item-props-list__item catalog-item-props-item">
                  <span class="catalog-item-props-item__title">
                    <?php echo htmlspecialchars($propRow['name'])?>:
                  </span>
                  <span class="catalog-item-props-item__value">
                    <?php echo htmlspecialchars($propRow['value'])?>
                  </span>
                </div>
            <?php } ?>
          </div>
      <?php } ?>
    </div>
    <div class="catalog-item__offer">
      <div class="catalog-item__price-container">
        <span class="catalog-item__price <?php echo ($itemData['price_old'] && ($itemData['price_old'] > $itemData['price'])) ? ' catalog-item__price_new' : ''?>">
          <?php echo Text::formatPrice((float)$itemData['price'])?> ₽
        </span>
        <?php if ($itemData['price_old'] && ($itemData['price_old'] > $itemData['price'])) { ?>
            <span class="catalog-item__price catalog-item__price_old">
              <?php echo Text::formatPrice((float)$itemData['price_old'])?> ₽
            </span>
        <?php } ?>
        <?php if ($itemData['unit'] && !stristr($itemData['unit'], 'шт')) { ?>
            <span class="catalog-item__unit">
              / <?php echo htmlspecialchars($itemData['unit'])?>
            </span>
        <?php } ?>
      </div>
      <div class="catalog-item__available catalog-item__available_<?php echo $itemData['available'] ? '' : 'not-'?>available">
        <?php echo $itemData['available'] ? 'В наличии' : 'Под заказ'?>
      </div>
      <?php if ($itemData['available']) { ?>
          <!--noindex-->
          <div class="catalog-item__add-to-cart-outer">
            <button type="button" class="btn btn-primary catalog-item__add-to-cart">
              <?php echo DO_BUY?>
            </button>
          </div>
          <!--/noindex-->
      <?php } ?>
    </div>
  </div>
</div>
<?php
AssetManager::requestCSS('/css/catalog-item.css');
AssetManager::requestJS('/js/catalog-item.js');
