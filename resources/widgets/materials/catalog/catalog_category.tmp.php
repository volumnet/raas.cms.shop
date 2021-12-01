<?php
/**
 * Виджет категории для отображения в списке
 * @param Page $page Категория для отоображения
 * @param int $brandId ID# бренда для отображения
 */
namespace RAAS\CMS\Shop;

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
  <div class="catalog-category__image">
    <img loading="lazy" src="/<?php echo htmlspecialchars($page->image->smallURL ?: 'files/cms/common/image/design/nophoto.jpg')?>" alt="<?php echo htmlspecialchars($page->image->name ?: $page->name)?>" />
  </div>
  <div class="catalog-category__text">
    <div class="catalog-category__title">
      <?php echo htmlspecialchars($page->name)?>
      <?php if ($counter = (int)$page->counter) { ?>
          <span class="catalog-category__counter">
            <?php echo (int)$counter?>
          </span>
      <?php } ?>
    </div>
  </div>
</a>
<?php
Package::i()->requestCSS('/css/catalog-category.css');
