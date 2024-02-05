<?php
/**
 * Панель управления категорией
 * @param Block $Block Текущий блок
 * @param Page $Page Текущая страница
 * @param string $sort Переменная сортировки
 * @param string $order Переменная упорядочения
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use RAAS\AssetManager;
use RAAS\CMS\Block;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

if (!$Page->catalogFilter->getIds()) {
    return; // Товары не выводятся, управлять нечем
}

$searchString = $_GET['search_string'];

$viewVariants = [
    'blocks' => ['urn' => 'blocks', 'name' => VIEW_AS_BLOCKS],
    'list' => ['urn' => 'list', 'name' => VIEW_AS_LIST],
];
$sortVariants = [
    ['urn' => 'price:asc', 'name' => SORT_BY_PRICE_ASC],
    ['urn' => 'price:desc', 'name' => SORT_BY_PRICE_DESC],
    ['urn' => 'name', 'name' => SORT_BY_NAME],
];
$defaultSort = $sort . (($sort != 'name') ? (':' . $order) : '');
$matchingVariants = array_values(array_filter(
    $sortVariants,
    function ($x) use ($defaultSort) {
        return $x['urn'] == $defaultSort;
    }
));
$defaultSortVariant = $matchingVariants ? $matchingVariants[0] : $sortVariants[0];
?>
<form class="catalog-controls" action="" data-vue-role="catalog-controls" data-v-slot="vm" data-v-bind_default-sort="<?php echo htmlspecialchars(json_encode($defaultSort))?>">
  <?php if ($searchString) { ?>
      <div class="catalog-controls__search">
        <div class="catalog-controls__search-title">
          Поиск:
        </div>
        <div class="catalog-controls__search-inner">
          «<?php echo htmlspecialchars($searchString)?>»
        </div>
      </div>
  <?php } else { ?>
      <div class="catalog-controls__sort">
        <div class="catalog-controls__sort-title">
          <?php echo SORT_BY?>:
        </div>
        <div class="catalog-controls__sort-inner catalog-controls-sort" data-vue-role="catalog-controls-sort" data-v-bind_source="<?php echo htmlspecialchars(json_encode($sortVariants))?>" data-v-bind_value="vm.sort" data-v-on_input="vm.changeSort($event); vm.update();">
          <button type="button" class="catalog-controls-sort__title">
            <?php echo htmlspecialchars($defaultSortVariant['name'])?>
          </button>
        </div>
      </div>
  <?php } ?>
  <button type="button" class="btn btn-primary catalog-controls__filter catalog-controls__filter-button" data-v-on_click="jqEmit('raas.shop.openfilter')"><?php echo FILTER?></button>
  <label class="catalog-controls__checkbox">
    <input data-vue-role="raas-field-checkbox" type="checkbox" class="raas-field-checkbox" data-v-model="vm.query.available" data-v-on_input="vm.update()">
    <?php echo AVAILABLE?>
  </label>
  <label class="catalog-controls__checkbox">
    <input data-vue-role="raas-field-checkbox" type="checkbox" class="raas-field-checkbox" data-v-model="vm.query.price_old_from" data-v-on_input="vm.update()">
    <?php echo DISCOUNT?>
  </label>
  <div class="catalog-controls__view">
    <div class="catalog-controls__view-title">
      <?php echo VIEW_AS?>:
    </div>
    <div class="catalog-controls-view-list">
      <?php foreach ($viewVariants as $viewVariant) { ?>
          <a class="catalog-controls-view-list__item catalog-controls-view-list__item_<?php echo htmlspecialchars($viewVariant['urn'])?> catalog-controls-view-item catalog-controls-view-item_<?php echo htmlspecialchars($viewVariant['urn'])?>" data-v-bind_class="{ 'catalog-controls-view-list__item_active': vm.activeViewVariant == <?php echo htmlspecialchars(json_encode($viewVariant['urn']))?>, 'catalog-controls-view-item_active': vm.activeViewVariant == <?php echo htmlspecialchars(json_encode($viewVariant['urn']))?> }" href="#<?php echo htmlspecialchars($viewVariant['urn'])?>" title="<?php echo htmlspecialchars($viewVariant['name'])?>" data-v-on_click.stop="vm.changeView(<?php echo htmlspecialchars(json_encode($viewVariant['urn']))?>)"></a>
      <?php } ?>
    </div>
  </div>
</form>
<?php
AssetManager::requestCSS(['/css/catalog-controls.css']);
AssetManager::requestJS(['/js/catalog-controls.js']);
