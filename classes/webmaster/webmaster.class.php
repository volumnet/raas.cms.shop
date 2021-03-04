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
        $interfaces['hidden_props'] = $this->checkSnippet(
            $this->interfacesFolder,
            'hidden_props',
            'HIDDEN_PROPERTIES',
            file_get_contents(
                Module::i()->resourcesDir . '/interfaces/hidden_props.php'
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
            'cart/compare' => View_Web::i()->_('COMPARISON'),
            'cart/order' => View_Web::i()->_('VIEW_ORDER'),
            'epay/robokassa' => View_Web::i()->_('ROBOKASSA'),
            'materials/catalog/catalog_item' => View_Web::i()->_('CATALOG_ITEM'),
            'materials/catalog/catalog_category' => View_Web::i()->_('CATEGORY_INC'),
            'materials/catalog/catalog' => View_Web::i()->_('CATALOG'),
            'materials/catalog/catalog_filter' => View_Web::i()->_('CATALOG_FILTER'),
            'materials/catalog/catalog_controls' => View_Web::i()->_('CATALOG_CONTROLS'),
            'materials/brands/brands' => View_Web::i()->_('BRANDS'),
            'materials/brands/brands_main' => View_Web::i()->_('BRANDS_MAIN'),
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
     * Создаем бренды
     * @return Material_Type Созданный или существующий тип материала
     */
    public function createBrands()
    {
        $MT = Material_Type::importByURN('brands');
        if (!$MT->id) {
            $MT = new Material_Type([
                'name' => View_Web::i()->_('BRANDS'),
                'urn' => 'brands',
                'global_type' => 1,
            ]);
            $MT->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('IMAGE'),
                'multiple' => 0,
                'urn' => 'image',
                'datatype' => 'image',
                'show_in_table' => 1,
            ]);
            $F->commit();
        }
        $temp = Page::getSet([
            'where' => ["pid = " . (int)$this->Site->id, "urn = 'brands'"]
        ]);
        if ($temp) {
            $brandsPage = $temp[0];
            $brandsPage->trust();
        } else {
            $brandsPage = $this->createPage(
                ['name' => View_Web::i()->_('BRANDS'), 'urn' => 'brands'],
                $this->Site
            );

            for ($i = 0; $i < 3; $i++) {
                $temp = $this->nextText;
                $Item = new Material([
                    'pid' => (int)$MT->id,
                    'vis' => 1,
                    'name' => View_Web::i()->_('BRAND') . ' ' . ($i + 1),
                    'description' => $temp['text'],
                    'sitemaps_priority' => 0.5
                ]);
                $Item->commit();
                $att = Attachment::createFromFile(
                    $this->nextImage,
                    $Item->fields['image']
                );
                $Item->fields['image']->addValue(json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => (int)$att->id
                ]));
            }

            $brandsBlock = new Block_Material([
                'material_type' => (int)$MT->id,
                'nat' => 1,
                'pages_var_name' => 'page',
                'rows_per_page' => 20,
                'sort_field_default' => 'name',
                'sort_order_default' => 'asc',
                'sort_var_name' => 'sort',
                'order_var_name' => 'order',
                'params' => ''
            ]);
            $brandsBlock = $this->createBlock(
                $brandsBlock,
                'content',
                'material_interface',
                'brands',
                $brandsPage,
                true
            );
            $brandsBlock->commit();

            $brandsMainBlock = new Block_Material([
                'material_type' => (int)$MT->id,
                'nat' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 20,
                'sort_field_default' => 'name',
                'sort_order_default' => 'asc!',
                'sort_var_name' => '',
                'order_var_name' => '',
                'params' => ''
            ]);
            $brandsMainBlock = $this->createBlock(
                $brandsMainBlock,
                'content',
                'material_interface',
                'brands_main',
                $this->Site,
                true
            );
            $brandsMainBlock->commit();
        }
        return $MT;
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
                'name' => View_Web::i()->_('CATALOG'),
                'urn' => 'catalog',
                'global_type' => 0,
            ]);
            $MT->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('ARTICLE'),
                'urn' => 'article',
                'datatype' => 'text',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('PRICE'),
                'urn' => 'price',
                'datatype' => 'number',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('IMAGE'),
                'multiple' => 1,
                'urn' => 'images',
                'datatype' => 'image',
                'show_in_table' => 1,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('VIDEOS'),
                'multiple' => 1,
                'urn' => 'videos',
                'datatype' => 'text',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('FILES'),
                'multiple' => 1,
                'urn' => 'files',
                'datatype' => 'file',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('SPECIAL_OFFER'),
                'urn' => 'spec',
                'datatype' => 'checkbox',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('AVAILABLE'),
                'urn' => 'available',
                'defval' => 1,
                'datatype' => 'checkbox',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('MINIMAL_AMOUNT'),
                'urn' => 'min',
                'defval' => 1,
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('CART_STEP'),
                'urn' => 'step',
                'defval' => 1,
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('OLD_PRICE'),
                'urn' => 'price_old',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('DISCOUNT'),
                'urn' => 'discount',
                'datatype' => 'text',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('RELATED_GOODS'),
                'multiple' => 1,
                'urn' => 'related',
                'datatype' => 'material',
                'source' => $MT->id,
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('UNIT'),
                'multiple' => 0,
                'urn' => 'unit',
                'datatype' => 'text',
                'placeholder' => View_Web::i()->_('PCS'),
            ]);
            $F->commit();

            $brandsMT = Material_Type::importByURN('brands');
            if ($brandsMT->id) {
                $F = new Material_Field([
                    'pid' => $MT->id,
                    'vis' => 0,
                    'name' => View_Web::i()->_('BRAND'),
                    'multiple' => 0,
                    'urn' => 'brand',
                    'datatype' => 'material',
                    'source' => $brandsMT->id,
                ]);
                $F->commit();
            }

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
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
                'vis' => 0,
                'name' => View_Web::i()->_('REVIEWS_COUNTER'),
                'multiple' => 0,
                'urn' => 'reviews_counter',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('LENGTH_CM'),
                'multiple' => 0,
                'urn' => 'length',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('WIDTH_CM'),
                'multiple' => 0,
                'urn' => 'width',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 0,
                'name' => View_Web::i()->_('HEIGHT_CM'),
                'multiple' => 0,
                'urn' => 'height',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('WEIGHT_G'),
                'multiple' => 0,
                'urn' => 'weight',
                'datatype' => 'number',
            ]);
            $F->commit();

            $F = new Material_Field([
                'pid' => $MT->id,
                'vis' => 1,
                'name' => View_Web::i()->_('COUNTRY'),
                'multiple' => 0,
                'urn' => 'country',
                'datatype' => 'text',
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
                'name' => View_Web::i()->_('COMPARE'),
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
            $catalog->trust();
        } else {
            foreach (['title', 'description', 'keywords'] as $key) {
                $urn = 'meta_' . $key . '_template';
                $field = new Page_Field([
                    'classname' => Material_Type::class,
                    'pid' => 0,
                    'datatype' => 'text',
                    'urn' => $urn,
                    'name' => View_Web::i()->_(mb_strtoupper($urn)),
                ]);
                $field->commit();
            }
            foreach (['title', 'description', 'keywords'] as $key) {
                $urn = 'meta_' . $key . '_list_template';
                $field = new Page_Field([
                    'classname' => Material_Type::class,
                    'pid' => 0,
                    'datatype' => 'text',
                    'urn' => $urn,
                    'name' => View_Web::i()->_(mb_strtoupper($urn)),
                ]);
                $field->commit();
            }
            $field = new Page_Field([
                'classname' => Material_Type::class,
                'pid' => 0,
                'datatype' => 'text',
                'urn' => $urn,
                'name' => View_Web::i()->_('DISCOUNT'),
            ]);
            $field->commit();


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

            $field = new Page_Field([
                'classname' => Material_Type::class,
                'pid' => 0,
                'datatype' => 'select',
                'urn' => 'filter_props',
                'multiple' => 1,
                'name' => View_Web::i()->_('FILTER_PROPS'),
                'source' => $fieldsSource,
            ]);
            $field->commit();
            $field = new Page_Field([
                'classname' => Material_Type::class,
                'pid' => 0,
                'datatype' => 'select',
                'urn' => 'main_props',
                'multiple' => 1,
                'name' => View_Web::i()->_('MAIN_PROPS'),
                'source' => $fieldsSource,
            ]);
            $field->commit();
            $field = new Page_Field([
                'classname' => Material_Type::class,
                'pid' => 0,
                'datatype' => 'select',
                'urn' => 'article_props',
                'multiple' => 1,
                'name' => View_Web::i()->_('ARTICLE_PROPS'),
                'source' => $fieldsSource,
            ]);
            $field->commit();


            $categories = [];
            $catalog = $this->createPage(
                ['name' => View_Web::i()->_('CATALOG'), 'urn' => 'catalog'],
                $this->Site
            );
            for ($i = 1; $i <= 3; $i++) {
                $categories[$i] = $this->createPage(
                    [
                        'name' => View_Web::i()->_('CATEGORY') . ' ' . $i,
                        'urn' => 'category' . $i
                    ],
                    $catalog
                );
                if ($i == 1) {
                    for ($j = 1; $j <= 3; $j++) {
                        $categories[$i . $j] = $this->createPage(
                            [
                                'name' => View_Web::i()->_('CATEGORY')
                                       .  ' ' . $i . $j,
                                'urn' => 'category' . $i . $j
                            ],
                            $categories[$i]
                        );
                        if ($j == 1) {
                            for ($k = 1; $k <= 3; $k++) {
                                $categories[$i . $j . $k] = $this->createPage(
                                    [
                                        'name' => View_Web::i()->_('CATEGORY')
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
            $brandsMaterialType = Material_Type::importByURN('brands');
            $brands = Material::getSet([
                'where' => "pid = " . (int)$brandsMaterialType->id,
                'orderBy' => "id",
            ]);
            $units = ['', 'гр', 'комплект', 'м'];
            for ($i = 0; $i < 10; $i++) {
                $temp = $this->nextText;
                $Item = new Material([
                    'pid' => (int)$catalogType->id,
                    'vis' => 1,
                    'name' => View_Web::i()->_('GOODS_ITEM') . ' ' . ($i + 1),
                    'description' => $temp['text'],
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
                        $Item->fields['images']
                    );
                    $Item->fields['images']->addValue(json_encode([
                        'vis' => 1,
                        'name' => '',
                        'description' => '',
                        'attachment' => (int)$att->id
                    ]));
                }
                $Item->fields['brand']->addValue($brands[rand(0, count($brands) - 1)]->id);
                $Item->fields['unit']->addValue($units[rand(0, count($units) - 1)]);
                $Item->fields['rating']->addValue(rand(1, 5));
                $Item->fields['reviews_counter']->addValue(rand(0, 100));
                $Item->fields['length']->addValue(rand(0, 1000));
                $Item->fields['width']->addValue(rand(0, 1000));
                $Item->fields['height']->addValue(rand(0, 1000));
                $Item->fields['weight']->addValue(rand(0, 1000));
                $Item->fields['country']->addValue(View_Web::i()->_('COUNTRY') . ' ' . ($i + 1));
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
                'sort_var_name' => 'sort',
                'order_var_name' => 'order',
                'params' => 'metaTemplates=template&listMetaTemplates=list_template&withChildrenGoods=1&commentFormBlock=&commentsListBlock='
            ]);
            $catalogBlock = $this->createBlock(
                $B,
                'content',
                'catalog_interface',
                'catalog',
                $catalog,
                true
            );
            $B->filter = [
                [
                    'id' => (int)$B->id,
                    'var' => 'search_string',
                    'relation' => 'FULLTEXT',
                    'field' => (int)$catalogType->fields['article']->id,
                ]
            ];
            $B->sort = [
                [
                    'id' => (int)$B->id,
                    'var' => 'price',
                    'field' => (int)$catalogType->fields['price']->id,
                    'relation' => 'asc',
                ],
                [
                    'id' => (int)$B->id,
                    'var' => 'name',
                    'field' => 'name',
                    'relation' => 'asc',
                ],
            ];
            $B->commit();

            $specBlock = new Block_Material([
                'material_type' => (int)$catalogType->id,
                'nat' => 0,
                'params' => 'spec=1',
                'pages_var_name' => '',
                'rows_per_page' => 20,
                'sort_field_default' => 'random',
                'sort_order_default' => 'asc!',
                'sort_var_name' => '',
                'order_var_name' => '',
            ]);
            $specBlock->filter = [
                [
                    'id' => (int)$specBlock->id,
                    'var' => 'spec',
                    'relation' => '=',
                    'field' => (int)$catalogType->fields['spec']->id,
                ]
            ];
            $specBlock = $this->createBlock(
                $specBlock,
                'content',
                'spec_interface',
                'spec',
                $this->Site,
                false
            );

            $newBlock = new Block_Material([
                'material_type' => (int)$catalogType->id,
                'nat' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 20,
                'sort_field_default' => 'post_date',
                'sort_order_default' => 'desc!',
                'sort_var_name' => '',
                'order_var_name' => '',
            ]);
            $newBlock = $this->createBlock(
                $newBlock,
                'content',
                'spec_interface',
                'spec',
                $this->Site,
                false
            );

            $popularBlock = new Block_Material([
                'material_type' => (int)$catalogType->id,
                'nat' => 0,
                'params' => 'type=popular',
                'pages_var_name' => '',
                'rows_per_page' => 20,
                'sort_field_default' => 'post_date',
                'sort_order_default' => 'desc!',
                'sort_var_name' => '',
                'order_var_name' => '',
            ]);
            $popularBlock = $this->createBlock(
                $popularBlock,
                'content',
                'spec_interface',
                'spec',
                $this->Site,
                false
            );

            $this->createCron();
        }
        return $catalog;
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
                    'name' => View_Web::i()->_('COMPARE'),
                    'urn' => 'compare',
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
                    'name' => View_Web::i()->_('COMPARE'),
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
            'where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id
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
        $this->createBrands();
        $catalogType = $this->createMaterialType();
        $ajax = array_shift(Page::getSet([
            'where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id
        ]));
        $catalog = $this->createCatalog($catalogType);
        $menus = $this->createMenus([[
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
        $compare = $this->createCompare($cartTypes['compare'], $ajax);
        $this->createFilter();
        $this->adjustBlocks();
        $yml = $this->createYandexMarket($catalogType, $catalog);
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
