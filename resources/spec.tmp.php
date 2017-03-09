<?php
namespace RAAS\CMS;

$MType = Material_Type::importByURN('catalog');
$Field = $MType->fields['spec'];
$SQL_query = "SELECT tM.* FROM " . Material::_tablename() . " AS tM
                JOIN cms_data AS tD ON tD.pid = tM.id
               WHERE tD.fid = " . (int)$Field->id . " AND tM.pid IN (" . implode(", ", array_merge(array((int)$MType->id), (array)$MType->all_children_ids)) . ")
            GROUP BY tM.id
            ORDER BY RAND()
               LIMIT 20";
$Set = Material::getSQLSet($SQL_query);
eval('?' . '>' . Snippet::importByURN('item_inc')->description);
?>
<?php if ($Set) { ?>
    <div class="spec">
      <div class="spec__list" data-role="slider" data-slider-carousel="jcarousel" data-slider-duration="800" data-slider-interval="3000" data-slider-autoscroll="true">
        <div class="spec-list">
          <?php foreach ((array)$Set as $row) { ?>
              <div class="spec-list__item">
                <?php $showItem($row); ?>
              </div>
          <?php } ?>
        </div>
      </div>
      <a href="#" class="spec__arrow spec__arrow_left" data-role="slider-prev"></a>
      <a href="#" class="spec__arrow spec__arrow_right" data-role="slider-next"></a>
    </div>
<?php } ?>
