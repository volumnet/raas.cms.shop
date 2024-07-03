<?php
namespace RAAS\CMS\Shop;

use RAAS\Attachment;
use RAAS\Crontab;
use RAAS\CMS\Block;
use RAAS\CMS\Block_Material;
use RAAS\CMS\Block_Menu;
use RAAS\CMS\Block_PHP;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Menu;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Page_Field;
use RAAS\CMS\Snippet;
use RAAS\CMS\Snippet_Folder;
use RAAS\CMS\Webmaster as CMSWebmaster;

class Webmaster extends CMSWebmaster
{
    protected static $instance;

    protected $leftMenuBlock;

    public function __get($var)
    {
        switch ($var) {
            case 'Site':
            case 'interfacesFolder':
            case 'widgetsFolder':
                return parent::__get($var);
                break;
            default:
                return Module::i()->__get($var);
                break;
        }
    }


    /**
     * Создаем стандартные сниппеты
     * @return array[Snippet] массив созданных или существующих интерфейсов
     */
    public function checkStdInterfaces()
    {
        $interfaces = [];
        $interfaces['__raas_shop_order_notify'] = $this->checkSnippet(
            $this->interfacesFolder,
            '__raas_shop_order_notify',
            'shop/form_notification.php',
        );

        return $interfaces;
    }


    /**
     * Добавим виджеты
     * @return array[Snippet] Массив созданных или существующих виджетов
     */
    public function createWidgets()
    {
        $widgets = [];
        $widgetsData = [
            'cart/cart' => View_Web::i()->_('CART'),
            'cart/favorites' => View_Web::i()->_('FAVORITES'),
            'cart/compare' => View_Web::i()->_('COMPARISON'),
            // 'cart/order' => View_Web::i()->_('VIEW_ORDER'),
            // 'epay/robokassa' => View_Web::i()->_('ROBOKASSA'),
            'epay/sberbank' => View_Web::i()->_('SBERBANK'),
            'materials/comments/rating' => View_Web::i()->_('RATING'),
            'cart/cart_main' => View_Web::i()->_('CART_MAIN'),
            'cart/favorites_main' => View_Web::i()->_('FAVORITES_MAIN'),
            'cart/compare_main' => View_Web::i()->_('COMPARISON_MAIN'),
            'materials/catalog/spec' => View_Web::i()->_('SPECIAL_OFFER'),
            'materials/catalog/popular_cats' => View_Web::i()->_('POPULAR_CATEGORIES'),
            'cart/my_orders' => View_Web::i()->_('MY_ORDERS'),
            'menu/menu_catalog' => View_Web::i()->_('MENU_CATALOG'),
            'menu/menu_left' => View_Web::i()->_('LEFT_MENU'),
        ];
        foreach ($widgetsData as $url => $name) {
            $urn = explode('/', $url);
            $urn = $urn[count($urn) - 1];
            $widget = Snippet::importByURN($urn);
            if (!($widget && $widget->id)) {
                $widget = $this->createSnippet(
                    $urn,
                    $name,
                    (int)$this->widgetsFolder->id,
                    Module::i()->resourcesDir . '/widgets/' . $url . '.tmp.php',
                    [
                        'WIDGET_NAME' => $name,
                        'WIDGET_URN' => $urn,
                        'WIDGET_CSS_CLASSNAME' => str_replace('_', '-', $urn)
                    ]
                );
            }
            $widgets[$urn] = $widget;
        }
        return $widgets;
    }


