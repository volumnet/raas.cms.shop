<?php
/**
 * Фильтр каталога (статическая версия)
 * @param Block_PHP $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\PropsHelper;
use RAAS\CMS\Snippet;

$DATA = $_GET;
if (!$DATA['sort']) {
    $DATA['sort'] = ''; // Иначе в JavaScript будет подставляться функция сортировки массивов
}

$pageMime = $Page->mime;
$catalogBlock = Block::spawn(37);
$catalogInterface = new CatalogInterface();
if ($pageMime == 'application/json') {
    $catalog = new Page($DATA['id']);
    unset($DATA['block_id'], $DATA['id']);
} else {
    $catalog = $Page;
}
$catalogInterface->setCatalogFilter($catalogBlock, $catalog, $DATA);
$hiddenProps = Snippet::importByURN('hidden_props')->process();
$hiddenProps = array_diff($hiddenProps, ['price']);
$availableProperties = $catalog->catalogFilter->availableProperties;

if ($filterProps = (array)$catalogInterface->getMetaTemplate($catalog, 'filter_props')) {
    $availableProperties = array_intersect_key(
        $availableProperties,
        array_flip($filterProps)
    );
    uksort($availableProperties, function ($a, $b) use ($filterProps) {
        return array_search($a, $filterProps) - array_search($b, $filterProps);
    });
}

$availableProperties = array_filter(
    $availableProperties,
    function ($propId) use ($catalog, $hiddenProps) {
        $prop = $catalog->catalogFilter->properties[$propId];
        $propURN = $prop->urn;
        return !in_array($propURN, $hiddenProps) &&
               !in_array($prop->datatype, ['image', 'file']);
    },
    ARRAY_FILTER_USE_KEY
);

$result = [
    'counter' => count($catalog->catalogFilter->getIds()),
    'filter' => $catalog->catalogFilter->filter,
    'data' => $DATA,
    'properties' => [],
];
foreach ($availableProperties as $propId => $availableProperty) {
    $prop = $catalog->catalogFilter->properties[$propId];
    if ((count($availableProperty) > 1) ||
        (($prop->datatype == 'checkbox') && !$prop->multiple)
    ) { // Чтобы не было свойств с одним значением
        $result['properties'][trim($propId)] = [
            'id' => (int)$propId,
            'urn' => $prop->urn,
            'datatype' => $prop->datatype,
            'multiple' => (int)$prop->multiple,
            'name' => $prop->name,
            'stdSource' => $prop->stdSource,
            'priority' => (int)$prop->priority,
            'values' => []
        ];
        foreach ($availableProperty as $value => $valueData) {
            unset($valueData['prop']);
            if (((trim($valueData['value']) !== '') && (trim($valueData['doRich']) !== '')) ||
                ($prop->datatype == 'number')
            ) {
                // 2020-05-11, AVS: добавлено условие ($prop->datatype == 'number'),
                // чтобы цена подгружалась сразу - иначе (т.к. слайдеры не меняются)
                // слайдер цены (и прочих числовых полей) не работает
                $result['properties'][trim($propId)]['values'][trim($value)] = $valueData;
            }
        }
    }
}
$properties = $result['properties'];

if (!$filterProps) {
    uasort($properties, function ($a, $b) {
        if (($a['urn'] == 'price') && ($b['urn'] != 'price')) {
            return -1;
        }
        if (($a['urn'] != 'price') && ($b['urn'] == 'price')) {
            return 1;
        }
        return $a['priority'] - $b['priority'];
    });
}
$result['properties'] = $properties;

if ($pageMime == 'application/json') {
    echo json_encode($result);
    exit;
} elseif (!$catalog->catalogFilter->getIds()) {
    return;
}

/**
 * Получает значение свойства фильтра
 * @param array $value <pre>[
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]</pre> Значение свойства
 */
