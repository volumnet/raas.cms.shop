<?php
namespace RAAS\CMS\Shop;

$showCategory = function($row)
{
    $queryString = http_build_query(array_intersect_key($_GET, array_flip(array('brand', 'model', 'engine'))));
    $queryString = $queryString ? '?' . $queryString : '';
    ?>
    <a class="catalog-category" href="<?php echo $row->url . $queryString ?>">
      <div class="catalog-category__image<?php echo !$row->image->id ? ' catalog-category__image_nophoto' : ''?>">
        <?php if ($row->image->id) { ?>
            <img src="/<?php echo htmlspecialchars(addslashes($row->image->smallURL))?>" alt="<?php echo htmlspecialchars($row->image->name ?: $row->name)?>" />
        <?php } ?>
      </div>
      <div class="catalog-category__text">
        <div class="catalog-category__title">
          <?php echo htmlspecialchars($row->name . ((int)$row->counter ? ' (' . (int)$row->counter . ')' : ''))?>
        </div>
      </div>
    </a>
    <?php
};
