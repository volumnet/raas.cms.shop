<?php
/**
 * Виджет товара в списке
 * @param Material $item Товар для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\Package;

$enablePropsCache = false;

$formatter = new ItemArrayFormatter($item, $enablePropsCache);
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
    'available',
]);
?>
<div class="catalog-item" data-vue-role="catalog-item" data-v-bind_item="<?php echo htmlspecialchars(json_encode($itemData))?>" data-v-slot="vm">
  <div class="catalog-item__image<?php echo (count($itemData['visImages']) > 1) ? ' catalog-item__image_swap' : ''?>">
    <a href="<?php echo $itemData['url']?>">
      <img loading="lazy" src="<?php echo htmlspecialchars($itemData['image'] ?: '/files/cms/common/image/design/nophoto.jpg')?>" alt="<?php echo htmlspecialchars($itemData['visImages'][0]['name'] ?: $itemData['name'])?>" />
      <?php if (count($itemData['visImages']) > 1) { ?>
          <img loading="lazy" src="<?php echo htmlspecialchars($itemData['visImages'][1]['smallURL'])?>" alt="<?php echo htmlspecialchars($itemData['visImages'][1]['name'] ?: $itemData['name'])?>" />
      <?php } ?>
    </a>
    <div class="catalog-item__controls">
      <!--noindex-->
      <button type="button" data-v-on_click="vm.toggleCompare()" class="catalog-item__add-to-compare" data-v-bind_class="{ 'catalog-item__add-to-compare_active': vm.inCompare}" data-v-bind_title="vm.inCompare ? '<?php echo DELETE_FROM_COMPARISON?>' : '<?php echo TO_COMPARISON?>'"></button>
      <button type="button" data-v-on_click="vm.toggleFavorites()" class="catalog-item__add-to-favorites" data-v-bind_class="{ 'catalog-item__add-to-favorites_active': vm.inFavorites}" data-v-bind_title="vm.inFavorites ? '<?php echo DELETE_FROM_FAVORITES?>' : '<?php echo TO_FAVORITES?>'"></button>
      <!--/noindex-->
    </div>
  </div>
  <div class="catalog-item__text">
    <div class="catalog-item__title">
      <a href="<?php echo $itemData['url']?>">
        <?php echo htmlspecialchars($itemData['name'])?>
      </a>
    </div>
    <div class="catalog-item__price-container">
      <span class="catalog-item__price <?php echo ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) ? ' catalog-item__price_new' : ''?>">
        <span data-v-html="vm.formatPrice(vm.item.price * vm.amount)">
          <?php echo Text::formatPrice((float)$itemData['price'])?>
        </span> ₽
      </span>
      <?php if ($itemData['price_old'] && ($itemData['price_old'] != $itemData['price'])) { ?>
          <span class="catalog-item__price catalog-item__price_old" data-v-if="vm.item.price_old && (vm.item.price_old > vm.item.price)">
            <span data-v-html="vm.formatPrice(vm.item.price_old * vm.amount)">
              <?php echo Text::formatPrice((float)$itemData['price_old'])?>
            </span> ₽
          </span>
      <?php } ?>
    </div>
    <div class="catalog-item__available catalog-item__available_<?php echo $itemData['available'] ? '' : 'not-'?>available">
      <?php echo $itemData['available'] ? 'В наличии' : 'Под заказ'?>
    </div>
    <?php if ($itemData['available']) { ?>
        <div class="catalog-item__add-to-cart-outer">
          <!--noindex-->
          <div class="catalog-item__amount-block">
            <a class="catalog-item__decrement" data-v-on_click="vm.setAmount(parseInt(vm.amount) - parseInt(vm.item.step || 1));">–</a>
            <input type="number" class="form-control catalog-item__amount" autocomplete="off" min="<?php echo (int)$itemData['min'] ?: 1?>" step="<?php echo (int)$itemData['step'] ?: 1?>" data-v-bind_value="vm.amount" data-v-on_input="vm.setAmount($event.target.value)" />
            <a class="catalog-item__increment" data-v-on_click="vm.setAmount(parseInt(vm.amount) + parseInt(vm.item.step || 1))">+</a>
          </div>
          <button type="button" data-v-on_click="vm.addToCart()" class="btn btn-primary catalog-item__add-to-cart" title="<?php echo DO_BUY?>"><?php echo DO_BUY?></button>
          <?php /*
          <button type="button" data-v-on_click="vm.toggleCart()" class="btn btn-primary catalog-item__add-to-cart" data-v-bind_class="{ 'catalog-item__add-to-cart_active': vm.inCart}" data-v-bind_title="vm.inCart ? '<?php echo DELETE_FROM_CART?>' : '<?php echo DO_BUY?>'" data-v-html="vm.inCart ? '<?php echo DELETE?>' : '<?php echo DO_BUY?>'"><?php echo DO_BUY?></button>
          */ ?>
          <!--/noindex-->
        </div>
    <?php } ?>
  </div>
</div>
<?php
Package::i()->requestCSS('/css/catalog-item.css');
Package::i()->requestJS('/js/catalog-item.js');