$getCatalogFilterPropertyValue = function (array $value) {
    $property = $value['prop'];
    ?>
    <label class="catalog-filter-property-value" data-v-bind_class="{ 'catalog-filter-property-value_disabled': (value && !value.enabled) }">
      <input type="<?php echo (($property->datatype == 'checkbox') && !$property->multiple) ? 'radio' : 'checkbox'?>" class="catalog-filter-property-value__input" name="<?php echo htmlspecialchars($property->urn) . (($property->datatype == 'checkbox' && !$property->multiple) ? '' : '[]')?>" value="<?php echo htmlspecialchars($value['value'])?>"<?php echo $value['checked'] ? ' checked="checked"' : ''?> data-v-bind_disabled="(value && !value.enabled) || false" data-v-on_click="$emit('change', $event);">
      <a href="#">
        <?php echo htmlspecialchars($value['doRich'])?>
      </a>
    </label>
<?php };


/**
 * Получает слайдер диапазона фильтра
 */
$getCatalogFilterRangeSlider = function () { ?>
    <div class="catalog-filter-range-slider" data-v-bind_data-from="valuefrom" data-v-bind_data-to="valueto"></div>
<?php };


/**
 * Получает диапазон фильтра
 * @param Material_Field $property Свойство фильтрации
 */
$getCatalogFilterRange = function (Material_Field $property) use (&$getCatalogFilterRangeSlider) { ?>
    <div class="catalog-filter-range">
      <div class="catalog-filter-range__controls">
        <div class="catalog-filter-range__control-label">от</div>
        <div class="catalog-filter-range__control">
          <input type="number" class="form-control" name="<?php echo htmlspecialchars($property->urn . '_from')?>" data-v-bind_min="min" data-v-bind_max="max" data-v-bind_step="step" data-v-bind_placeholder="min" data-v-model="valuefrom" data-v-on_change="changeByInput($event)" />
        </div>
        <div class="catalog-filter-range__control-label">до</div>
        <div class="catalog-filter-range__control">
          <input type="number" class="form-control" name="<?php echo htmlspecialchars($property->urn . '_to')?>" data-v-bind_min="min" data-v-bind_max="max" data-v-bind_step="step" data-v-bind_placeholder="max" data-v-model="valueto" data-v-on_change="changeByInput($event)" />
        </div>
      </div>
      <div class="catalog-filter-range__slider">
        <div data-vue-role="catalog-filter-range-slider" data-inline-template data-v-bind_min="min" data-v-bind_max="max" data-v-bind_step="step" data-v-bind_valuefrom="valuefrom" data-v-bind_valueto="valueto" data-v-on_change="changeBySlider($event)">
          <?php $getCatalogFilterRangeSlider()?>
        </div>
      </div>
    </div>
<?php };


/**
 * Получает список значений свойства фильтра
 * @param Material_Field $property Свойство
 * @param array $values <pre>array<mixed[] значение => [
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]>></pre>
 */
$getCatalogFilterPropertyValuesList = function (
    Material_Field $property,
    array $values
) use (&$getCatalogFilterPropertyValue) {
    if (($property->datatype == 'checkbox') && !$property->multiple) {
        $values = ['' => [
            'enabled' => true,
            'value' => '',
            'doRich' => 'не важно',
            'art' => true,
        ]] + $values;
        $values = array_map(function ($x) {
            if (trim($x['value']) === '1') {
                $x['doRich'] = 'да';
            } elseif (trim($x['value']) === '0') {
                $x['doRich'] = 'нет';
            }
            $x['art'] = false;
            return $x;
        }, $values);
        for ($i = 1; $i >= 0; $i--) {
            if (!isset($values[$i])) {
                $values[trim($i)] = [
                    'enabled' => false,
                    'value' => trim($i),
                    'doRich' => $i ? 'да' : 'нет',
                    'checked' => false,
                    'art' => true,
                ];
            }
        }
    }
    ?>
    <div class="catalog-filter-property-values-list">
      <?php foreach ($values as $key => $value) {
          if (trim($value['doRich']) && trim($value['value'])) { ?>
              <div class="catalog-filter-property-values-list__item">
                <div data-vue-role="catalog-filter-property-value" data-inline-template data-v-bind_property="property" data-v-bind_value="values['<?php echo htmlspecialchars($key)?>']" data-v-on_change="$emit('change', $event);">
                  <?php $getCatalogFilterPropertyValue($value)?>
                </div>
              </div>
          <?php }
      } ?>
    </div>
<?php };


