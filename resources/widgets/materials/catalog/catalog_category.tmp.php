<?php
/**
 * Виджет категории для отображения в списке
 * @param Page $page Категория для отоображения
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Package;
use RAAS\CMS\Page;

$queryStringArr = array_intersect_key($_GET, array_flip(['brand']));
$queryString = http_build_query($queryStringArr);
$queryString = $queryString ? '?' . $queryString : '';
?>
<a class="catalog-category" href="<?php echo $page->url . $queryString ?>">
  <div class="catalog-category__image">
    <img loading="lazy" src="/<?php echo htmlspecialchars($page->image->smallURL ?: '/files/cms/common/image/design/nophoto.jpg')?>" alt="<?php echo htmlspecialchars($page->image->name ?: $page->name)?>" />
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
