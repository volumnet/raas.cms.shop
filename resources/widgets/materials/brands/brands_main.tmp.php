<?php
/**
 * Виджет брендов на главной
 * @param Block_Material $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param Material[]|null $Set Набор материалов для отображения
 * @param Material $Item Активный материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Pages;
use RAAS\AssetManager;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\PageRecursiveCache;
use RAAS\CMS\Snippet;

if ($Set) { ?>
    <div class="brands-main">
      <div class="brands-main__title h2">
        <a href="/brands/">
          <?php echo htmlspecialchars($Block->name)?>
        </a>
      </div>
      <div class="brands-main__inner slider slider_horizontal" data-vue-role="raas-slider" data-vue-type="horizontal" data-v-bind_wrap="true" data-v-bind_autoscroll="true" data-v-slot="vm">
        <a data-v-on_click="vm.prev()" class="brands-main__arrow brands-main__arrow_prev slider__arrow slider__arrow_prev" data-v-bind_class="{ 'brands-main__arrow_active': vm.prevAvailable, 'slider__arrow_active': vm.prevAvailable }"></a>
        <div class="brands-main__list slider__list" data-role="slider-list">
          <div class="brands-main-list slider-list slider-list_horizontal">
            <?php foreach ($Set as $item) { ?>
                <div class="brands-main-list__item">
                  <div class="brands-main-item">
                    <a class="brands-main-item__image" href="<?php echo htmlspecialchars($item->url)?>">
                      <?php if ($item->image->id) { ?>
                          <img loading="lazy" src="/<?php echo htmlspecialchars($item->image->fileURL)?>" alt="<?php echo htmlspecialchars($item->image->name ?: $item->name)?>" title="<?php echo htmlspecialchars($item->image->name ?: $item->name)?>" />
                      <?php } else {
                          echo htmlspecialchars($item->name);
                      } ?>
                    </a>
                  </div>
                </div>
            <?php } ?>
          </div>
        </div>
        <a data-v-on_click="vm.next()" class="brands-main__arrow brands-main__arrow_next slider__arrow slider__arrow_next" data-v-bind_class="{ 'brands-main__arrow_active': vm.nextAvailable, 'slider__arrow_active': vm.nextAvailable }"></a>
      </div>
    </div>
    <?php
    AssetManager::requestCSS('/css/brands-main.css');
}
