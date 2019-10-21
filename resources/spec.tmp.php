<?php
/**
 * Виджет блока "Спецпредложение"
 * @param Page $Page Текущая страница
 * @param Block_PHP $Block Текущий блок
 */
namespace RAAS\CMS;

$mtype = Material_Type::importByURN('catalog');
$field = $mtype->fields['spec'];
$sqlQuery = "SELECT tM.*
               FROM " . Material::_tablename() . " AS tM
               JOIN cms_data AS tD ON tD.pid = tM.id
              WHERE tD.fid = " . (int)$field->id . "
                AND tM.pid IN (" . implode(", ", $mtype->selfAndChildrenIds) . ")
           GROUP BY tM.id
           ORDER BY RAND()
              LIMIT 20";
$set = Material::getSQLSet($sqlQuery);

?>
<?php if ($set) { ?>
    <div class="spec">
      <div class="spec__title">
        <?php echo htmlspecialchars($Block->name)?>
      </div>
      <div class="spec__inner">
        <div class="spec__list" data-role="slider" data-slider-carousel="jcarousel" data-slider-wrap="circular" data-slider-duration="800" data-slider-interval="3000" data-slider-autoscroll="true">
          <div class="spec-list">
            <?php foreach ((array)$set as $row) { ?>
                <div class="spec-list__item">
                  <?php Snippet::importByURN('catalog_item')->process(['item' => $row]); ?>
                </div>
            <?php } ?>
          </div>
        </div>
        <a href="#" class="spec__arrow spec__arrow_left" data-role="slider-prev"></a>
        <a href="#" class="spec__arrow spec__arrow_right" data-role="slider-next"></a>
      </div>
    </div>
<?php } ?>
