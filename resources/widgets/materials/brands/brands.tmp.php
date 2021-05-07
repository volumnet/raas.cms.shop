<?php
/**
 * Виджет брендов
 * @param Block_Material $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param Material[]|null $Set Набор материалов для отображения
 * @param Material $Item Активный материал для отображения
 */
namespace RAAS\CMS\Shop;

use SOME\Pages;
use RAAS\CMS\Material_Type;
use RAAS\CMS\PageRecursiveCache;
use RAAS\CMS\Snippet;

if ($Item) {
    $catalogMaterialType = Material_Type::importByURN('catalog')
    $pageLevel = 2;
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
        if (isset($selfAndParentsIds[$pageLevel])) {
            $rootId = $selfAndParentsIds[$pageLevel];
            if (!isset($rootPagesIds[$rootId])) {
                $rootPages[$rootId] = new Page(PageRecursiveCache::i()->cache[$rootId]);
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
      <div class="brands__article">
        <div class="brands-article">
          <?php if ($Item->image->id) { ?>
              <div class="brands-article__image">
                <img loading="lazy" src="/<?php echo htmlspecialchars($Item->image->fileURL)?>" alt="<?php echo htmlspecialchars($Item->image->name ?: $row->name)?>" />
              </div>
          <?php } ?>
            <div class="brands-article__description">
              <?php echo $Item->description; ?>
            </div>
          </div>
          <?php if ($rootPages) { ?>
              <div class="brands-article__pages">
                <div class="h2 brands-article__pages-title">
                  <?php echo sprintf(GOODS_OF_BRAND_BY_CATS, $Item->name)?>
                </div>
                <div class="brands-article__pages-inner">
                  <div class="catalog-categories-list">
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
              </div>
          <?php } ?>
        </div>
      </div>
    </div>
<?php } elseif ($Set) { ?>
    <div class="brands">
      <div class="brands__list">
        <div class="brands-list">
          <?php foreach ($Set as $item) { ?>
              <div class="brands-list__item">
                <div class="brands-item">
                  <a class="brands-item__image" href="<?php echo htmlspecialchars($item->url)?>">
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
      <?php if ($Pages->pages > 1) { ?>
          <div class="brands__pagination">
            <?php Snippet::importByURN('pagination')->process(['pages' => $Pages]); ?>
          </div>
      <?php } ?>
    </div>
    <?php
    Package::i()->requestCSS('/css/brands.css');
}
