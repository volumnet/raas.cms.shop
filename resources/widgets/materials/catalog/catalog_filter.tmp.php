<?php
namespace RAAS\CMS;

$mt = microtime(1);
$DATA = $_GET;
$MType = Material_Type::importByURN('catalog');
$cc = new Catalog_Cache($MType);
if (!$cc->load()) {
    $cc->getCache();
    $cc->save();
}
$cc = $cc->data;
$cc = array_filter($cc, function($x) use ($Page) { return array_intersect(array_merge(array($Page->id), (array)$Page->all_children_ids), (array)$x['pages_ids']); });

$filterProps = array(/*'example' => 'Пример', */);
$filterRaw = array();
$filter = array();

// $dictionaryExample = new Dictionary(1);
// $dictionaryExample = $dictionaryExample->children;
// foreach ($dictionaryExample as $row) {
//     $filterRaw['example'][] = array('value' => $row->urn, 'name' => $row->name);
// }


foreach ($filterRaw as $key => $arr) {
    $filterSet = $cc;
    foreach ($filterProps as $key2 => $name) {
        if (($key2 != $key) && $DATA[$key2]) {
            $filterSet = array_filter(
                $filterSet,
                function ($x) use ($key2, $DATA) {
                    return $x[$key2] == $DATA[$key2];
                }
            );
        }
    }
    $filterSet = array_map(
        function ($x) use ($key) {
            return $x[$key];
        },
        $filterSet
    );
    $filter[$key] = array_filter(
        $arr,
        function ($x) use ($filterSet) {
            return in_array($x['value'], $filterSet);
        }
    );
}

$maxPrice = array_map(
    function ($x) {
        return (int)$x['price'];
    },
    $cc
);
$maxPrice = max($maxPrice);
$priceStep = 1000;
$maxPrice = ceil($maxPrice / (int)$priceStep) * (int)$priceStep;
if ($DATA['price_to'] && $DATA['price_to'] > $maxPrice) {
    $DATA['price_to'] = $maxPrice;
}
$price1 = floor($DATA['price_from'] / (int)$priceStep) * (int)$priceStep;
$price2 = ceil(($DATA['price_to'] ?: $maxPrice) / (int)$priceStep) * (int)$priceStep;

// echo microtime(1) - $mt;
?>
<!--noindex-->
<div class="catalog-filter">
  <form action="" method="get" data-page-id="<?php echo (int)$Page->id?>">
    <div class="row">
      <div class="col-sm-3">
        <div><label><?php echo ARTICLE?></label></div>
        <div class="catalog-filter__property">
          <input type="text" class="form-control" name="article" placeholder="Артикул" value="<?php echo htmlspecialchars($_GET['article'])?>">
        </div>
      </div>
      <div class="col-sm-4">
        <div><label><?php echo PRICE?></label></div>
        <div class="catalog-filter__property catalog-filter__price">
          <div class="row">
            <div class="col-xs-1">от</div>
            <div class="col-xs-5"><input type="number" class="form-control" min="0" max="<?php echo (int)$maxPrice?>" step="<?php echo (int)$priceStep?>" name="price_from" placeholder="От" value="<?php echo htmlspecialchars($price1)?>"></div>
            <div class="col-xs-1">до</div>
            <div class="col-xs-5"><input type="number" class="form-control" min="0" max="<?php echo (int)$maxPrice?>" step="<?php echo (int)$priceStep?>" name="price_to" placeholder="До" value="<?php echo htmlspecialchars($price2)?>"></div>
          </div>
          <div class="catalog-filter__slider" id="catalog-filter__slider"></div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="hidden-xs"><label>&nbsp;</label></div>
        <div class="catalog-filter__property catalog-filter__property_search">
          <button type="submit" class="btn btn-primary"><?php echo DO_SEARCH?></button>
          <a href="<?php echo htmlspecialchars($Page->url)?>" class="btn btn-default"><?php echo RESET?></a>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-sm-3"></div>
      <div class="col-sm-4"></div>
      <div class="col-sm-1"></div>
    </div>
  </form>
  <script src="/js/catalog_filter.js"></script>
</div>
<!--/noindex-->