    /**
     * Создаем типов корзин
     * @param Material_Type $catalogType типа материалов: каталог
     * @param Material_Field $priceCol колонка с ценой
     * @param Form $orderForm Форма заказа
     * @return array[Cart_Type] массив созданных или существующих типов корзин
     */
    public function createCartTypes(
        Material_Type $catalogType,
        Material_Field $priceCol,
        Form $orderForm
    ) {
        $cartTypes = [];
        $CT = Cart_Type::importByURN('cart');
        if (!($CT && $CT->id)) {
            $CT = new Cart_Type([
                'id' => 1,
                'name' => View_Web::i()->_('CART'),
                'urn' => 'cart',
                'form_id' => (int)$orderForm->id,
                'no_amount' => 0,
                'weight_callback' => '$result = 0;' . "\n"
                    . 'foreach ($items as $item) {' . "\n"
                    . '    $itemWeight = ((float)$item->weight / 1000) ?: 0;' . "\n"
                    . '    $itemAmount = (int)$item->amount ?: 1;' . "\n"
                    . '    $result += $itemWeight * $itemAmount;' . "\n"
                    . '}' . "\n"
                    . 'return $result ?: 1;' . "\n",
                'sizes_callback' => '$result = [20, 20, 20];' . "\n"
                    . 'return $result;' . "\n",
                'mtypes' => [
                    ['id' => $catalogType->id, 'price_id' => $priceCol->id]
                ],
            ]);
            $CT->commit();
        }
        $cartTypes['cart'] = $CT;

        $CT = Cart_Type::importByURN('favorites');
        if (!($CT && $CT->id)) {
            $CT = new Cart_Type([
                'id' => 2,
                'name' => View_Web::i()->_('FAVORITES'),
                'urn' => 'favorites',
                'form_id' => 0,
                'no_amount' => 1,
                'mtypes' => [
                    ['id' => $catalogType->id, 'price_id' => $priceCol->id]
                ],
            ]);
            $CT->commit();
        }
        $cartTypes['favorites'] = $CT;

        $CT = Cart_Type::importByURN('compare');
        if (!($CT && $CT->id)) {
            $CT = new Cart_Type([
                'id' => 3,
                'name' => View_Web::i()->_('COMPARISON'),
                'urn' => 'compare',
                'form_id' => 0,
                'no_amount' => 1,
                'mtypes' => [
                    ['id' => $catalogType->id, 'price_id' => $priceCol->id]
                ],
            ]);
            $CT->commit();
        }
        $cartTypes['compare'] = $CT;
        return $cartTypes;
    }


    /**
     * Создадим статусы заказов
     * @return array[Order_Status] массив созданных или существующих статусов
     *                             заказов
     */
    public function createOrderStatuses()
    {
        $orderStatuses = [];
        foreach ([
            'progress' => 'IN_PROGRESS',
            'completed' => 'COMPLETED',
            'canceled' => 'CANCELED'
        ] as $key => $val) {
            $OS = Order_Status::importByURN($key);
            if (!($OS && $OS->id)) {
                $OS = new Order_Status([
                    'name' => View_Web::i()->_($val),
                    'urn' => $key
                ]);
                $OS->commit();
            }
            $orderStatuses[$key] = $OS;
        }
        return $orderStatuses;
    }


