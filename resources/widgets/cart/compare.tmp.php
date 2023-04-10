<?php
/**
 * Виджет блока сравнения
 * @param Page $Page Текущая страница
 * @param Block_Cart $Block Текущий блок
 * @param Cart $Cart Текущая корзина
 * @param ?Material[] $Set <pre><code>array<
 *     string[] ID# товара => Material
 * ></code></pre> Набор материалов для сравнения
 * @param ?array $groups <pre><code>array<string[] ID# группы => [
 *     'id' => string ID# группы,
 *     'name' => string Наименование группы,
 *     'itemsIds' => int[] ID# товаров в группе
 * ]></code></pre> Набор групп
 * @param array $rawData <pre><code>array<string[] ID# материала => array<
 *     string[] ID# поля => string Сырое значение поля материала
 * >></code></pre> Сырые данные по материалам
 * @param Material_Field[] $fields <pre><code>array<
 *     string[] ID# поля => Material_Field Поле
 * ></code></pre>Задействованные поля
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\AssetManager;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Material_Field;

if (($Page->mime == 'application/json') || (int)($_GET['AJAX'] ?? 0)) {
    $allowedHiddenFields = ['brand'];

    $cartData = [];
    $cartData['count'] = (int)$Cart->count;
    $cartData['items'] = [];
    foreach ($Cart->items as $i => $cartItem) {
        $item = $cartItem->material;
        $cartItemFormatter = new CartItemArrayFormatter($cartItem);
        $cartItemData = $cartItemFormatter->format([
            'article',
            'url',
            'available',
            'unit',
            'visImages' => function ($item, $propsCache) {
                return array_map(function ($x) {
                    return [
                        'id' => $x->id,
                        'name' => $x->name,
                        'smallURL' => $x->smallURL,
                    ];
                }, $item->visImages);
            },
            'props' => function ($item) use (
                $fields,
                $rawData,
                $allowedHiddenFields
            ) {
                $result = [];
                foreach ($fields as $fid => $field) {
                    $values = (array)$rawData[$item->id][$fid];
                    if (!$field->multiple && !$values) {
                        $values = [''];
                    }
                    if ($field->vis ||
                        in_array($field->urn, $allowedHiddenFields)
                    ) {
                        $richArray = [];
                        foreach ($values as $fii => $value) {
                            if (($field->datatype == 'checkbox') && !$field->multiple) {
                                $richValue = $value ? _YES : _NO;
                            } elseif ($field->datatype == 'material') {
                                $material = new Material($value);
                                if ($url = $material->url) {
                                    $richValue = [
                                        'href' => $url,
                                        'text' => $material->name
                                    ];
                                } else {
                                    $richValue = $material->name;
                                }
                            } else {
                                $richValue = $field->doRich($value);
                            }
                            $richArray[] = $richValue;
                        }
                        $result[trim($fid)] = $richArray;
                    }
                }
                return $result;
            },
            'eCommerce' => ECommerce::getProduct($item, $i),
        ]);
        $cartData['items'][] = $cartItemData;
    }
    $cartData['additional']['groups'] = (array)$groups;
    $cartData['additional']['fields'] = [];
    if ($fields) {
        foreach ($fields as $field) {
            if ($field->vis || in_array($field->urn, $allowedHiddenFields)) {
                foreach ($cartData['items'] as $item) {
                    if ($item['props'][$field->id]) {
                        $cartData['additional']['fields'][] = [
                            'id' => (int)$field->id,
                            'name' => $field->name,
                            'datatype' => $field->datatype,
                        ];
                        break;
                    }
                }
            }
        }
    }

    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($cartData);
    exit;
} else { ?>
    <compare class="compare" :cart="compare">
      <div class="compare__loading">
        <?php echo COMPARE_IS_LOADING?>
      </div>
    </compare>
    <?php
    AssetManager::requestCSS('/css/compare.css');
    AssetManager::requestJS('/js/compare.js');
}