/**
 * Получает селектор свойства фильтра
 * @param Material_Field $property Свойство
 * @param array $values <pre>array<mixed[] значение => [
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]>></pre>
 */
$getCatalogFilterPropertySelector = function (
    Material_Field $property,
    array $values
) use (&$DATA) { ?>
    <select class="catalog-filter-property-selector" name="<?php echo htmlspecialchars($property->urn)?>" data-v-on_change="$emit('change', $event);">
      <option value=""<?php echo !$DATA[$property->urn] ? ' selected="selected"' : ''?>>
        не важно
      </option>
      <?php foreach ($values as $key => $value) { ?>
          <option value="<?php echo htmlspecialchars($value['doRich'])?>"<?php echo $value['checked'] ? ' selected="selected"' : ''?> data-v-if="!values[<?php echo htmlspecialchars($key)?>] || values[<?php echo htmlspecialchars($key)?>].enabled">
            <?php echo htmlspecialchars($value['doRich'])?>
          </option>
      <?php } ?>
    </select>
<?php };


/**
 * Получает свойство фильтра
 * @param Material_Field $property Свойство
 * @param array $values <pre>array<mixed[] значение => [
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]>></pre>
 */
$getCatalogFilterProperty = function (
    Material_Field $property,
    array $values
) use (
    &$getCatalogFilterRange,
    &$getCatalogFilterPropertyValuesList,
    &$getCatalogFilterPropertySelector
) {
    ?>
    <div class="catalog-filter-property" data-v-bind_class="{ 'catalog-filter-property_active': active }">
      <div class="catalog-filter-property__title" data-v-on_click="toggle()">
        <?php echo htmlspecialchars($property->name)?>
        <span class="catalog-filter-property__toggle"></span>
      </div>
      <div class="catalog-filter-property__inner" data-raas-role="catalog-filter-property__inner">
        <?php if (($property->datatype == 'number') || in_array($property->urn, [])) { ?>
            <div data-vue-role="catalog-filter-range" data-inline-template data-v-bind_property="property" data-v-bind_filter="filter" data-v-bind_data="data" data-v-on_change="$emit('change', $event);">
              <?php $getCatalogFilterRange($property)?>
            </div>
        <?php } elseif ($multiple = true) { // Выбор множественного значения ?>
            <div class="catalog-filter-property__list">
              <div data-vue-role="catalog-filter-property-list" data-inline-template data-v-bind_property="property" data-v-bind_values="property.values" data-v-on_change="$emit('change', $event)">
                <?php $getCatalogFilterPropertyValuesList($property, $values)?>
              </div>
            </div>
        <?php } else { ?>
            <div data-vue-role="catalog-filter-property-selector" data-inline-template data-v-bind_property="property" data-v-bind_data="data" data-v-bind_values="property.values" data-v-on_change="$emit('change', $event)">
              <?php $getCatalogFilterPropertySelector($property, $values)?>
            </div>
        <?php } ?>
      </div>
    </div>
    <?php
};


/**
 * Получает список свойств фильтра
 * @param array $properties <pre>array<string[] ID# свойства => array<mixed[] значение => [
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]>></pre>
 */
$getCatalogFilterPropertiesList = function (array $properties) use (
    &$getCatalogFilterProperty,
    &$catalog,
    &$DATA
) { ?>
    <div class="catalog-filter-properties-list">
      <div class="catalog-filter-properties-list__item">
        <div class="catalog-filter-property catalog-filter-property_fixed">
          <div class="catalog-filter-property__title">
            Поиск:
          </div>
          <div class="catalog-filter-property__inner">
            <input type="text" class="form-control catalog-filter-inputtext" id="catalog-filter__search-string" name="search_string" value="<?php echo htmlspecialchars($DATA['search_string'])?>" />
          </div>
        </div>
      </div>
      <?php foreach ($properties as $propId => $values) {
          if (count((array)$values) <= 1) {
              continue;
          }
          $property = $catalog->catalogFilter->properties[$propId];
          ?>
          <div class="catalog-filter-properties-list__item">
            <div data-vue-role="catalog-filter-property" data-inline-template data-v-bind_property="properties['<?php echo htmlspecialchars($propId)?>']" data-v-bind_data="data" data-v-bind_filter="filter" data-v-bind_multiple="multiple" data-v-on_change="$emit('change', $event);">
              <?php $getCatalogFilterProperty($property, $values)?>
            </div>
          </div>
      <?php } ?>
    </div>
<?php };