    /**
     * Создаем загрузчики
     * @param Material_Type $catalogType тип материала каталога
     * @param Page $catalog Страница каталога
     * @return [
     *             'priceloader' => PriceLoader,
     *             'imageloader' => ImageLoader
     *         ] созданные загрузчики
     */
    public function createLoaders(Material_Type $catalogType, Page $catalog)
    {
        $loaders = [];
        $IL = ImageLoader::importByURN('default');
        if (!($IL && $IL->id)) {
            $IL = new ImageLoader([
                'mtype' => $catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'ifid' => $catalogType->fields['images']->id,
                'name' => View_Web::i()->_('DEFAULT_IMAGELOADER'),
                'urn' => 'default',
                'sep_string' => '_',
                'interface_classname' => ImageloaderInterface::class,
            ]);
            $IL->commit();
        }
        $loaders['imageloader'] = $IL;

        $PL = PriceLoader::importByURN('default');
        if (!($PL && $PL->id)) {
            $PL = new PriceLoader([
                'mtype' => (int)$catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'name' => View_Web::i()->_('DEFAULT_PRICELOADER'),
                'urn' => 'default',
                'cat_id' => (int)$catalog->id,
                'interface_classname' => PriceloaderInterface::class,
            ]);
            $PL->commit();
            $i = 0;
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['article']->id,
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => 'name',
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => 'description',
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['related']->id,
                'callback' => "namespace RAAS\\CMS;\n"
                           .  "\n"
                           .  "\$y = array_filter(array_map('trim', preg_split('/[;,]/umi', \$x)), 'trim');\n"
                           .  "\$temp = [];\n"
                           .  "foreach (\$y as \$val) {\n"
                           .  "    \$sqlQuery = \"SELECT pid\n"
                           .  "                     FROM cms_data\n"
                           .  "                    WHERE fid = ?\n"
                           .  "                      AND value = ?\";\n"
                           .  "    \$sqlResult = Material::_SQL()->getvalue([\$sqlQuery, [" . (int)$catalogType->fields['article']->id . ", \$val]]);\n"
                           .  "    if (\$sqlResult) {\n"
                           .  "        \$temp[] = (int)\$sqlResult;\n"
                           .  "    }\n"
                           .  "}\n"
                           .  "return \$temp;",
                'callback_download' => "\$temp = [];\n"
                                    .  "foreach ((array)\$x as \$item) {\n"
                                    .  "    if (\$item->id) {\n"
                                    .  "        \$temp[] = \$item->article;\n"
                                    .  "    }\n"
                                    .  "}\n"
                                    .  "return implode(', ', \$temp);",
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['available']->id,
                'callback' => "if (\$x && (trim(\$x) !== '0')) {\n"
                           .  "    return (int)(bool)preg_match('/налич/umi', \$x);\n"
                           .  "}\n"
                           .  "return 0;",
                'callback_download' => "return (int)\$x ? 'в наличии' : 'под заказ';",
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['price_old']->id,
                'callback' => "\$y = (float)preg_replace('/[^\\d\\.]+/i', '', trim(\$x));\n"
                           .  "return \$y;",
                'priority' => ++$i
            ]);
            $PLC->commit();
            $PLC = new PriceLoader_Column([
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['price']->id,
                'callback' => "\$y = str_replace(',', '.', \$x);\n"
                           .  "\$y = (float)preg_replace('/[^\\d\\.]+/i', '', trim(\$y));\n"
                           .  "return \$y;",
                'priority' => ++$i
            ]);
            $PLC->commit();
        }
        $loaders['priceloader'] = $PL;
        return $loaders;
    }


    public function createPageFields()
    {
        $result = [];

        $fieldsSource = <<<'RAAS_CMS_SHOP_FIELDS_SOURCE_TMP'
namespace RAAS\CMS;

$pageId = (int)$_GET['id'];
$pagesIds = PageRecursiveCache::i()->getSelfAndChildrenIds($pageId);
$mTypeId = Material_Type::importByURN('catalog')->id;
$typesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($mTypeId);

$sqlQuery = "SELECT DISTINCT tM.id
               FROM " . Material::_tablename() . " AS tM
               JOIN cms_materials_pages_assoc AS tMPA ON tMPA.id = tM.id
              WHERE tMPA.pid IN (" . implode(", ", $pagesIds) . ")
                AND tM.pid IN (" . implode(", ", $typesIds) . ")";
$materialsIds = Material::_SQL()->getcol($sqlQuery);
if (!$materialsIds) {
    return [];
}

$sqlQuery = "SELECT DISTINCT pid
               FROM " . Material::_tablename() . "
              WHERE id IN (" . implode(", ", $materialsIds) . ")";
$realTypesIds = Material::_SQL()->getcol($sqlQuery);
$realTypesAndParentsIds = MaterialTypeRecursiveCache::i()->getSelfAndParentsIds($realTypesIds);
if (!$realTypesAndParentsIds) {
    return [];
}

$sqlQuery = "SELECT tF.id,
                    CONCAT(tF.name, ' (', tMT.name, ')') AS name
                FROM " . Material_Field::_tablename() . " AS tF
                JOIN " . Material_Type::_tablename() . " AS tMT ON tMT.id = tF.pid
               WHERE tF.classname = 'RAAS\\\\CMS\\\\Material_Type'
                 AND tF.datatype NOT IN ('image', 'file')
                 AND tF.pid IN (" . implode(", ", $realTypesAndParentsIds) . ")
            GROUP BY tF.id
            ORDER BY tF.name";
$sqlResult = Material_Field::_SQL()->get($sqlQuery);
$result = [];
foreach ($sqlResult as $sqlRow) {
    $result[$sqlRow['id']] = $sqlRow['name'];
}
return $result;

RAAS_CMS_SHOP_FIELDS_SOURCE_TMP;

        $fieldsData = [];
        foreach (['', '_list'] as $suffix) {
            foreach (['title', 'description', 'keywords'] as $key) {
                $urn = 'meta_' . $key . $suffix . '_template';
                $fieldsData[] = [
                    'name' => View_Web::i()->_(mb_strtoupper($urn)),
                    'urn' => $urn,
                    'datatype' => 'text',
                ];
            }
        }
        $fieldsData = array_merge($fieldsData, [
            [
                'name' => View_Web::i()->_('DISCOUNT_PERCENT'),
                'urn' => 'discount',
                'datatype' => 'number',
            ],
            [
                'name' => View_Web::i()->_('FILTER_PROPS'),
                'urn' => 'filter_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source_type' => 'php',
                'source' => $fieldsSource,
            ],
            [
                'name' => View_Web::i()->_('MAIN_PROPS'),
                'urn' => 'main_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source_type' => 'php',
                'source' => $fieldsSource,
            ],
            [
                'name' => View_Web::i()->_('ARTICLE_PROPS'),
                'urn' => 'article_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source_type' => 'php',
                'source' => $fieldsSource,
            ],
        ]);
        foreach ($fieldsData as $row) {
            $field = Page_Field::importByURN($row['urn']);
            if (!($field && $field->id)) {
                $field = new Page_Field($row);
                $field->commit();
            }
            $result[$row['urn']] = $field;
        }

        return $result;
    }


    /**
     * Создаем страницу корзины
     * @param Cart_Type $cartType Тип корзины
     * @param Page $ajax Страница AJAX
     * @param Form $form Форма заказа
     * @return Page Созданная или существующая страница корзины
     */
    public function createCart(Cart_Type $cartType, Page $ajax, Form $form)
    {
        // @deprecated 2023-03-05, AVS: создание AJAX-страниц для корзин устарело, используется X-RAAS-Block-Id
        // $temp = Page::getSet([
        //     'where' => ["pid = " . (int)$ajax->id, "urn = 'cart'"]
        // ]);
        // if ($temp) {
        //     $ajaxCart = $temp[0];
        //     $ajaxCart->trust();
        // } else {
        //     $ajaxCart = $this->createPage(
        //         [
        //             'name' => View_Web::i()->_('CART'),
        //             'urn' => 'cart',
        //             'template' => 0,
        //             'cache' => 0,
        //             'response_code' => 200,
        //             'mime' => 'application/json',
        //         ],
        //         $ajax
        //     );
        //     $ajaxBlock = $this->createBlock(new Block_Cart([
        //         'cart_type' => (int)$cartType->id,
        //         'params' => 'cdek[authLogin]='
        //             . '&cdek[secure]='
        //             . '&cdek[senderCityId]=250'
        //             . '&cdek[pickupTariff]=136'
        //             . '&cdek[deliveryTariff]=137'
        //             . '&russianpost[login]='
        //             . '&russianpost[password]='
        //             . '&russianpost[token]='
        //             . '&minOrderSum=0',
        //     ]), '', 'cart_interface', 'cart', $ajaxCart);
        // }


        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'cart'"]
        ]);
        if ($temp) {
            $cart = $temp[0];
            $cart->trust();
        } else {
            $cart = $this->createPage([
                'name' => View_Web::i()->_('CART'),
                'urn' => 'cart',
                'cache' => 0,
                'response_code' => 200
            ], $this->Site);
            $this->createBlock(new Block_Cart([
                'cart_type' => (int)$cartType->id,
                'params' => 'cdek[authLogin]='
                    . '&cdek[secure]='
                    . '&cdek[senderCityId]=250'
                    . '&cdek[pickupTariff]=136'
                    . '&cdek[deliveryTariff]=137'
                    . '&russianpost[senderIndex]='
                    . '&russianpost[pickupTariff]=23030'
                    . '&russianpost[deliveryTariff]=23030'
                    . '&russianpost[services][]=41'
                    . '&russianpost[services][]=42'
                    . '&minOrderSum=0',
            ]), 'content', CartInterface::class, 'cart', $cart);

            $this->createBlock(
                new Block_PHP(),
                'cart',
                '',
                'cart_main',
                $this->Site,
                true
            );
        }

        DeliveryTemplate::spawn(
            View_Web::i()->_('RECEIVING_METHODS'),
            'delivery',
            $this
        )->create();
        PaymentTypesTemplate::spawn(
            View_Web::i()->_('PAYMENT_TYPES'),
            'payment',
            $this
        )->create();
        DiscountTemplate::spawn(
            View_Web::i()->_('PROMO_CODES'),
            'discount',
            $this
        )->create();

        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $paymentMaterialType = Material_Type::importByURN('payment');
        $deliveryField = $form->fields['delivery'];
        $deliveryField->source = (int)$deliveryMaterialType->id;
        $deliveryField->commit();
        $paymentField = $form->fields['payment'];
        $paymentField->source = (int)$paymentMaterialType->id;
        $paymentField->commit();
        return $cart;
    }


    /**
     * Создаем страницу избранного
     * @param Cart_Type $cartType Тип корзины
     * @param Page $ajax Страница AJAX
     * @return Page Созданная или существующая страница избранного
     */
    public function createFavorites(Cart_Type $cartType, Page $ajax)
    {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'favorites'"]
        ]);
        if ($temp) {
            $favorites = $temp[0];
            $favorites->trust();
        } else {
            $favorites = $this->createPage(
                [
                    'name' => View_Web::i()->_('FAVORITES'),
                    'urn' => 'favorites',
                    'cache' => 0,
                    'response_code' => 200
                ],
                $this->Site
            );
            $B = new Block_Cart([
                'name' => View_Web::i()->_('FAVORITES'),
                'cart_type' => (int)$cartType->id
            ]);
            $this->createBlock(
                $B,
                'content',
                CompareInterface::class,
                'favorites',
                $favorites
            );

            $B = new Block_PHP();
            $this->createBlock(
                $B,
                'cart',
                '',
                'favorites_main',
                $this->Site,
                true
            );
        }

        $temp = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'favorites'"]
        ]);
        if ($temp) {
            $ajaxFavorites = $temp[0];
            $ajaxFavorites->trust();
        } else {
            $ajaxFavorites = $this->createPage(
                [
                    'name' => View_Web::i()->_('FAVORITES'),
                    'urn' => 'favorites',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200,
                    'mime' => 'application/json',
                ],
                $ajax
            );
            $B = new Block_Cart([
                'name' => View_Web::i()->_('FAVORITES'),
                'cart_type' => (int)$cartType->id,
            ]);
            $this->createBlock(
                $B,
                '',
                CompareInterface::class,
                'favorites',
                $ajaxFavorites
            );
        }
        return $favorites;
    }


    /**
     * Создаем страницу сравнения
     * @param Cart_Type $cartType Тип корзины
     * @param Page $ajax Страница AJAX
     * @return Page Созданная или существующая страница избранного
     */
    public function createCompare(Cart_Type $cartType, Page $ajax)
    {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'compare'"]
        ]);
        if ($temp) {
            $compare = $temp[0];
            $compare->trust();
        } else {
            $compare = $this->createPage(
                [
                    'name' => View_Web::i()->_('COMPARISON'),
                    'urn' => 'compare',
                    'cache' => 0,
                    'response_code' => 200
                ],
                $this->Site
            );
            $B = new Block_Cart([
                'name' => View_Web::i()->_('COMPARISON'),
                'cart_type' => (int)$cartType->id,
            ]);
            $this->createBlock(
                $B,
                'content',
                CompareInterface::class,
                'compare',
                $compare
            );

            $B = new Block_PHP();
            $this->createBlock(
                $B,
                'cart',
                '',
                'compare_main',
                $this->Site,
                true
            );
        }

        $temp = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'compare'"]
        ]);
        if ($temp) {
            $ajaxCompare = $temp[0];
            $ajaxCompare->trust();
        } else {
            $ajaxCompare = $this->createPage(
                [
                    'name' => View_Web::i()->_('COMPARISON'),
                    'urn' => 'compare',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200,
                    'mime' => 'application/json',
                ],
                $ajax
            );
            $B = new Block_Cart([
                'name' => View_Web::i()->_('COMPARISON'),
                'cart_type' => (int)$cartType->id,
            ]);
            $this->createBlock(
                $B,
                '',
                CompareInterface::class,
                'compare',
                $ajaxCompare
            );
        }
        return $compare;
    }


    /**
     * Создаем страницу доставки/оплаты
     * @return Page Созданная или существующая страница
     */
    public function createDelivery()
    {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'delivery'"]
        ]);
        if ($temp) {
            $delivery = $temp[0];
            $delivery->trust();
        } else {
            $delivery = $this->createPage(
                [
                    'name' => View_Web::i()->_('DELIVERY_AND_PAYMENT'),
                    'urn' => 'delivery',
                ],
                $this->Site,
                true
            );
        }
        return $delivery;
    }


    /**
     * Создает фильтр
     * @param Block_Material $catalogBlock Блок каталога
     */
    public function createFilter(Block_Material $catalogBlock)
    {
        $pagesSet = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'ajax'"]
        ]);
        $ajax = array_shift($pagesSet);
        $catalogFilterPages = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'catalog_filter'"]
        ]);
        if ($catalogFilterPages) {
            $catalogFilterPage = $catalogFilterPages[0];
            $catalogFilterPage->trust();
        } else {
            $catalogFilterPage = $this->createPage(
                [
                    'name' => View_Web::i()->_('CATALOG_FILTER'),
                    'urn' => 'catalog_filter',
                    'template' => 0,
                    'cache' => 1,
                    'mime' => 'application/json',
                    'response_code' => 200
                ],
                $ajax
            );
            $this->createBlock(
                new Block_PHP(),
                '',
                null,
                'catalog_filter',
                $catalogFilterPage
            );
        }

        $catalogPages = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'catalog'"]
        ]);
        if ($catalogPages) {
            $catalogPage = $catalogPages[0];
            $catalogPage->trust();
            $this->createBlock(
                new Block_PHP([
                    'vis_material' => Block::BYMATERIAL_WITHOUT,
                    'params' => 'catalogBlockId=' . (int)$catalogBlock->id,
                ]),
                'left',
                null,
                'catalog_filter',
                $catalogPage,
                true
            );
        }
    }


    /**
     * Выравниваем левые блоки
     */
    public function adjustBlocks()
    {
        $sqlQuery = "SELECT COUNT(*)
                       FROM " . Block::_tablename()
                  . " WHERE location = 'left'";
        $c = $this->SQL->getvalue($sqlQuery);
        $this->leftMenuBlock->swap(-$c, $this->Site);
    }


    /**
     * Создать страницу Яндекс-Маркета
     * @param Material_Type $catalogType Тип материала каталога
     * @param Page $catalog Страница каталога
     * @return Page Созданная или существующая страница
     */
    public function createYandexMarket(
        Material_Type $catalogType,
        Page $catalog
    ) {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'yml'"]
        ]);
        if ($temp) {
            $yml = $temp[0];
            $yml->trust();
        } else {
            $yml = $this->createPage(
                [
                    'name' => View_Web::i()->_('YANDEX_MARKET'),
                    'urn' => 'yml',
                    'mime' => 'application/xml',
                    'template' => 0,
                    'response_code' => 200
                ],
                $this->Site
            );

            $B = new Block_YML([
                'agency' => 'Volume Networks',
                'email' => 'info@volumnet.ru',
                'default_currency' => 'RUB',
                'meta_cats' => $catalog->selfAndChildrenIds,
                'local_delivery_cost' => 0,
            ]);
            $this->createBlock($B, '', YMLInterface::class, null, $yml);
            $B->addType(
                $catalogType,
                '',
                [
                    'available' => [
                        'field_id' => (int)$catalogType->fields['available']->id
                    ],
                    'price' => [
                        'field_id' => (int)$catalogType->fields['price']->id
                    ],
                    'oldprice' => [
                        'field_id' => (int)$catalogType->fields['price_old']->id
                    ],
                    'currencyId' => [
                        'field_static_value' => 'RUB'
                    ],
                    'picture' => [
                        'field_id' => (int)$catalogType->fields['images']->id
                    ],
                    'pickup' => [
                        'field_static_value' => 1
                    ],
                    'delivery' => [
                        'field_static_value' => 1
                    ],
                    'vendorCode' => [
                        'field_id' => (int)$catalogType->fields['article']->id
                    ],
                    'name' => [
                        'field_id' => 'name'
                    ],
                    'description' => [
                        'field_id' => 'description'
                    ],
                    'rec' => (int)$catalogType->fields['related']->id,
                ],
                [
                    [
                        'param_name' => 'Спецпредложение',
                        'field_id' => (int)$catalogType->fields['spec']->id,
                        'field_callback' => "return \$x ? 'true' : 'false';"
                    ]
                ]
            );
        }
        return $yml;
    }


    /**
     * Создание интернет-магазина
     */
    public function createIShop()
    {
        ini_set('max_execution_time', 3600);
        $this->createPageFields();
        $interfaces = $this->checkStdInterfaces();
        $widgets = $this->createWidgets();
        $forms = $this->createForms(
            [
                [
                    'name' => View_Web::i()->_('ORDER_FORM'),
                    'urn' => 'order',
                    'interface_id' => (int)$interfaces['__raas_shop_order_notify']->id,
                    'fields' => [
                        [
                            'name' => View_Web::i()->_('ESTIMATED_WEIGHT'),
                            'urn' => 'weight',
                            'vis' => false,
                            'required' => true,
                            'datatype' => 'number',
                        ],
                        [
                            'name' => View_Web::i()->_('RECEIVING_METHOD'),
                            'urn' => 'delivery',
                            'required' => true,
                            'datatype' => 'material',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('PAYMENT_METHOD'),
                            'urn' => 'payment',
                            'required' => true,
                            'datatype' => 'material',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('PROMO_CODE'),
                            'urn' => 'promo',
                            'required' => false,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('POSTAL_CODE'),
                            'urn' => 'post_code',
                            'required' => true,
                            'datatype' => 'text',
                            'pattern' => '^\d{6}$',
                        ],
                        [
                            'name' => View_Web::i()->_('REGION'),
                            'urn' => 'region',
                            'required' => true,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('CITY'),
                            'urn' => 'city',
                            'required' => true,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('STREET'),
                            'urn' => 'street',
                            'required' => true,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('HOUSE'),
                            'urn' => 'house',
                            'required' => true,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('APARTMENT'),
                            'urn' => 'apartment',
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('PICKUP_POINT'),
                            'urn' => 'pickup_point',
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('PICKUP_POINT_ID'),
                            'urn' => 'pickup_point_id',
                            'vis' => false,
                            'datatype' => 'text',
                        ],

                        [
                            'name' => View_Web::i()->_('LAST_NAME'),
                            'urn' => 'last_name',
                            'required' => true,
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('FIRST_NAME'),
                            'urn' => 'first_name',
                            'required' => true,
                            'datatype' => 'text',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('SECOND_NAME'),
                            'urn' => 'second_name',
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('PHONE'),
                            'urn' => 'phone',
                            'datatype' => 'tel',
                            'required' => true,
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('EMAIL'),
                            'urn' => 'email',
                            'datatype' => 'email',
                        ],
                        [
                            'name' => View_Web::i()->_('ORDER_COMMENT'),
                            'urn' => 'description',
                            'datatype' => 'textarea',
                        ],
                        [
                            'name' => View_Web::i()->_('TRACK_NUMBER'),
                            'vis' => false,
                            'urn' => 'barcode',
                            'datatype' => 'text',
                        ],
                        [
                            'name' => View_Web::i()->_('AGREE_PRIVACY_POLICY'),
                            'urn' => 'agree',
                            'required' => true,
                            'datatype' => 'checkbox',
                        ],
                    ]
                ]
            ]
        );
        BrandsTemplate::spawn(View_Web::i()->_('BRANDS'), 'brands', $this)
            ->create();
        $materialTemplate = CatalogTemplate::spawn(
            View_Web::i()->_('CATALOG'),
            'catalog',
            $this
        );
        $catalogType = $materialTemplate->materialType;
        $catalog = $materialTemplate->create();

        $catalog->fields['main_props']->addValue((int)$catalogType->fields['article']->id);
        $catalog->fields['main_props']->addValue((int)$catalogType->fields['brand']->id);
        $catalog->fields['main_props']->addValue((int)$catalogType->fields['length']->id);
        $catalog->fields['main_props']->addValue((int)$catalogType->fields['height']->id);
        $catalog->fields['main_props']->addValue((int)$catalogType->fields['width']->id);
        $catalog->fields['main_props']->addValue((int)$catalogType->fields['weight']->id);

        GoodsCommentsTemplate::spawn(
            View_Web::i()->_('GOODS_REVIEWS'),
            'goods_comments',
            $this,
            $materialTemplate->catalogBlock
        )->create();

        GoodsFAQTemplate::spawn(
            View_Web::i()->_('GOODS_FAQ'),
            'goods_faq',
            $this,
            $materialTemplate->catalogBlock
        )->create();

        $pagesSet = Page::getSet([
            'where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id
        ]);
        $ajax = array_shift($pagesSet);
        $menus = $this->createMenus([
            [
                'pageId' => (int)$catalog->id,
                'urn' => 'left',
                'inherit' => 10,
                'name' => View_Web::i()->_('LEFT_MENU'),
                'realize' => false,
                'addMainPageLink' => false,
                'blockLocation' => 'left',
                'fullMenu' => true,
                'blockPage' => $catalog,
                'inheritBlock' => true,
            ],
            [
                'pageId' => (int)$catalog->id,
                'urn' => 'catalog',
                'inherit' => 10,
                'name' => View_Web::i()->_('CATALOG'),
                'realize' => false,
                'addMainPageLink' => false,
                'blockLocation' => 'menu_catalog',
                'fullMenu' => true,
                'blockPage' => $this->Site,
                'inheritBlock' => true,
            ],
            [
                'pageId' => (int)$catalog->id,
                'urn' => 'popular_cats',
                'inherit' => 1,
                'name' => View_Web::i()->_('POPULAR_CATEGORIES'),
                'realize' => false,
                'addMainPageLink' => false,
                'blockLocation' => 'content4',
                'fullMenu' => true,
                'blockPage' => $this->Site,
                'inheritBlock' => false,
                'widget_urn' => 'popular_cats',
            ],
        ]);

        $sqlQuery = "SELECT tB.id
                       FROM " . Block::_tablename() . " AS tB
                       JOIN cms_blocks_menu AS tBM
                       JOIN cms_blocks_pages_assoc AS tBPA ON tBPA.block_id = tB.id
                      WHERE tBM.menu = ?
                        AND tB.location = ?
                        AND tBPA.page_id = ?
                   ORDER BY tB.id DESC
                      LIMIT 1";
        $lastBlockId = $this->SQL->getvalue([
            $sqlQuery,
            [(int)$menus['left']->id, 'menu_left', (int)$catalog->id]
        ]);
        $this->leftMenuBlock = new Block_Menu((int)$lastBlockId);
        $this->leftMenuBlock->name = View_Web::i()->_('CATALOG');
        $this->leftMenuBlock->commit();


        $cartTypes = $this->createCartTypes(
            $catalogType,
            $catalogType->fields['price'],
            $forms['order']
        );
        $orderStatuses = $this->createOrderStatuses();
        $loaders = $this->createLoaders($catalogType, $catalog);
        $cart = $this->createCart($cartTypes['cart'], $ajax, $forms['order']);


        $favorites = $this->createFavorites($cartTypes['favorites'], $ajax);
        $compare = $this->createCompare($cartTypes['compare'], $ajax);
        $delivery = $this->createDelivery();
        $this->createFilter($materialTemplate->catalogBlock);
        $this->adjustBlocks();
        $yml = $this->createYandexMarket($catalogType, $catalog);
        $this->createCron();
    }


    /**
     * Создает cron-задачи
     */
    public function createCron()
    {
        $recalculatePriceTask = new Crontab([
            'name' => View_Web::i()->_('RECALCULATING_PRICES'),
            'vis' => 1,
            'once' => 0,
            'minutes' => '*',
            'hours' => '*',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => RecalculatePriceCommand::class,
            'args' => '[]'
        ]);
        $recalculatePriceTask->commit();
        $updateCatalogFilterTask = new Crontab([
            'name' => View_Web::i()->_('UPDATING_CATALOG_FILTER_CACHE'),
            'vis' => 1,
            'once' => 0,
            'minutes' => '*',
            'hours' => '*',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => UpdateCatalogFilterCommand::class,
            'args' => '[]'
        ]);
        $updateCatalogFilterTask->commit();
        $updatePropsCache = new Crontab([
            'name' => View_Web::i()->_('UPDATING_PROPS_CACHE'),
            'vis' => 1,
            'once' => 0,
            'minutes' => '*',
            'hours' => '*',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => UpdatePropsCacheCommand::class,
            'args' => '[]'
        ]);
        $updatePropsCache->commit();
        $updateYMLTask = new Crontab([
            'name' => View_Web::i()->_('UPDATING_YANDEX_MARKET'),
            'vis' => 1,
            'once' => 0,
            'minutes' => '0',
            'hours' => '0',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => UpdateYMLCommand::class,
            'args' => '[]'
        ]);
        $updateYMLTask->commit();
        $updateRatingTask = new Crontab([
            'name' => View_Web::i()->_('UPDATING_CATALOG_RATING'),
            'vis' => 1,
            'once' => 0,
            'minutes' => '*',
            'hours' => '*',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => UpdateRatingCommand::class,
            'args' => '[]'
        ]);
        $updateRatingTask->commit();
        $sdekPVZImportTask = new Crontab([
            'name' => View_Web::i()->_('UPDATING_PICKUP_POINTS'),
            'vis' => 0,
            'once' => 0,
            'minutes' => '0',
            'hours' => '0',
            'days' => '*',
            'weekdays' => '*',
            'command_classname' => UpdatePickupPointsCommand::class,
            'args' => '[]'
        ]);
        $sdekPVZImportTask->commit();
    }
}
