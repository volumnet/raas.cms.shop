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
foreach ($Cart->items as $cartItem) {
    $item = new Material($cartItem->id);
    $cartData['items'][] = [
        'id' => $cartItem->id,
        'meta' => $cartItem->meta,
        'price' => $cartItem->realprice,
        'name' => $cartItem->name,
        'url' => $item->url,
        'image' => $item->visImages ? '/' . $item->visImages[0]->smallURL : null,
    ];
}

if ($_GET['AJAX']) {
    echo json_encode($cartData);
    exit;
} else { ?>
    <template id="raas-shop-favorites-item-template">
      <div class="favorites-list__item">
        <div class="favorites-item">
          <div class="favorites-item__image">
            <a v-bind:href="item.url" v-if="item.image">
              <img v-bind:src="item.image" alt="">
            </a>
          </div>
          <div class="favorites-item__title">
            <a v-bind:href="item.url">
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
    </template>
    <template id="raas-shop-favorites-list-template">
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
          <raas-shop-favorites-item v-for="item in items" v-bind:item="item" v-on:delete="$emit('delete', item)"></raas-shop-favorites-item>
        </div>
        <div class="favorites-list__actions">
          <a class="favorites-list__clear" v-on:click="$emit('clear')">
            <?php echo CLEAR_FAVORITES?>
          </a>
        </div>
      </div>
    </template>

    <div class="favorites">
      <div class="favorites__list" v-if="items.length">
        <raas-shop-favorites-list v-bind:items="items" v-bind:cart="cart" v-on:clear="requestClear()" v-on:delete="requestItemDelete($event)"></raas-shop-favorites-list>
      </div>
      <div class="favorites__empty" v-if="!items.length">
        <?php echo htmlspecialchars(YOUR_FAVORITES_IS_EMPTY)?>
      </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        RAASShopFavoritesItemComponent = Vue.component('raas-shop-favorites-item', {
            props: ['item'],
            template: '#raas-shop-favorites-item-template',
            methods: {
                formatPrice: window.formatPrice,
            }
        });
        RAASShopFavoritesListComponent = Vue.component('raas-shop-favorites-list', {
            props: ['items', 'cart'],
            template: '#raas-shop-favorites-list-template',
        });
        raasShopFavorites = new Vue({
            el: '.favorites',
            data: function () {
                return {
                    items: <?php echo json_encode($cartData['items'])?>,
                    cart: $.RAAS.Shop.ajax<?php echo $Cart->cartType->no_amount ? 'Favorites' : 'Cart'?>,
                }
            },
            methods: {
                requestItemDelete: function (item) {
                    var self = this;
                    $.RAASConfirm('Вы действительно хотите удалить этот товар?')
                        .then(function () {
                            self.items = self.items.filter(function (x) {
                                return (x.id != item.id) ||
                                       (x.meta != item.meta);
                            });
                            self.cart.set(item.id, 0, item.meta, item.price);
                        });
                },
                requestClear: function () {
                    var self = this;
                    $.RAASConfirm('Вы действительно хотите очистить избранное?')
                        .then(function () {
                            self.items = [];
                            self.cart.clear();
                        });
                },
            },
        });
    });
    </script>
<?php }
