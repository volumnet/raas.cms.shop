<?php
/**
 * Бренды
 * @param Block_Material $Block Текущий блок <pre><code>Block([
 *     'additionalParams' => [
 *         'pageLevel' => int Категории какого уровня выводить (абсолютный уровень, 0-based)
 *     ],
 * ])</code></pre>
 * @param Page $Page Текущая страница
 * @param Material[]|null $Set Набор материалов для отображения
 * @param Material $Item Активный материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Pages;
use RAAS\AssetManager;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\PageRecursiveCache;
use RAAS\CMS\Snippet;

if ($Item) {
    $catalogMaterialType = Material_Type::importByURN('catalog');
    $pageLevel = (int)$Block->additionalParams['pageLevel'] ?: 2;
    $catalogFilter = CatalogFilter::loadOrBuild(
        $catalogMaterialType,
        true,
        []
    );
    $brandField = $catalogFilter->propertiesByURNs['brand'];
    $brandMapping = (array)$catalogFilter->propsMapping[$brandField->id][$Item->id];
    $pagesMapping = (array)$catalogFilter->propsMapping['pages_ids'];
    $pagesMapping = array_map(function ($pageMapping) use ($brandMapping) {
        return array_intersect($pageMapping, $brandMapping);
    }, $pagesMapping);
    $pagesMapping = array_filter($pagesMapping);
    $pagesIds = array_keys($pagesMapping);
    $rootPages = [];
    foreach ($pagesIds as $pageId) {
        $selfAndParentsIds = PageRecursiveCache::i()->getSelfAndParentsIds($pageId);
        $rootId = $selfAndParentsIds[min($pageLevel, count($selfAndParentsIds))];
        if ($rootId) {
            if (!isset($rootPagesIds[$rootId])) {
                $rootPage = new Page(PageRecursiveCache::i()->cache[$rootId]);
                $rootPage->counter = count($pagesMapping[$rootId]);
                $rootPages[$rootId] = $rootPage;
            }
        }
    }
    usort($rootPages, function ($a, $b) {
        $aPriority = PageRecursiveCache::i()->cache[$a->id]['priority'];
        $bPriority = PageRecursiveCache::i()->cache[$b->id]['priority'];
        return $aPriority - $bPriority;
    });
    ?>
    <div class="brands">
      <div class="brands__article brands-article">
        <?php if ($Item->image->id) { ?>
            <img
              class="brands-article__image"
              loading="lazy"
              src="/<?php echo htmlspecialchars($Item->image->fileURL)?>"
              alt="<?php echo htmlspecialchars($Item->image->name ?: $row->name)?>"
            />
        <?php } ?>
        <div class="brands-article__description">
          <?php echo $Item->description; ?>
        </div>
        <?php if ($rootPages) { ?>
            <div class="brands-article__pages">
              <div class="h2 brands-article__pages-title">
                <?php echo sprintf(GOODS_OF_BRAND_BY_CATS, $Item->name)?>
              </div>
              <div class="brands-article__pages-inner catalog-categories-list">
                <?php foreach ($rootPages as $row) { ?>
                    <div class="catalog-categories-list__item">
                      <?php Snippet::importByURN('catalog_category')->process([
                          'page' => $row,
                          'brandId' => $Item->id,
                      ])?>
                    </div>
                <?php } ?>
              </div>
            </div>
        <?php } ?>
      </div>
    </div>
    <?php
    AssetManager::requestCSS('/css/brands-article.css');
    AssetManager::requestJS('/js/brands-article.js');
} elseif ($Set) { ?>
    <div class="brands">
      <div class="brands__list brands-list">
        <?php foreach ($Set as $item) { ?>
            <a class="brands-list__item brands-item" href="<?php echo htmlspecialchars($item->url)?>">
              <?php if ($item->image->id) { ?>
                  <img
                    loading="lazy"
                    src="/<?php echo htmlspecialchars($item->image->fileURL)?>"
                    alt="<?php echo htmlspecialchars($item->image->name ?: $item->name)?>"
                    title="<?php echo htmlspecialchars($item->image->name ?: $item->name)?>"
                  />
              <?php } else {
                  echo htmlspecialchars($item->name);
              } ?>
            </a>
        <?php } ?>
      </div>
      <?php if ($Pages->pages > 1) { ?>
          <div class="brands__pagination">
            <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
          </div>
      <?php } ?>
    </div>
    <?php
    AssetManager::requestCSS('/css/brands-list.css');
    AssetManager::requestJS('/js/brands-list.js');
}
