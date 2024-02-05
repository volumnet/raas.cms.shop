<?php
/**
 * Популярные категории
 * @param Block_Menu $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param array|null $menuArr Данные кэша меню
 * @param Menu|null $Item Меню для отображения
 */
namespace RAAS\CMS\Shop;

use RAAS\AssetManager;
use RAAS\CMS\Menu;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

$children = [];
if ($menuArr && (isset($menuArr['children']) && is_array($menuArr['children']))) {
    $children = $menuArr['children'];
} elseif ($Item instanceof Menu) {
    $children = $Item->visSubMenu;
}
if ($children) { ?>
    <div class="popular-cats">
      <div class="popular-cats__title h2">
        <?php echo htmlspecialchars($Block->name)?>
      </div>
      <div class="popular-cats__inner slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="true" data-v-bind_autoscroll="true" data-v-slot="vm">
        <a data-v-on_click="vm.prev()" class="popular-cats__arrow popular-cats__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'popular-cats__arrow_active': vm.prevAvailable, 'slider__arrow_active': vm.prevAvailable }"></a>
        <div class="popular-cats__list slider__list" data-role="slider-list">
          <div class="popular-cats-list slider-list slider-list_horizontal">
            <?php
            foreach ($children as $item) {
                if ($item instanceof Menu) {
                    $page = $item->page;
                } else {
                    $page = new Page($item['page_id']);
                }
                ?>
                <div class="popular-cats-list__item slider-list__item">
                  <?php Snippet::importByURN('catalog_category')->process([
                      'page' => $page,
                  ]); ?>
                </div>
            <?php } ?>
          </div>
        </div>
        <a data-v-on_click="vm.next()" class="popular-cats__arrow popular-cats__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'popular-cats__arrow_active': vm.nextAvailable, 'slider__arrow_active': vm.nextAvailable }"></a>
      </div>
    </div>
    <?php
    AssetManager::requestCSS('/css/popular-cats.css');
}
