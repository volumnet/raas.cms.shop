<?php
/**
 * Фильтр каталога
 * @param Block_PHP $Block Текущий блок
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\AssetManager;
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

if ($pageMime == 'application/json') {
    $catalog = new Page($DATA['id']);
    $catalogBlock = Block::spawn((int)$DATA['block_id']);
    unset($DATA['block_id'], $DATA['id']);
} else {
    $catalogBlock = Block::spawn((int)$Block->additionalParams['catalogBlockId']);
    $catalog = $Page;
}
$catalogInterface = new CatalogInterface();
$catalogInterface->setCatalogFilter($catalogBlock, $catalog, $DATA);
$availableProperties = $catalog->catalogFilter->availableProperties;

$filterProps = $catalogInterface->getMetaTemplate($catalog, 'filter_props');
$filterProps = array_values(array_filter((array)$filterProps));
if ($filterProps) {
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
    function ($propId) use ($catalog) {
        $prop = $catalog->catalogFilter->properties[$propId];
        $propURN = $prop->urn;
        return ($prop->vis || $prop->urn == 'price') &&
            !in_array($prop->datatype, ['image', 'file']);
    },
    ARRAY_FILTER_USE_KEY
);

$result = [
    'counter' => count($catalog->catalogFilter->getIds()),
    'filter' => (object)$catalog->catalogFilter->filter,
    'formData' => $DATA,
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
        $isNumericByValues = null;
        if ($prop->datatype == 'text') {
            foreach ($availableProperty as $value => $valueData) {
                if ($valueData['doRich'] && !is_numeric($valueData['doRich'])) {
                    $isNumericByValues = false;
                } elseif ($isNumericByValues === null) {
                    $isNumericByValues = true;
                }
            }
            if ($isNumericByValues) {
                $result['properties'][trim($propId)]['datatype'] = 'number';
            }
        }
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
                ($prop->datatype == 'number') ||
                $isNumericByValues
            ) {
                // 2021-11-30, AVS: закомментировал условие, т.к. фильтр
                // изначально статический, и на странице с выбранным значением
                // по умолчанию AJAX-ом не подгружается, оставляя только
                // выбранное значение
                // if (($pageMime == 'application/json') || // Чтобы не перегружать код всеми значениями
                //     ($valueData['checked']) ||
                //     ($prop->datatype == 'number')
                // ) {
                    // 2020-05-11, AVS: добавлено условие ($prop->datatype == 'number'),
                    // чтобы цена подгружалась сразу - иначе (т.к. слайдеры не меняются)
                    // слайдер цены (и прочих числовых полей) не работает
                    $result['properties'][trim($propId)]['values'][trim($value)] = $valueData;
                // }
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

$static = (bool)(int)$Block->additionalParams['static'];
?>
<div class="catalog-filter__outer">
  <div class="catalog-filter" <?php echo $static ? '': ' style="display: none" data-v-bind_style="{ display: \'block\' }"'?> data-vue-role="catalog-filter" data-v-bind_catalog-id="<?php echo (int)$catalog->id?>" data-v-bind_block-id="<?php echo (int)$catalogBlock->id?>" data-v-bind_source="<?php echo htmlspecialchars(json_encode($result))?>" data-v-slot="vm">
    <div class="catalog-filter__header">
      <div class="catalog-filter__title">
        <?php echo htmlspecialchars($Block->name)?>
      </div>
      <div class="catalog-filter__close">
        <button class="catalog-filter__close-link btn-close" data-v-on_click="vm.close()"></button>
      </div>
    </div>
    <form action="" method="get" class="catalog-filter__inner">
      <div class="catalog-filter__listcatalog-filter-properties-list" data-vue-role="catalog-filter-properties-list" data-v-bind_form-data="vm.formData" data-v-bind_filter="vm.filter" data-v-bind_properties="vm.properties" data-v-bind_multiple="vm.multiple" data-v-on_input="vm.change($event)">
        <?php if ($static) {
            foreach ($availableProperties as $propId => $values) {
                if (count((array)$values) <= 1) {
                    continue;
                }
                $property = $catalog->catalogFilter->properties[$propId];
                ?>
                <div class="catalog-filter-properties-list__itemcatalog-filter-property catalog-filter-property_active">
                  <div class="catalog-filter-property__title">
                    <?php echo htmlspecialchars($property->name)?>
                  </div>
                  <div class="catalog-filter-property__inner">
                    <?php if (($property->datatype == 'number') || in_array($property->urn, [])) {
                        $valueKeys = array_keys($values);
                        $valueKeys = array_filter($valueKeys);
                        $min = $max = 0;
                        $step = 1;
                        if ($valueKeys) {
                            $min = min($valueKeys);
                            $max = max($valueKeys);
                            $minRealStep = null;
                            for ($i = 0; $i < count($valueKeys) - 1; $i++) {
                                $delta = $valueKeys[$i + 1] - $valueKeys[$i];
                                if (($delta > 0) && (!$minRealStep || ($delta < $minRealStep))) {
                                    $minRealStep = $delta;
                                }
                            }
                            $step = pow(10, floor(log10($minRealStep)));
                        }
                        ?>
                        <div class="catalog-filter-range">
                          <div class="catalog-filter-range__controls">
                            <?php echo FROM?>
                            <input type="number" class="catalog-filter-range__control form-control" name="<?php echo htmlspecialchars($property->urn . '_from')?>" min="<?php echo $min?>" max="<?php echo $max?>" step="<?php echo $step?>" placeholder="<?php echo $min?>" value="<?php echo htmlspecialchars($DATA[$property->urn . '_from'])?>" />
                            <?php echo TO?>
                            <input type="number" class="catalog-filter-range__control form-control" name="<?php echo htmlspecialchars($property->urn . '_to')?>" min="<?php echo $min?>" max="<?php echo $max?>" step="<?php echo $step?>" placeholder="<?php echo $min?>" value="<?php echo htmlspecialchars($DATA[$property->urn . '_to'])?>" />
                          </div>
                        </div>
                    <?php } elseif ($multiple = true) { // Выбор множественного значения ?>
                        <div class="catalog-filter-property__list catalog-filter-property-values-list">
                          <?php
                          if (($property->datatype == 'checkbox') && !$property->multiple) {
                              $values = ['' => [
                                  'enabled' => true,
                                  'value' => '',
                                  'doRich' => DOESNT_MATTER,
                              ]] + $values;
                              $values = array_map(function ($x) {
                                  if (trim($x['value']) === '1') {
                                      $x['doRich'] = _YES;
                                  } elseif (trim($x['value']) === '0') {
                                      $x['doRich'] = _NO;
                                  }
                                  return $x;
                              }, $values);
                              for ($i = 1; $i >= 0; $i--) {
                                  if (!isset($values[$i])) {
                                      $values[trim($i)] = [
                                          'enabled' => false,
                                          'value' => trim($i),
                                          'doRich' => $i ? _YES : _NO,
                                          'checked' => false,
                                      ];
                                  }
                              }
                          }
                          foreach ($values as $key => $value) {
                              if (trim($value['doRich']) && trim($value['value'])) { ?>
                                <label class="catalog-filter-property-values-list__item catalog-filter-property-value<?php echo !$value['enabled'] ? ' catalog-filter-property-value_disabled' : ''?>">
                                  <input type="<?php echo (($property->datatype == 'checkbox') && !$property->multiple) ? 'radio' : 'checkbox'?>" class="catalog-filter-property-value__input" name="<?php echo htmlspecialchars($property->urn) . (($property->datatype == 'checkbox' && !$property->multiple) ? '' : '[]')?>" value="<?php echo htmlspecialchars($value['value'])?>"<?php echo ($value['checked'] ? ' checked="checked"' : '') . (!$value['enabled'] ? ' disabled="disabled"' : '')?>>
                                  <a href="<?php echo htmlspecialchars($catalog->catalogFilter->getCanonicalURLFromFilter($catalog->catalogFilter->filter, $property->urn, $value['value']))?>" rel="nofollow">
                                    <?php echo htmlspecialchars($value['doRich'])?>
                                  </a>
                                </label>
                              <?php }
                          } ?>
                        </div>
                    <?php } else { ?>
                        <select class="catalog-filter-property-selector form-control" name="<?php echo htmlspecialchars($property->urn)?>">
                          <option value=""<?php echo !$DATA[$property->urn] ? ' selected="selected"' : ''?>>
                            <?php echo DOESNT_MATTER?>
                          </option>
                          <?php foreach ($values as $key => $value) {
                              if (!$value || $value['enabled']) { ?>
                                  <option value="<?php echo htmlspecialchars($value['doRich'])?>"<?php echo $value['checked'] ? ' selected="selected"' : ''?>>
                                    <?php echo htmlspecialchars($value['doRich'])?>
                                  </option>
                              <?php }
                          } ?>
                        </select>
                    <?php } ?>
                  </div>
                </div>
            <?php }
        } ?>
      </div>
      <div class="catalog-filter-preview-marker" data-vue-role="catalog-filter-preview-marker" data-v-bind_counter="vm.counter" data-v-bind_active="!!vm.previewTimeoutId" data-v-bind_last-active-element="vm.lastActiveElement" data-v-bind_float="vm.floatingMarker" data-v-on_submit="vm.submit()">
        <?php if ($static) { ?>
            <span class="catalog-filter-preview-marker__results">
              <?php echo FOUND?>
              <span class="catalog-filter-preview-marker__counter">
                <?php echo (int)$result['counter']?>
              </span>
              <?php echo htmlspecialchars(Text::numTxt(
                  (int)$result['counter'],
                  [FOUND_0_ITEMS, FOUND_1_ITEM, FOUND_2_ITEMS]
              ))?>
            </span>
        <?php } ?>
      </div>
      <div class="catalog-filter__controls">
        <button type="submit" class="btn btn-primary" data-v-on_click="vm.submit($event);">
          <?php echo DO_SEARCH?>
        </button>
        <a href="<?php echo htmlspecialchars($Page->url)?>" class="btn btn-secondary">
          <?php echo RESET?>
        </a>
      </div>
    </form>
  </div>
</div>
<?php
AssetManager::requestCSS(['/css/catalog-filter.css']);
AssetManager::requestJS(['/js/catalog-filter.js']);
?>
