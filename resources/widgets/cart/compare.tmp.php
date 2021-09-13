<?php
/**
 * Виджет блока избранного
 * @param Page $Page Текущая страница
 * @param Block_Cart $Block Текущий блок
 * @param Cart $Cart Текущая корзина
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\CMS\Material;
use RAAS\CMS\Package;

$cartData = [];
$cartData['count'] = (int)$Cart->count;
$cartData['items'] = [];
foreach ($Cart->items as $i => $cartItem) {
    $cartItemFormatter = new CartItemArrayFormatter($cartItem);
    $cartItemData = $cartItemFormatter->format([
        'article',
        'url',
    ]);
    $cartData['items'][] = $cartItemData;
}

if ($_GET['AJAX']) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($cartData);
    exit;
} else { ?>
    <script type="text/html" v-pre id="favorites-item-template">
      <div class="favorites-list__item">
        <div class="favorites-item">
          <div class="favorites-item__image">
            <a :href="item.url" v-if="item.image">
              <img loading="lazy" :src="item.image" alt="">
            </a>
          </div>
          <div class="favorites-item__title">
            <a :href="item.url">
              {{ item.name }}
            </a>
          </div>
          <div class="favorites-item__price">
            {{ formatPrice(item.price) }} ₽
          </div>
          <div class="favorites-item__actions">
            <a class="favorites-item__delete" v-on:click="$emit('delete', item)" title="<?php echo DELETE?>"></a>
          </div>
        </div>
      </div>
    </script>
    <script type="text/html" v-pre id="favorites-list-template">
      <div class="favorites-list">
        <div class="favorites-list__header">
          <div class="favorites-list__item favorites-list__item_header">
            <div class="favorites-item favorites-item_header">
              <div class="favorites-item__image">
                <?php echo IMAGE?>
              </div>
              <div class="favorites-item__title">
                <?php echo NAME?>
              </div>
              <div class="favorites-item__price">
                <?php echo PRICE?>
              </div>
              <div class="favorites-item__actions"></div>
            </div>
          </div>
        </div>
        <div class="favorites-list__list">
          <favorites-item v-for="item in items" :item="item" v-on:delete="$emit('delete', item)"></favorites-item>
        </div>
        <div class="favorites-list__actions">
          <a class="favorites-list__clear" v-on:click="$emit('clear')">
            <?php echo CLEAR_FAVORITES?>
          </a>
        </div>
      </div>
    </script>

    <favorites class="favorites" inline-template :cart="favorites">
      <div>
        <div class="favorites__list" v-if="items.length">
          <favorites-list :items="items" :cart="cart" @clear="requestClear()" @delete="requestItemDelete($event)"></favorites-list>
        </div>
        <div class="favorites__empty" v-if="!items.length">
          <?php echo htmlspecialchars(YOUR_FAVORITES_IS_EMPTY)?>
        </div>
      </div>
    </favorites>
    <script>
    window.raasShopFavoritesData = {
        items: <?php echo json_encode($cartData['items'])?>,
    };
    </script>
    <?php Package::i()->requestJS('/js/favorites.js')?>
<?php }
