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
    <div class="spec catalog">
      <div class="catalog-carousel-block">
        <div class="jcarousel-wrapper">
          <div class="jcarousel" data-role="jcarousel">
            <ul class="jcarousel-inner">
              <?php foreach ((array)$Set as $row) { ?>
                  <li>
                    <?php $showItem($row); ?>
                  </li>
              <?php } ?>
            </ul>
          </div>
          <a href="#" class="jcarousel-prev"></a>
          <a href="#" class="jcarousel-next"></a>
        </div>
      </div>
    </div>
    <script src="/js/spec.js"></script>
<?php } ?>
