<?php
/**
 * Виджет рейтинга (звезды)
 * @param float $rating Рейтинг (1-5)
 */
namespace RAAS\CMS;

?>
<div class="rating">
  <div class="rating__list">
    <div class="rating-list">
      <?php for ($i = 1; $i <= 5; $i++) {
          $halfStar = min(2, max(0, (int)(($rating - $i + 1) * 2)));
          $starClass = ['star-o', 'star-half-o', 'star'];
          ?>
          <span class="rating-list__item">
            <span class="rating-list-item fa fa-<?php echo $starClass[$halfStar]?>"></span>
          </span>
      <?php } ?>
    </div>
  </div>
</div>
