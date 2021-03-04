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
