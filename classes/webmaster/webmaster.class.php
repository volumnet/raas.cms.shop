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
        $interfacesData = [
            '__raas_shop_cart_interface' => [
                'name' => 'CART_STANDARD_INTERFACE',
                'filename' => 'cart_interface',
            ],
            '__raas_shop_compare_interface' => [
                'name' => 'COMPARE_STANDARD_INTERFACE',
                'filename' => 'compare_interface',
            ],
            '__raas_shop_catalog_interface' => [
                'name' => 'CATALOG_STANDARD_INTERFACE',
                'filename' => 'catalog_interface',
            ],
            '__raas_shop_spec_interface' => [
                'name' => 'SPEC_STANDARD_INTERFACE',
                'filename' => 'spec_interface',
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
            'cart/order' => View_Web::i()->_('VIEW_ORDER'),
            'epay/robokassa' => View_Web::i()->_('ROBOKASSA'),
            'materials/comments/goods_comments' => View_Web::i()->_('GOODS_COMMENTS'),
            'materials/comments/goods_comments_form' => View_Web::i()->_('GOODS_COMMENTS_FORM'),
            'materials/comments/rating' => View_Web::i()->_('RATING'),
            'cart/cart_main' => View_Web::i()->_('CART_MAIN'),
            'cart/favorites_main' => View_Web::i()->_('FAVORITES_MAIN'),
            'cart/compare_main' => View_Web::i()->_('COMPARISON_MAIN'),
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
                'id' => 1,
                'name' => View_Web::i()->_('CART'),
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
        if (!$CT->id) {
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
            if (!$OS->id) {
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
        if (!$IL->id) {
            $IL = new ImageLoader([
                'mtype' => $catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'ifid' => $catalogType->fields['images']->id,
                'name' => View_Web::i()->_('DEFAULT_IMAGELOADER'),
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
                'name' => View_Web::i()->_('DEFAULT_PRICELOADER'),
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
                'name' => View_Web::i()->_('DISCOUNT'),
                'urn' => 'discount',
                'datatype' => 'text',
            ],
            [
                'name' => View_Web::i()->_('FILTER_PROPS'),
                'urn' => 'filter_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source' => $fieldsSource,
            ],
            [
                'name' => View_Web::i()->_('MAIN_PROPS'),
                'urn' => 'main_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source' => $fieldsSource,
            ],
            [
                'name' => View_Web::i()->_('ARTICLE_PROPS'),
                'urn' => 'article_props',
                'datatype' => 'select',
                'multiple' => 1,
                'source' => $fieldsSource,
            ],
        ]);
        foreach ($fieldsData as $row) {
            $field = Page_Field::importByURN($row['urn']);
            if (!$field->id) {
                $field = new Page_Field($row);
                $field->commit();
            }
            $result[$row['urn']] = $field;
        }

        return $result;
    }


    /**
     * Создает отзывы к товарам
     */
    public function createComments(
        Material_Type $catalogMType,
        Page $catalog,
        Block_Material $catalogBlock
    ) {
        if (!$mainName) {
            $mainName = $name;
        }
        $MT = Material_Type::importByURN('goods_comments');
        if (!$MT->id) {
            $MT = new Material_Type([
                'name' => View_Web::i()->_('GOODS_COMMENTS'),
                'urn' => $urn,
                'global_type' => 0,
            ]);
            $MT->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('GOODS_ITEM'),
                'urn' => 'material',
                'datatype' => 'material',
                'source' => (int)$catalogMType->id,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('DATE'),
                'urn' => 'date',
                'datatype' => 'date',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('PHONE'),
                'urn' => 'phone',
                'datatype' => 'tel',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('EMAIL'),
                'urn' => 'email',
                'datatype' => 'email',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('RATING'),
                'multiple' => 0,
                'urn' => 'rating',
                'datatype' => 'number',
                'min_val' => 0,
                'max_val' => 5,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('ADVANTAGES'),
                'multiple' => 0,
                'urn' => 'advantages',
                'datatype' => 'textarea',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('DISADVANTAGES'),
                'multiple' => 0,
                'urn' => 'disadvantages',
                'datatype' => 'textarea',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('PRO_VOTES'),
                'urn' => 'pros',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('CON_VOTES'),
                'urn' => 'cons',
                'datatype' => 'number',
            ]);
            $F->commit();

        }

        $S = Snippet::importByURN('__raas_form_notify');
        $FRM = Form::importByURN('goods_comments');
        if (!$FRM->id) {
            $FRM = $this->createForm([
                'name' => View_Web::i()->_('GOODS_COMMENTS'),
                'urn' => 'goods_comments',
                'material_type' => (int)$MT->id,
                'interface_id' => (int)$S->id,
                'fields' => [
                    [
                        'vis' => 0,
                        'name' => View_Web::i()->_('GOODS_ITEM'),
                        'urn' => 'material',
                        'required' => 1,
                        'datatype' => 'material',
                        'show_in_table' => 1,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('YOUR_NAME'),
                        'urn' => 'name',
                        'required' => 1,
                        'datatype' => 'text',
                        'show_in_table' => 1,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('PHONE'),
                        'urn' => 'phone',
                        'datatype' => 'text',
                        'show_in_table' => 1,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('EMAIL'),
                        'urn' => 'email',
                        'datatype' => 'email',
                        'show_in_table' => 0,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('RATING'),
                        'urn' => 'rating',
                        'datatype' => 'number',
                        'min_val' => 1,
                        'max_val' => 5,
                        'show_in_table' => 0,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('ADVANTAGES'),
                        'multiple' => 0,
                        'urn' => 'advantages',
                        'datatype' => 'textarea',
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('DISADVANTAGES'),
                        'multiple' => 0,
                        'urn' => 'disadvantages',
                        'datatype' => 'textarea',
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('COMMENT'),
                        'urn' => '_description_',
                        'required' => 1,
                        'datatype' => 'textarea',
                        'show_in_table' => 0,
                    ],
                    [
                        'vis' => 1,
                        'name' => View_Web::i()->_('AGREE_PRIVACY_POLICY'),
                        'urn' => 'agree',
                        'required' => 1,
                        'datatype' => 'checkbox',
                    ],
                ]
            ]);
        }

        $formBlock = $this->createBlock(
            new Block_Form([
                'vis' => 0,
                'form' => $FRM->id,
            ]),
            'content',
            '__raas_form_interface',
            'feedback',
            $catalog,
            true
        );

        ;
        $listBlock = $this->createBlock(
            new Block_Material([
                'material_type' => (int)$MT->id,
                'vis' => 0,
                'nat' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 0,
                'sort_field_default' => 'post_date',
                'sort_order_default' => 'desc!',
            ]),
            'content',
            '__raas_material_interface',
            'goods_comments',
            $catalog
        );

        $params = $catalogBlock->params;
        $params = str_replace('commentFormBlock=', 'commentFormBlock=' . (int)$formBlock->id, $params);
        $params = str_replace('commentsListBlock=', 'commentsListBlock=' . (int)$listBlock->id, $params);
        $catalogBlock->params = $params;
        $catalogBlock->commit();

        // Создадим материалы
        for ($i = 0; $i < 3; $i++) {
            $user = $this->nextUser;
            $answer = $this->nextUser;
            $temp = $this->nextText;
            $item = new Material([
                'pid' => (int)$MT->id,
                'vis' => 1,
                'name' => $user['name']['first'] . ' '
                       .  $user['name']['last'],
                'description' => $temp['name'],
                'priority' => ($i + 1) * 10,
                'sitemaps_priority' => 0.5
            ]);
            $item->commit();
            $t = time() - 86400 * rand(1, 7);
            $t1 = $t + rand(0, 86400);
            $item->fields['date']->addValue(date('Y-m-d', $t));
            $item->fields['phone']->addValue($user['phone']);
            $item->fields['email']->addValue($user['email']);
            $item->fields['answer_date']->addValue(date('Y-m-d', $t1));
            $item->fields['answer_name']->addValue(
                $answer['name']['first'] . ' ' . $answer['name']['last']
            );
            $item->fields['answer_gender']->addValue(
                (int)($answer['gender'] == 'male')
            );
            $item->fields['answer']->addValue($temp['text']);
            $att = Attachment::createFromFile(
                $user['pic']['filepath'],
                $MT->fields['image']
            );
            $item->fields['image']->addValue(json_encode([
                'vis' => 1,
                'name' => '',
                'description' => '',
                'attachment' => (int)$att->id
            ]));
            $att = Attachment::createFromFile(
                $answer['pic']['filepath'],
                $MT->fields['answer_image']
            );
            $item->fields['answer_image']->addValue(json_encode([
                'vis' => 1,
                'name' => '',
                'description' => '',
                'attachment' => (int)$att->id
            ]));
        }
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
            $cart->trust();
        } else {
            $cart = $this->createPage(
                [
                    'name' => View_Web::i()->_('CART'),
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
                'cart',
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
            $ajaxCart->trust();
        } else {
            $ajaxCart = $this->createPage(
                [
                    'name' => View_Web::i()->_('CART'),
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
                '__raas_shop_cart_interface',
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
                '__raas_shop_cart_interface',
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
                $ajaxCompare
            );
        }
        return $compare;
    }


    /**
     * Создает фильтр
     */
    public function createFilter()
    {
        $ajax = array_shift(Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'ajax'"]
        ]));
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
                new Block_PHP(['vis_material' => Block::BYMATERIAL_WITHOUT]),
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
                            'name' => View_Web::i()->_('YOUR_NAME'),
                            'urn' => 'full_name',
                            'required' => true,
                            'datatype' => 'text',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('PHONE'),
                            'urn' => 'phone',
                            'datatype' => 'text',
                            'required' => true,
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('EMAIL'),
                            'urn' => 'email',
                            'datatype' => 'text',
                            'show_in_table' => true,
                        ],
                        [
                            'name' => View_Web::i()->_('ORDER_COMMENT'),
                            'urn' => 'description',
                            'datatype' => 'textarea',
                            'show_in_table' => 0,
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

        $ajax = array_shift(Page::getSet([
            'where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id
        ]));
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
                'name' => View_Web::i()->_('CATALOG_MAIN_MENU'),
                'realize' => false,
                'addMainPageLink' => false,
                'blockLocation' => 'menu_main',
                'fullMenu' => true,
                'blockPage' => $this->Site,
                'inheritBlock' => true,
            ],
        ]);
        $this->createAJAXMenus($ajax);
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
        $compare = $this->createCompare($cartTypes['compare'], $ajax);
        $this->createFilter();
        $this->adjustBlocks();
        $yml = $this->createYandexMarket($catalogType, $catalog);
        $this->createCron();
    }


    /**
     * Создает AJAX-меню
     */
    public function createAJAXMenus(Page $ajax)
    {
        $cacheInterfaceId = Snippet::importByURN('__raas_cache_interface')->id;
        $menuInterface =  Snippet::importByURN('__raas_menu_interface');
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'menu_left'"]
        ]);
        if ($temp) {
            $leftMenuPage = $temp[0];
        } else {
            $leftMenuPageData = [
                'name' => View_Web::i()->_('LEFT_MENU'),
                'urn' => 'menu_left',
                'template' => 0,
                'cache' => 0,
                'response_code' => 200
            ];
            $leftMenu = Menu::importByURN('left');
            $leftMenuPage = $this->createPage($leftMenuPageData, $ajax);
            $leftMenuWidget = Snippet::importByURN('menu_left');
            $leftMenuBlock = new Block_Menu([
                'menu' => (int)$leftMenu->id,
                'full_menu' => 1,
                'cache_type' => Block::CACHE_DATA,
                'cache_interface_id' => (int)$cacheInterfaceId,
            ]);
            $this->createBlock(
                $leftMenuBlock,
                '',
                $menuInterface,
                $leftMenuWidget,
                $leftMenuPage,
                false
            );
        }
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$ajax->id, "urn = 'menu_mobile'"]
        ]);
        if ($temp) {
            $mobileMenuPage = $temp[0];
        } else {
            $mobileMenuPageData = [
                'name' => View_Web::i()->_('MOBILE_MENU'),
                'urn' => 'menu_mobile',
                'template' => 0,
                'cache' => 0,
                'response_code' => 200
            ];
            $mobileMenu = Menu::importByURN('mobile');
            $mobileMenuPage = $this->createPage($mobileMenuPageData, $ajax);
            $mobileMenuWidget = Snippet::importByURN('menu_mobile');
            $mobileMenuBlock = new Block_Menu([
                'menu' => (int)$mobileMenu->id,
                'full_menu' => 1,
                'cache_type' => Block::CACHE_DATA,
                'cache_interface_id' => (int)$cacheInterfaceId,
            ]);
            $this->createBlock(
                $mobileMenuBlock,
                '',
                $menuInterface,
                $mobileMenuWidget,
                $mobileMenuPage,
                false
            );
        }
    }


    /**
     * Создает cron-задачи
     */
    public function createCron()
    {
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
    }
}
