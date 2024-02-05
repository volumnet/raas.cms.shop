<?php
/**
 * Категория для отображения в списке
 * @param Page $page Категория для отоображения
 * @param int $brandId ID# бренда для отображения
 */
namespace RAAS\CMS\Shop;

use RAAS\AssetManager;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

if ($brandId) {
    $get = ['brand' => (int)$brandId];
} else {
    $get = array_intersect_key($_GET, array_flip(['brand']));
}
$queryString = http_build_query($get);
$queryString = $queryString ? '?' . $queryString : '';
?>
<a class="catalog-category" href="<?php echo $page->url . $queryString ?>">
  <img class="catalog-category__image" loading="lazy" src="/<?php echo htmlspecialchars($page->image->smallURL ?: 'files/cms/common/image/design/nophoto.jpg')?>" alt="<?php echo htmlspecialchars($page->image->name ?: $page->name)?>" />
  <div class="catalog-category__title">
    <?php echo htmlspecialchars($page->name)?>
    <?php if ($counter = (int)$page->counter) { ?>
        <span class="catalog-category__counter">
          <?php echo (int)$counter?>
        </span>
    <?php } ?>
  </div>
</a>
<?php
AssetManager::requestCSS('/css/catalog-category.css');
