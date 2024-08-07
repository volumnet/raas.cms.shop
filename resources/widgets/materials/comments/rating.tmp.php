<?php
/**
 * Рейтинг (звезды)
 * @param float $rating Рейтинг (1-5)
 */
namespace RAAS\CMS\Shop;

use RAAS\AssetManager;
?>
<ol class="rating rating__list">
  <?php for ($i = 1; $i <= 5; $i++) {
      $halfStar = min(2, max(0, (int)(($rating - $i + 1) * 2)));
      $starClass = ['empty', 'half', 'full'];
      ?>
      <li class="rating__item rating__item_<?php echo $starClass[$halfStar]?>"></li>
  <?php } ?>
</ol>
