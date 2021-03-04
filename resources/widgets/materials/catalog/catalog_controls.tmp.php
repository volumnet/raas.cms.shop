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
<div class="catalog-controls">
  <div class="catalog-controls__title">Сортировка по:</div>
  <div class="catalog-controls__list">
    <div class="catalog-controls-list">
      <?php foreach ($variants as $key => $name) {
          $isActive = ($sort == $key);
          ?>
          <div class="catalog-controls-list__item<?php echo ($isActive ? (' catalog-controls-list__item_active catalog-controls-list__item_' . htmlspecialchars($order)) : '')?>">
            <a href="<?php echo HTTP::queryString('sort=' . $key . '&order=' . (($isActive && ($order == 'asc')) ? 'desc' : 'asc'))?>" class="catalog-controls-item<?php echo ($isActive ? (' catalog-controls-item_active catalog-controls-item_' . htmlspecialchars($order)) : '')?>">
              <?php echo htmlspecialchars($name)?>
            </a>
          </div>
      <?php } ?>
    </div>
  </div>
</div>
