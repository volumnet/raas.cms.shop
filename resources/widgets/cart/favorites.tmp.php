<?php
/**
 * Виджет блока избранного
 * @param Page $Page Текущая страница
 * @param Block_Cart $Block Текущий блок
 * @param Cart $Cart Текущая корзина
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\AssetManager;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Material_Field;

if ($Page->mime == 'application/json') {
    $cartData = [];
    $cartData['count'] = (int)$Cart->count;
    $cartData['items'] = [];
    foreach ($Cart->items as $i => $cartItem) {
        $item = $cartItem->material;
        $propsCache = (array)json_decode($item->cache_shop_props, true);
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
            'props' => function ($item) use ($propsCache) {
                if ($propsCache) {
                    $propsTable = [];
                    foreach ((array)$propsCache['main_props'][$item->cache_url_parent_id] as $propData) {
                        if ($propData['values']) {
                            $propsTable[] = [
                                'name' => $propData['name'],
                                'value' => implode(', ', array_map(function ($x) {
                                    return is_array($x) ? $x['name'] : $x;
                                }, $propData['values'])),
                            ];
                        }
                    }
                } else {
                    if (!$mainProps) {
                        $catalogInterface = new CatalogInterface();
                        $mainPropsIds = (array)$catalogInterface->getMetaTemplate($item->urlParent, 'main_props');
                        $mainProps = [];
                        foreach ($mainPropsIds as $mainPropId) {
                            $field = new Material_Field($mainPropId);
                            if ($field->id) {
                                $mainProps[] = new Material_Field($mainPropId);
                            }
                        }
                    }
                    $propsTable = [];
                    foreach ($mainProps as $prop) {
                        $field = $prop->deepClone();
                        $field->Owner = $item;
                        if ($val = $field->doRich()) {
                            $propsTable[] = ['name' => $field->name, 'value' => $val];
                        }
                    }
                }
                return $propsTable;
            },
            'eCommerce' => ECommerce::getProduct($item, $i),
        ]);
        $cartData['items'][] = $cartItemData;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($cartData);
    exit;
} else { ?>
    <favorites class="favorites" :cart="favorites">
      <div class="favorites__loading">
        <?php echo FAVORITES_IS_LOADING?>
      </div>
    </favorites>
    <?php
    AssetManager::requestCSS('/css/favorites.css');
    AssetManager::requestJS('/js/favorites.js');
}
