<?php
/**
 * Виджет блока "Спецпредложение"
 * @param Page $Page Текущая страница
 * @param Block_Material $Block Текущий блок
 * @param Material[] $Set Набор товаров для отображения
 */
namespace RAAS\CMS;

use RAAS\AssetManager;

if ($Set) { ?>
    <div class="spec">
      <?php if ($Block->name && $Block->name[0] != '.') { ?>
          <div class="spec__title h2">
            <?php echo htmlspecialchars($Block->name)?>
          </div>
      <?php } ?>
      <div class="spec__inner slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="true" data-v-bind_autoscroll="true" data-v-slot="vm">
        <a data-v-on_click="vm.prev()" class="spec__arrow spec__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'spec__arrow_active': vm.prevAvailable, 'slider__arrow_active': vm.prevAvailable }"></a>
        <div class="spec__list slider__list" data-role="slider-list">
          <div class="spec-list slider-list slider-list_horizontal">
            <?php foreach ((array)$Set as $i => $item) { ?>
                <div class="spec-list__item slider-list__item" data-role="slider-item" data-slider-index="<?php echo (int)$i?>" data-v-bind_class="{ 'spec-list__item_active': (vm.activeFrame == <?php echo $i?>), 'slider-list__item_active': (vm.activeFrame == <?php echo $i?>) }">
                  <?php Snippet::importByURN('catalog_item')->process([
                      'item' => $item,
                      'page' => $Page,
                      'position' => $i,
                  ]); ?>
                </div>
            <?php } ?>
          </div>
        </div>
        <a data-v-on_click="vm.next()" class="spec__arrow spec__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'spec__arrow_active': vm.nextAvailable, 'slider__arrow_active': vm.nextAvailable }"></a>
      </div>
    </div>
    <?php
    AssetManager::requestCSS('/css/spec.css');
}
