<?php
namespace RAAS\CMS;

$mt = microtime(1);
$MType = Material_Type::importByURN('catalog');
$cc = new Catalog_Cache($MType);
if (!$cc->load()) {
    $cc->getCache();
    $cc->save();
}
$cc = $cc->data;
$cc = array_filter($cc, function($x) use ($Page) { return array_intersect(array_merge(array($Page->id), (array)$Page->all_children_ids), (array)$x['pages_ids']); });

// echo microtime(1) - $mt;
?>
<div class="catalog_filter">
  <form action="" method="get" data-page-id="<?php echo (int)$Page->id?>">
    
  </form>
  <script src="/js/catalog_filter.js"></script>
</div>