/**
 * Получает маркер предпросмотра фильтра
 */
$getCatalogFilterPreviewMarker = function () { ?>
    <div class="catalog-filter-preview-marker" data-v-bind_class="{ 'catalog-filter-preview-marker_floating': float, 'catalog-filter-preview-marker_static': !float, 'catalog-filter-preview-marker_active': active }">
      <span class="catalog-filter-preview-marker__results">
        Найдено
        <span class="catalog-filter-preview-marker__counter" data-v-html="counter"></span>
        <span class="catalog-filter-preview-marker__items-text" data-v-html="numTxt(counter, ['товаров', 'товар', 'товара'])"></span>
      </span>
      <a class="catalog-filter-preview-marker__link" data-v-if="float" data-v-on_click="$emit('submit')">
        Показать
      </a>
    </div>
<?php };


/**
 * Получает фильтр
 * @param array $properties <pre>array<string[] ID# свойства => array<mixed[] значение => [
 *     'value' => mixed значение,
 *     'doRich' => mixed Отформатированное значение
 *     'prop' => Material_Field свойство, к которому относится значение
 *     'checked' => bool Установлено ли значение
 *     'enabled' => bool Активно ли значение
 * ]>></pre>
 */
$getCatalogFilter = function (array $properties) use (
    &$getCatalogFilterPropertiesList,
    &$getCatalogFilterPreviewMarker,
    $Page
) { ?>
    <form action="" method="get" class="catalog-filter__inner">
      <input type="hidden" name="sort" data-v-bind_value="data.sort || ''">
      <input type="hidden" name="order" data-v-bind_value="data.order || ''">
      <div class="catalog-filter__list">
        <div data-vue-role="catalog-filter-properties-list" data-inline-template data-v-bind_data="data" data-v-bind_filter="filter" data-v-bind_properties="properties" data-v-bind_multiple="multiple" data-v-on_change="change($event)">
          <?php $getCatalogFilterPropertiesList($properties)?>
        </div>
      </div>
      <div data-vue-role="catalog-filter-preview-marker" data-inline-template data-v-bind_counter="counter" data-v-bind_active="previewTimeoutId" data-v-bind_lastactiveelement="lastActiveElement" data-v-bind_float="floatingMarker" data-v-on_submit="submit()">
        <?php $getCatalogFilterPreviewMarker()?>
      </div>
      <div class="catalog-filter__controls">
        <button type="submit" class="btn btn-primary" data-v-on_click="submit($event);">
          <?php echo DO_SEARCH?>
        </button>
        <a href="<?php echo htmlspecialchars($Page->url)?>" class="btn btn-default">
          <?php echo RESET?>
        </a>
      </div>
    </form>
<?php };


$vueData = array_merge($result, [
    'catalogId' => (int)$catalog->id,
    'blockId' => (int)$catalogBlock->id,
]);
?>
<script>
var raasShopCatalogFilterData = <?php echo json_encode($vueData)?>;
</script>
<div class="catalog-filter__outer">
  <div class="catalog-filter">
    <div class="catalog-filter__header">
      <div class="catalog-filter__title">
        <?php echo htmlspecialchars($Block->name)?>
      </div>
      <div class="catalog-filter__close">
        <a class="catalog-filter__close-link"></a>
      </div>
    </div>
    <div class="catalog-filter__inner" data-role="catalog-filter" data-vue-role="catalog-filter" data-inline-template>
      <?php $getCatalogFilter($availableProperties)?>
    </div>
  </div>
</div>
<?php Package::i()->requestJS(['/js/catalog-filter.js']); ?>
