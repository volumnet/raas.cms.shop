<?php
/**
 * Виджет товара в списке
 * @param Material $item Товар для отображения
 * @param Page|null $page Страница, на которой отображается товар
 * @param int|null $position Позиция товара в списке
 * @param Material_Field[] $mainProps Характеристики для карточки
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\AssetManager;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;

$propsCache = (array)json_decode($item->cache_shop_props, true);

$formatter = new ItemArrayFormatter($item, (bool)$propsCache);
$itemData = $formatter->format([
    'visImages' => function ($item, $propsCache) {
        return $propsCache['images']['values'] ?: array_map(function ($x) {
            return [
                'id' => $x->id,
                'name' => $x->name,
                'smallURL' => $x->smallURL,
            ];
        }, $item->visImages);
    },
    'eCommerce' => function ($item, $propsCache) use ($page, $position) {
        return ECommerce::getProduct($item, $position, $page, $propsCache);
    },
    'available' => function ($item, $propsCache) {
        return (bool)(int)(
            $propsCache['available']['values'] ?
            $propsCache['available']['values'][0] :
            $item->available
        );
    },
    'unit',
]);

if ($propsCache) {
    $propsTable = [];
    foreach ((array)$propsCache['main_props'][$item->cache_url_parent_id] as $propData) {
        if ($propData['values']) {
            $propsTable[] = [
                'name' => $propData['name'],
                'value' => implode(', ', array_map(function ($x) {
                    return is_array($x) ? $x['name'] : $x;
                }, $propData['values'])),
            ];
        }
    }
} else {
    if (!$mainProps) {
        $catalogInterface = new CatalogInterface();
        $mainPropsIds = (array)$catalogInterface->getMetaTemplate($item->urlParent, 'main_props');
        $mainProps = [];
        foreach ($mainPropsIds as $mainPropId) {
            $field = new Material_Field($mainPropId);
            if ($field->id) {
                $mainProps[] = new Material_Field($mainPropId);
            }
        }
    }
    $propsTable = [];
    foreach ($mainProps as $prop) {
        $field = $prop->deepClone();
        $field->Owner = $item;
        if ($val = $field->doRich()) {
            $propsTable[] = ['name' => $field->name, 'value' => $val];
        }
    }
}
?>
<div class="catalog-item" data-vue-role="catalog-item" data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>" data-v-bind_bind-amount-to-cart="true" data-v-slot="vm" data-id="<?php echo (int)$itemData['id']?>">
  <div class="catalog-item__image<?php echo (count($itemData['visImages']) > 1) ? ' catalog-item__image_swap' : ''?>">
    <a href="<?php echo $itemData['url']?>">
      <img loading="lazy" src="<?php echo htmlspecialchars($itemData['image'] ?: '/files/cms/common/image/design/nophoto.jpg')?>" alt="<?php echo htmlspecialchars($itemData['visImages'][0]['name'] ?: $itemData['name'])?>" />
      <?php if (count($itemData['visImages']) > 1) { ?>
          <img loading="lazy" src="/<?php echo htmlspecialchars($itemData['visImages'][1]['smallURL'])?>" alt="<?php echo htmlspecialchars($itemData['visImages'][1]['name'] ?: $itemData['name'])?>" />
      <?php } ?>
    </a>
    <div class="catalog-item__controls">
      <!--noindex-->
      <button type="button" data-v-on_click="vm.toggleCompare()" class="catalog-item__add-to-compare" data-v-bind_class="{ 'catalog-item__add-to-compare_active': vm.inCompare }" data-v-bind_title="vm.inCompare ? '<?php echo IN_COMPARISON?>' : '<?php echo TO_COMPARISON?>'"></button>
      <button type="button" data-v-on_click="vm.toggleFavorites()" class="catalog-item__add-to-favorites" data-v-bind_class="{ 'catalog-item__add-to-favorites_active': vm.inFavorites }" data-v-bind_title="vm.inFavorites ? '<?php echo IN_FAVORITES?>' : '<?php echo TO_FAVORITES?>'"></button>
      <!--/noindex-->
    </div>
  </div>
  <div class="catalog-item__text">
    <div class="catalog-item__text-inner">
      <a class="catalog-item__title" href="<?php echo $itemData['url']?>">
        <?php echo htmlspecialchars($itemData['name'])?>
      </a>
      <?php if ($propsTable) { ?>
          <div class="catalog-item__props catalog-item-props-list">
            <?php foreach ($propsTable as $propRow) { ?>
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
        <span class="catalog-item__price <?php echo ($itemData['price_old'] && ($itemData['price_old'] > $itemData['price'])) ? ' catalog-item__price_new' : ''?>"  data-v-html="vm.formatPrice(vm.item.price * Math.max(vm.item.min || 1, vm.amount)) + ' ₽'">
          <?php echo Text::formatPrice((float)$itemData['price'])?> ₽
        </span>
        <?php if ($itemData['price_old'] && ($itemData['price_old'] > $itemData['price'])) { ?>
            <span class="catalog-item__price catalog-item__price_old" data-v-if="vm.item.price_old && (vm.item.price_old > vm.item.price)" data-v-html="vm.formatPrice(vm.item.price_old * Math.max(vm.item.min || 1, vm.amount)) + ' ₽'">
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
            <div class="catalog-item__amount-block" title="<?php echo IN_CART?>" data-v-if="vm.inCart">
              <button type="button" class="catalog-item__decrement" data-v-on_click="vm.setAmount(parseInt(vm.amount) - parseInt(vm.item.step || 1)); vm.setCart();">–</button>
              <input type="number" class="catalog-item__amount" autocomplete="off" min="0" step="<?php echo (int)$itemData['step'] ?: 1?>" data-v-bind_value="vm.amount" data-v-on_change="vm.setAmount($event.target.value); vm.setCart();" />
              <button type="button" class="catalog-item__increment" data-v-on_click="vm.setAmount(parseInt(vm.amount) + parseInt(vm.item.step || 1)); vm.setCart();">+</button>
            </div>
            <button type="button" data-v-else data-v-on_click="vm.setAmount(Math.max(vm.item.min, 1)); vm.setCart()" class="btn btn-primary catalog-item__add-to-cart">
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
