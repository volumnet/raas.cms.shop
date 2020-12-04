<?php
/**
 * Фильтр каталога
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
            if ((
                (trim($valueData['value']) !== '') &&
                (trim($valueData['doRich']) !== '') &&
                (
                    ($prop->datatype != 'material') ||
                    (trim($valueData['value']) !== '0') // Чтобы не отображались нули в материальных полях
                )
            ) ||
                ($prop->datatype == 'number')
            ) {
                if (($pageMime == 'application/json') || // Чтобы не перегружать код всеми значениями
                    ($valueData['checked']) ||
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
}
$properties = array_values($result['properties']); // Для сохранения сортировки JavaScript

if (!$filterProps) {
    usort($properties, function ($a, $b) {
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

?>
<script type="text/html" data-v-pre id="catalog-filter-property-value-template">
  <label class="catalog-filter-property-value" v-bind:class="{ 'catalog-filter-property-value_disabled': !value.enabled }">
    <input v-bind:type="((property.datatype == 'checkbox') && !property.multiple) ? 'radio' : 'checkbox'" class="catalog-filter-property-value__input" v-bind:name="property.urn + ((property.datatype == 'checkbox' && !property.multiple) ? '' : '[]')" v-bind:disabled="!value.enabled || false" v-bind:value="value.value" v-bind:checked="value.checked" v-on:click="$emit('change', $event);">
    {{ value.doRich }}
  </label>
</script>

<script type="text/html" data-v-pre id="catalog-filter-range-slider-template">
  <div class="catalog-filter-range-slider" v-bind:data-from="valuefrom" v-bind:data-to="valueto"></div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-range-template">
  <div class="catalog-filter-range">
    <div class="catalog-filter-range__controls">
      <div class="catalog-filter-range__control-label">от</div>
      <div class="catalog-filter-range__control">
        <input type="number" class="form-control" v-bind:min="min" v-bind:max="max" v-bind:step="step" v-bind:name="property.urn + '_from'" v-bind:placeholder="min" v-model="valuefrom" v-on:change="changeByInput($event)" />
      </div>
      <div class="catalog-filter-range__control-label">до</div>
      <div class="catalog-filter-range__control">
        <input type="number" class="form-control" v-bind:min="min" v-bind:max="max" v-bind:step="step" v-bind:name="property.urn + '_to'" v-bind:placeholder="max" v-model="valueto" v-on:change="changeByInput($event)" />
      </div>
    </div>
    <div class="catalog-filter-range__slider">
      <catalog-filter-range-slider v-bind:min="min" v-bind:max="max" v-bind:step="step" v-bind:valuefrom="valuefrom" v-bind:valueto="valueto" v-on:change="changeBySlider($event)"></catalog-filter-range-slider>
    </div>
  </div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-property-list-template">
  <div class="catalog-filter-property-values-list">
    <div class="catalog-filter-property-values-list__item" v-for="value of values">
      <catalog-filter-property-value v-bind:property="property" v-bind:value="value" v-on:change="$emit('change', $event);"></catalog-filter-property-value>
    </div>
  </div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-property-selector-template">
  <select class="catalog-filter-property-selector" v-bind:name="property.urn" v-on:change="$emit('change', $event);">
    <option value="" v-bind:selected="!data[property.urn]">не важно</option>
    <option v-for="value of values" v-if="value.enabled" v-bind:value="value.value" v-bind:selected="value.checked">
      {{ value.doRich }}
    </option>
  </select>
</script>

<script type="text/html" data-v-pre id="catalog-filter-property-template">
  <div class="catalog-filter-property" v-bind:class="{ 'catalog-filter-property_active': active }">
    <div class="catalog-filter-property__title" v-on:click="toggle()">
      {{ property.name }}
      <span class="catalog-filter-property__toggle"></span>
    </div>
    <div class="catalog-filter-property__inner" data-raas-role="catalog-filter-property__inner">
      <catalog-filter-range v-if="(property.datatype == 'number') || ([].indexOf(property.urn) != -1)" v-bind:property="property" v-bind:filter="filter" v-bind:data="data" v-on:change="$emit('change', $event);"></catalog-filter-range>
      <div class="catalog-filter-property__list" v-else-if="multiple">
        <catalog-filter-property-list v-bind:property="property" v-bind:values="realValues" v-on:change="$emit('change', $event)"></catalog-filter-property-list>
      </div>
      <catalog-filter-property-selector v-else v-bind:property="property" v-bind:data="data" v-bind:values="realValues" v-on:change="$emit('change', $event)"></catalog-filter-property-selector>
    </div>
  </div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-properties-list-template">
  <div class="catalog-filter-properties-list">
    <div class="catalog-filter-properties-list__item">
      <div class="catalog-filter-property catalog-filter-property_fixed">
        <div class="catalog-filter-property__title">
          Поиск:
        </div>
        <div class="catalog-filter-property__inner">
          <input type="text" class="form-control catalog-filter-inputtext" id="catalog-filter__search-string" name="search_string" v-bind:value="data.search_string" />
        </div>
      </div>
    </div>
    <div class="catalog-filter-properties-list__item" v-for="(property, propId) in properties">
      <catalog-filter-property v-bind:property="property" v-bind:data="data" v-bind:filter="filter" v-bind:multiple="multiple" v-on:change="$emit('change', $event);"></catalog-filter-property>
    </div>
  </div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-preview-marker-template">
  <div class="catalog-filter-preview-marker" v-bind:class="{ 'catalog-filter-preview-marker_floating': float, 'catalog-filter-preview-marker_static': !float, 'catalog-filter-preview-marker_active': active }">
    <span class="catalog-filter-preview-marker__results">
      Найдено
      <span class="catalog-filter-preview-marker__counter">
        {{ counter }}
      </span>
      <span class="catalog-filter-preview-marker__items-text">
        {{ numTxt(counter, ['товаров', 'товар', 'товара']) }}
      </span>
    </span>
    <a class="catalog-filter-preview-marker__link" v-if="float" v-on:click="$emit('submit')">
      Показать
    </a>
  </div>
</script>

<script type="text/html" data-v-pre id="catalog-filter-template">
  <form action="" method="get" class="catalog-filter__inner">
    <input type="hidden" name="sort" v-bind:value="data.sort || ''">
    <input type="hidden" name="order" v-bind:value="data.order || ''">
    <div class="catalog-filter__list">
      <catalog-filter-properties-list v-bind:data="data" v-bind:filter="filter" v-bind:properties="properties" v-bind:multiple="multiple" v-on:change="change($event)"></catalog-filter-properties-list>
    </div>
    <catalog-filter-preview-marker v-bind:counter="counter" v-bind:active="previewTimeoutId" v-bind:lastactiveelement="lastActiveElement" v-bind:float="floatingMarker" v-on:submit="submit()"></catalog-filter-preview-marker>
    <div class="catalog-filter__controls">
      <button type="submit" class="btn btn-primary" v-on:click="submit($event);">
        <?php echo DO_SEARCH?>
      </button>
      <a href="<?php echo htmlspecialchars($Page->url)?>" class="btn btn-secondary">
        <?php echo RESET?>
      </a>
    </div>
  </form>
</script>

<?php
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
    <div class="catalog-filter__inner" data-role="catalog-filter" data-vue-role="catalog-filter"></div>
  </div>
</div>
<?php Package::i()->requestJS(['/js/catalog-filter.js']); ?>
