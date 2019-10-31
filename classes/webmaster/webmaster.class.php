<?php
namespace RAAS\CMS\Shop;

use RAAS\Attachment;
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
            case 'nextImage':
            case 'nextText':
            case 'nextUser':
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
        $interfacesData = [
            '__raas_shop_cart_interface' => [
                'name' => 'CART_STANDARD_INTERFACE',
                'filename' => 'cart_interface',
            ],
            '__raas_shop_order_notify' => [
                'name' => 'ORDER_STANDARD_NOTIFICATION',
                'filename' => 'form_notification',
            ],
            '__raas_shop_imageloader_interface' => [
                'name' => 'IMAGELOADER_STANDARD_INTERFACE',
                'filename' => 'imageloader_interface',
            ],
            '__raas_shop_priceloader_interface' => [
                'name' => 'PRICELOADER_STANDARD_INTERFACE',
                'filename' => 'priceloader_interface',
            ],
            '__raas_shop_yml_interface' => [
                'name' => 'YML_STANDARD_INTERFACE',
                'filename' => 'yml_interface',
            ],
            '__raas_robokassa_interface' => [
                'name' => 'ROBOKASSA_INTERFACE',
                'filename' => 'robokassa_interface',
            ],
            '__raas_my_orders_interface' => [
                'name' => 'MY_ORDERS_STANDARD_INTERFACE',
                'filename' => 'my_orders_interface',
            ],
        ];
        foreach ($interfacesData as $interfaceURN => $interfaceData) {
            $interfaces[$interfaceURN] = $this->checkSnippet(
                $this->interfacesFolder,
                $interfaceURN,
                $interfaceData['name'],
                file_get_contents(
                    Module::i()->resourcesDir .
                    '/interfaces/' . $interfaceData['filename'] . '.php'
                )
            );
        }
        $interfaces['catalog_interface'] = $this->checkSnippet(
            $this->interfacesFolder,
            'catalog_interface',
            'CATALOG_INTERFACE',
            file_get_contents(
                Module::i()->resourcesDir . '/interfaces/catalog_interface.php'
            ),
            false
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
            'epay/robokassa' => View_Web::i()->_('ROBOKASSA'),
            'materials/catalog/catalog_item' => View_Web::i()->_('CATALOG_ITEM'),
            'materials/catalog/catalog_category' => View_Web::i()->_('CATEGORY_INC'),
            'materials/catalog/catalog' => View_Web::i()->_('CATALOG'),
            'materials/catalog/catalog_filter' => View_Web::i()->_('CATALOG_FILTER'),
            'cart/cart_main' => View_Web::i()->_('CART_MAIN'),
            'cart/favorites_main' => View_Web::i()->_('FAVORITES_MAIN'),
            'materials/catalog/spec' => View_Web::i()->_('SPECIAL_OFFER'),
            'cart/my_orders' => View_Web::i()->_('MY_ORDERS'),
        ];
        foreach ($widgetsData as $url => $name) {
            $urn = explode('/', $url);
            $urn = $urn[count($urn) - 1];
            $widget = Snippet::importByURN($urn);
            if (!$widget->id) {
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
     * Создаем тип материалов
     * @return Material_Type Созданный или существующий тип материала
     */
    public function createMaterialType()
    {
        $MT = Material_Type::importByURN('catalog');
        if (!$MT->id) {
            $MT = new Material_Type([
                'name' => $this->view->_('CATALOG'),
                'urn' => 'catalog',
                'global_type' => 0,
            ]);
            $MT->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('ARTICLE'),
                'urn' => 'article',
                'datatype' => 'text',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('PRICE'),
                'urn' => 'price',
                'datatype' => 'number',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('IMAGE'),
                'multiple' => 1,
                'urn' => 'images',
                'datatype' => 'image',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('VIDEOS'),
                'multiple' => 1,
                'urn' => 'videos',
                'datatype' => 'text',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('FILES'),
                'multiple' => 1,
                'urn' => 'files',
                'datatype' => 'file',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('SPECIAL_OFFER'),
                'urn' => 'spec',
                'datatype' => 'checkbox',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('AVAILABLE'),
                'urn' => 'available',
                'defval' => 1,
                'datatype' => 'checkbox',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('MINIMAL_AMOUNT'),
                'urn' => 'min',
                'defval' => 1,
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('CART_STEP'),
                'urn' => 'step',
                'defval' => 1,
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('OLD_PRICE'),
                'urn' => 'price_old',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'name' => $this->view->_('RELATED_GOODS'),
                'multiple' => 1,
                'urn' => 'related',
                'datatype' => 'material',
                'source' => $MT->id,
            ]);
            $F->commit();
        }
        return $MT;
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
        if (!$CT->id) {
            $CT = new Cart_Type([
                'name' => $this->view->_('CART'),
                'urn' => 'cart',
                'form_id' => (int)$orderForm->id,
                'no_amount' => 0,
                'mtypes' => [
                    ['id' => $catalogType->id, 'price_id' => $priceCol->id]
                ],
            ]);
            $CT->commit();
        }
        $cartTypes['cart'] = $CT;

        $CT = Cart_Type::importByURN('favorites');
        if (!$CT->id) {
            $CT = new Cart_Type([
                'name' => $this->view->_('FAVORITES'),
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
            if (!$OS->id) {
                $OS = new Order_Status([
                    'name' => $this->view->_($val),
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
        if (!$IL->id) {
            $IL = new ImageLoader([
                'mtype' => $catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'ifid' => $catalogType->fields['images']->id,
                'name' => $this->view->_('DEFAULT_IMAGELOADER'),
                'urn' => 'default',
                'sep_string' => '_',
                'interface_id' => (int)Snippet::importByURN(
                    '__raas_shop_imageloader_interface'
                )->id,
            ]);
            $IL->commit();
        }
        $loaders['imageloader'] = $IL;

        $PL = PriceLoader::importByURN('default');
        if (!$PL->id) {
            $PL = new PriceLoader([
                'mtype' => (int)$catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'name' => $this->view->_('DEFAULT_PRICELOADER'),
                'urn' => 'default',
                'cat_id' => (int)$catalog->id,
                'interface_id' => (int)Snippet::importByURN(
                    '__raas_shop_priceloader_interface'
                )->id,
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
                'callback' => "\$y = str_replace(',', '.', \$x);\n"
                           .  "\$y = (float)preg_replace('/[^\\d\\.]+/i', '', trim(\$x));\n"
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


    /**
     * Создадим каталог
     * @param Material_Type $catalogType Тип материала каталога
     * @return Page Созданная или существующая страница
     */
    public function createCatalog(Material_Type $catalogType)
    {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'catalog'"]
        ]);
        if ($temp) {
            $catalog = $temp[0];
        } else {
            $categories = [];
            $catalog = $this->createPage(
                ['name' => $this->view->_('CATALOG'), 'urn' => 'catalog'],
                $this->Site
            );
            for ($i = 1; $i <= 3; $i++) {
                $categories[$i] = $this->createPage(
                    [
                        'name' => $this->view->_('CATEGORY') . ' ' . $i,
                        'urn' => 'category' . $i
                    ],
                    $catalog
                );
                if ($i == 1) {
                    for ($j = 1; $j <= 3; $j++) {
                        $categories[$i . $j] = $this->createPage(
                            [
                                'name' => $this->view->_('CATEGORY')
                                       .  ' ' . $i . $j,
                                'urn' => 'category' . $i . $j
                            ],
                            $categories[$i]
                        );
                        if ($j == 1) {
                            for ($k = 1; $k <= 3; $k++) {
                                $categories[$i . $j . $k] = $this->createPage(
                                    [
                                        'name' => $this->view->_('CATEGORY')
                                               .  ' ' . $i . $j . $k,
                                        'urn' => 'category' . $i . $j . $k
                                    ],
                                    $categories[$i . $j]
                                );
                            }
                        }
                    }
                }
            }
            foreach ($categories as $category) {
                $att = Attachment::createFromFile(
                    $this->nextImage,
                    $category->fields['image']
                );
                $category->fields['image']->addValue(json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => (int)$att->id
                ]));
            }
            $goods = [];
            for ($i = 0; $i < 10; $i++) {
                $temp = $this->nextText;
                $Item = new Material([
                    'pid' => (int)$catalogType->id,
                    'vis' => 1,
                    'name' => $this->view->_('GOODS_ITEM') . ' ' . ($i + 1),
                    'description' => $temp['text'],
                    'priority' => ($i + 1) * 10,
                    'sitemaps_priority' => 0.5
                ]);
                $cats = [];
                $Item->cats = [
                    $this->Site->id,
                    $categories[111]->id, $categories[112]->id, $categories[113]->id,
                    $categories[12]->id, $categories[13]->id,
                    $categories[2]->id, $categories[3]->id,
                ];
                $Item->commit();
                $Item->fields['article']->addValue(dechex(crc32($i)));
                $Item->fields['price']->addValue($price = rand(100, 100000));
                $Item->fields['price_old']->addValue(
                    ($price % 2) ? (int)($price * (100 + rand(5, 25)) / 100) : 0
                );
                $Item->fields['videos']->addValue(
                    'https://www.youtube.com/watch?v=YVgc2PQd_bo'
                );
                $Item->fields['videos']->addValue(
                    'https://www.youtube.com/watch?v=YVgc2PQd_bo'
                );
                $Item->fields['spec']->addValue(1);
                $Item->fields['available']->addValue((int)(bool)($i % 4));
                $Item->fields['min']->addValue($i % 4 ? 1 : 2);
                $Item->fields['step']->addValue($i % 4 ? 1 : 2);
                foreach (['test.doc', 'test.pdf'] as $val) {
                    $att = Attachment::createFromFile(
                        Module::i()->resourcesDir . '/' . $val,
                        $Item->fields['files']
                    );

                    $Item->fields['files']->addValue(json_encode([
                        'vis' => 1,
                        'name' => '',
                        'description' => '',
                        'attachment' => (int)$att->id
                    ]));
                }

                for ($j = 0; $j < (!$i ? 4 : 1); $j++) {
                    $att = Attachment::createFromFile(
                        $this->nextImage,
                        $category->fields['image']
                    );
                    $Item->fields['images']->addValue(json_encode([
                        'vis' => 1,
                        'name' => '',
                        'description' => '',
                        'attachment' => (int)$att->id
                    ]));
                }
                $goods[] = $Item;
            }
            for ($i = 0; $i < 10; $i++) {
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
            }

            $B = new Block_Material([
                'material_type' => (int)$catalogType->id,
                'nat' => 1,
                'pages_var_name' => 'page',
                'rows_per_page' => 20,
                'sort_field_default' => (int)$catalogType->fields['price']->id,
                'sort_order_default' => 'asc',
            ]);
            $catalogBlock = $this->createBlock(
                $B,
                'content',
                'catalog_interface',
                'catalog',
                $catalog,
                true
            );

            $B = new Block_PHP();
            $specBlock = $this->createBlock(
                $B,
                'content',
                null,
                'spec',
                $this->Site,
                false
            );
        }
        return $catalog;
    }


    /**
     * Создаем страницу корзины
     * @param Cart_Type $cartType Тип корзины
     * @param Page $ajax Страница AJAX
     * @return Page Созданная или существующая страница корзины
     */
    public function createCart(Cart_Type $cartType, Page $ajax)
    {
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'cart'"]
        ]);
        if ($temp) {
            $cart = $temp[0];
        } else {
            $cart = $this->createPage(
                [
                    'name' => $this->view->_('CART'),
                    'urn' => 'cart',
                    'cache' => 0,
                    'response_code' => 200
                ],
                $this->Site
            );
            $B = new Block_Cart(['cart_type' => (int)$cartType->id]);
            $this->createBlock(
                $B,
                'content',
                '__raas_shop_cart_interface',
                'cart',
                $cart
            );

            $B = new Block_PHP();
            $this->createBlock(
                $B,
                'menu_user',
                '',
                'cart_main',
                $this->Site,
                true
            );
        }

        $temp = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'cart'"]
        ]);
        if ($temp) {
            $ajaxCart = $temp[0];
        } else {
            $ajaxCart = $this->createPage(
                [
                    'name' => $this->view->_('CART'),
                    'urn' => 'cart',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200
                ],
                $ajax
            );
            $B = new Block_Cart(['cart_type' => (int)$cartType->id]);
            $this->createBlock(
                $B,
                '',
                '__raas_shop_cart_interface',
                'cart',
                $ajaxCart
            );
        }
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
        } else {
            $favorites = $this->createPage(
                [
                    'name' => $this->view->_('FAVORITES'),
                    'urn' => 'favorites',
                    'cache' => 0,
                    'response_code' => 200
                ],
                $this->Site
            );
            $B = new Block_Cart(['cart_type' => (int)$cartType->id]);
            $this->createBlock(
                $B,
                'content',
                '__raas_shop_cart_interface',
                'favorites',
                $favorites
            );

            $B = new Block_PHP();
            $this->createBlock(
                $B,
                'menu_user',
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
        } else {
            $ajaxFavorites = $this->createPage(
                [
                    'name' => $this->view->_('FAVORITES'),
                    'urn' => 'favorites',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200
                ],
                $ajax
            );
            $B = new Block_Cart(['cart_type' => (int)$cartType->id]);
            $this->createBlock(
                $B,
                '',
                '__raas_shop_cart_interface',
                'cart',
                $ajaxFavorites
            );
        }
        return $favorites;
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
        } else {
            $yml = $this->createPage(
                [
                    'name' => $this->view->_('YANDEX_MARKET'),
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
            $this->createBlock($B, '', '__raas_shop_yml_interface', null, $yml);
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
        $interfaces = $this->checkStdInterfaces();
        $widgets = $this->createWidgets();
        $forms = $this->createForms(
            [
                [
                    'name' => $this->view->_('ORDER_FORM'),
                    'urn' => 'order',
                    'interface_id' => (int)$interfaces['__raas_shop_order_notify']->id,
                    'fields' => [
                        [
                            'name' => $this->view->_('YOUR_NAME'),
                            'urn' => 'full_name',
                            'required' => true,
                            'datatype' => 'text',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => $this->view->_('PHONE'),
                            'urn' => 'phone',
                            'datatype' => 'text',
                            'required' => true,
                            'show_in_table' => true,
                        ],
                        [
                            'name' => $this->view->_('EMAIL'),
                            'urn' => 'email',
                            'datatype' => 'text',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => $this->view->_('ORDER_COMMENT'),
                            'urn' => 'description',
                            'datatype' => 'textarea',
                            'show_in_table' => 0,
                        ],
                        [
                            'name' => $this->view->_('AGREE_PRIVACY_POLICY'),
                            'urn' => 'agree',
                            'required' => true,
                            'datatype' => 'checkbox',
                        ],
                    ]
                ]
            ]
        );
        $catalogType = $this->createMaterialType();
        $ajax = array_shift(Page::getSet([
            'where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id
        ]));
        $catalog = $this->createCatalog($catalogType);
        $menus = $this->createMenus([[
            'pageId' => (int)$catalog->id,
            'urn' => 'left',
            'inherit' => 10,
            'name' => $this->view->_('LEFT_MENU'),
            'realize' => false,
            'addMainPageLink' => false,
            'blockLocation' => 'left',
            'fullMenu' => true,
            'blockPage' => $catalog,
            'inheritBlock' => true,
        ]]);
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


        $cartTypes = $this->createCartTypes(
            $catalogType,
            $catalogType->fields['price'],
            $forms['order']
        );
        $orderStatuses = $this->createOrderStatuses();
        $loaders = $this->createLoaders($catalogType, $catalog);
        $cart = $this->createCart($cartTypes['cart'], $ajax);
        $favorites = $this->createFavorites($cartTypes['favorites'], $ajax);
        $this->adjustBlocks();
        $yml = $this->createYandexMarket($catalogType, $catalog);
    }
}
