<?php
/**
 * Виджет сортировки для каталога
 * @param Block_Material $Текущий блок
 * @param string $sort Вариант сортировки
 * @param 'asc'|'desc' $order Направление сортировки
 */
namespace RAAS\CMS;

use SOME\HTTP;

$variantNames = ['price' => 'цене', 'name' => 'названию'];
$variants = [];
foreach ($Block->sort as $var) {
    if ($variantNames[$var['var']]) {
        $name = $variantNames[$var['var']];
    } elseif (is_numeric($var['field'])) {
        $field = new Material_Field($var['field']);
        $name = $field->name;
    } else {
        $name = constant(mb_strtoupper($var['var']));
    }
    $variants[$var['var']] = $name;
}
?>
<div class="catalog-sort">
  <div class="catalog-sort__title">Сортировка по:</div>
  <div class="catalog-sort__list">
    <div class="catalog-sort-list">
      <?php foreach ($variants as $key => $name) {
          $isActive = ($sort == $key);
          ?>
          <div class="catalog-sort-list__item<?php echo ($isActive ? (' catalog-sort-list__item_active catalog-sort-list__item_' . htmlspecialchars($order)) : '')?>">
            <a href="<?php echo HTTP::queryString('sort=' . $key . '&order=' . (($isActive && ($order == 'asc')) ? 'desc' : 'asc'))?>" class="catalog-sort-item<?php echo ($isActive ? (' catalog-sort-item_active catalog-sort-item_' . htmlspecialchars($order)) : '')?>">
              <?php echo htmlspecialchars($name)?>
            </a>
          </div>
      <?php } ?>
    </div>
  </div>
</div>
