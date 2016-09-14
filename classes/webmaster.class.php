<?php
namespace RAAS\CMS\Shop;

use \RAAS\Application;
use \RAAS\CMS\Snippet;
use \RAAS\CMS\Snippet_Folder;
use \RAAS\CMS\Material_Type;
use \RAAS\CMS\Material_Field;
use \RAAS\CMS\Form_Field;
use \RAAS\CMS\Page;
use \RAAS\CMS\Block_Material;
use \RAAS\CMS\Package;
use \RAAS\CMS\Form;
use \RAAS\CMS\Material;
use \RAAS\Attachment;
use \RAAS\CMS\Menu;
use \RAAS\CMS\Block_Menu;
use \RAAS\CMS\Block_PHP;
use \RAAS\CMS\Block;

class Webmaster extends \RAAS\CMS\Webmaster
{
    protected static $instance;

    protected $leftMenuBlock;
    protected $leftCartBlock;
    protected $leftFavoritesBlock;

    public function __get($var)
    {
        switch ($var) {
            case 'nextImage':
            case 'nextText':
            case 'nextUser':
            case 'Site':
                return parent::__get($var);
                break;
            default:
                return \RAAS\CMS\Shop\Module::i()->__get($var);
                break;
        }
    }


    /**
     * Создаем стандартные сниппеты
     * @return array[Snippet] массив созданных или существующих интерфейсов
     */
    public function checkStdInterfaces()
    {
        $ifo = Snippet_Folder::importByURN('__raas_interfaces');
        $interfaces = array();
        $interfaces['__raas_shop_cart_interface'] = $this->checkSnippet($ifo, '__raas_shop_cart_interface', 'CART_STANDARD_INTERFACE', $this->stdCartInterface);
        $interfaces['__raas_shop_order_notify'] = $this->checkSnippet($ifo, '__raas_shop_order_notify', 'ORDER_STANDARD_NOTIFICATION', $this->stdFormTemplate);
        $interfaces['__raas_shop_imageloader_interface'] = $this->checkSnippet($ifo, '__raas_shop_imageloader_interface', 'IMAGELOADER_STANDARD_INTERFACE', $this->stdImageLoaderInterface);
        $interfaces['__raas_shop_priceloader_interface'] = $this->checkSnippet($ifo, '__raas_shop_priceloader_interface', 'PRICELOADER_STANDARD_INTERFACE', $this->stdPriceLoaderInterface);
        $interfaces['__raas_shop_yml_interface'] = $this->checkSnippet($ifo, '__raas_shop_yml_interface', 'YML_STANDARD_INTERFACE', file_get_contents($this->resourcesDir . '/yml_interface.php'));
        $interfaces['__raas_robokassa_interface'] = $this->checkSnippet($ifo, '__raas_robokassa_interface', 'ROBOKASSA_INTERFACE', file_get_contents($this->resourcesDir . '/robokassa_interface.php'));
        $interfaces['catalog_interface'] = $this->checkSnippet($ifo, 'catalog_interface', 'CATALOG_INTERFACE', file_get_contents($this->resourcesDir . '/catalog_interface.php'), false);
        return $interfaces;
    }


    /**
     * Добавим виджеты
     * @return array[Snippet] Массив созданных или существующих виджетов
     */
    public function createWidgets()
    {
        // Добавим виджеты
        $snippets = array(
            'cart' => $this->view->_('CART'),
            'robokassa' => $this->view->_('ROBOKASSA'),
            'yml' => $this->view->_('YANDEX_MARKET'),
            'item_inc' => $this->view->_('ITEM_INC'),
            'category_inc' => $this->view->_('CATEGORY_INC'),
            'catalog' => $this->view->_('CATALOG'),
            'catalog_filter' => $this->view->_('CATALOG_FILTER'),
            'cart_main' => $this->view->_('CART_MAIN'),
            'favorites_main' => $this->view->_('FAVORITES_MAIN'),
            'menu_left' => $this->view->_('LEFT_MENU'),
            'item_inc' => $this->view->_('ITEM_INC'),
            'file_inc' => $this->view->_('FILE_INC'),
            'spec' => $this->view->_('SPECIAL_OFFER'),
            'my_orders' => $this->view->_('MY_ORDERS'),
        );
        $VF = Snippet_Folder::importByURN('__raas_views');
        foreach ($snippets as $urn => $name) {
            $temp = Snippet::importByURN($urn);
            if (!$temp->id) {
                $S = new Snippet();
                $S->name = $this->view->_($name);
                $S->urn = $urn;
                $S->pid = $VF->id;
                $f = $this->resourcesDir . '/' . $urn . '.tmp.php';
                $S->description = file_get_contents($f);
                $S->commit();
            }
        }
    }


    /**
     * Создаем тип материалов
     * @return Material_Type Созданный или существующий тип материала
     */
    public function createMaterialType()
    {
        $MT = Material_Type::importByURN('catalog');
        if (!$MT->id) {
            $MT = new Material_Type(array(
                'name' => $this->view->_('CATALOG'),
                'urn' => 'catalog',
                'global_type' => 0,
            ));
            $MT->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('ARTICLE'),
                'urn' => 'article',
                'datatype' => 'text',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('PRICE'),
                'urn' => 'price',
                'datatype' => 'number',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('IMAGE'),
                'multiple' => 1,
                'urn' => 'images',
                'datatype' => 'image',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('VIDEOS'),
                'multiple' => 1,
                'urn' => 'videos',
                'datatype' => 'text',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('FILES'),
                'multiple' => 1,
                'urn' => 'files',
                'datatype' => 'file',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('SPECIAL_OFFER'),
                'urn' => 'spec',
                'datatype' => 'checkbox',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('AVAILABLE'),
                'urn' => 'available',
                'defval' => 1,
                'datatype' => 'checkbox',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('MINIMAL_AMOUNT'),
                'urn' => 'min',
                'defval' => 1,
                'datatype' => 'number',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('CART_STEP'),
                'urn' => 'step',
                'defval' => 1,
                'datatype' => 'number',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('OLD_PRICE'),
                'urn' => 'price_old',
                'datatype' => 'number',
            ));
            $F->commit();

            $F = new Material_Field(array(
                'pid' => $MT->id,
                'name' => $this->view->_('RELATED_GOODS'),
                'multiple' => 1,
                'urn' => 'related',
                'datatype' => 'material',
                'source' => $MT->id,
            ));
            $F->commit();
        }
        return $MT;
    }


    /**
     * Создадим формы
     * @param Snippet $snippetOrderFormNotify сниппет уведомления о заказе
     * @return array[Form] созданные или существующие формы
     */
    public function createForms(Snippet $snippetOrderFormNotify)
    {
        $forms = array();
        $FRM = Form::importByURN('');
        if (!$FRM->id) {
            $FRM = new \RAAS\CMS\Form(array(
                'name' => $this->view->_('ORDER_FORM'),
                'urn' => 'order',
                'signature' => 1,
                'antispam' => 'hidden',
                'antispam_field_name' => '_name',
                'interface_id' => (int)$snippetOrderFormNotify->id,
            ));
            $FRM->commit();

            $F = new Form_Field(array(
                'pid' => $FRM->id,
                'name' => $this->view->_('YOUR_NAME'),
                'urn' => 'full_name',
                'required' => 1,
                'datatype' => 'text',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F = new Form_Field(array(
                'pid' => $FRM->id,
                'name' => $this->view->_('PHONE'),
                'urn' => 'phone',
                'datatype' => 'text',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F = new Form_Field(array(
                'pid' => $FRM->id,
                'name' => $this->view->_('EMAIL'),
                'urn' => 'email',
                'datatype' => 'text',
                'show_in_table' => 1,
            ));
            $F->commit();

            $F = new Form_Field(array(
                'pid' => $FRM->id,
                'name' => $this->view->_('ORDER_COMMENT'),
                'urn' => 'description',
                'datatype' => 'textarea',
                'show_in_table' => 0,
            ));
            $F->commit();
        }
        $forms['order'] = $FRM;
        return $forms;
    }


    /**
     * Создадим меню
     * @param Page $catalog Страница каталога
     * @return array[Menu] созданные или существующие меню
     */
    public function createMenus(Page $catalog)
    {
        $menus = array();
        foreach (array(
            array('page_id' => $catalog->id, 'urn' => 'left', 'inherit' => 10, 'name' => $this->view->_('LEFT_MENU'))
        ) as $row) {
            $MNU = Menu::importByURN($row['urn']);
            if (!$MNU->id) {
                $MNU = new Menu($row);
                $MNU->commit();
            }
            $menus[$row['urn']] = $MNU;
        }

        $stdCacheInterface = Snippet::importByURN('__raas_cache_interface');
        $B = new Block_Menu(array(
            'menu' => $menus['left']->id ?: 0,
            'full_menu' => 1,
            'cache_type' => Block::CACHE_DATA,
            'cache_interface_id' => (int)$stdCacheInterface->id
        ));
        $this->createBlock($B, 'left', '__raas_menu_interface', 'menu_left', $this->Site, true);
        $this->leftMenuBlock = $B;

        return $menus;
    }


    /**
     * Создаем типов корзин
     * @param Material_Type $catalogType типа материалов: каталог
     * @param Material_Field $priceCol колонка с ценой
     * @param Form $orderForm Форма заказа
     * @return array[Cart_Type] массив созданных или существующих типов корзин
     */
    public function createCartTypes(Material_Type $catalogType, Material_Field $priceCol, Form $orderForm)
    {
        $cartTypes = array();
        $CT = Cart_Type::importByURN('cart');
        if (!$CT->id) {
            $CT = new Cart_Type(array(
                'name' => $this->view->_('CART'),
                'urn' => 'cart',
                'form_id' => (int)$orderForm->id,
                'no_amount' => 0,
                'mtypes' => array(array('id' => $catalogType->id, 'price_id' => $priceCol->id)),
            ));
            $CT->commit();
        }
        $cartTypes['cart'] = $CT;

        $CT = Cart_Type::importByURN('favorites');
        if (!$CT->id) {
            $CT = new Cart_Type(array(
                'name' => $this->view->_('FAVORITES'),
                'urn' => 'favorites',
                'form_id' => 0,
                'no_amount' => 1,
                'mtypes' => array(array('id' => $catalogType->id, 'price_id' => $priceCol->id)),
            ));
            $CT->commit();
        }
        $cartTypes['favorites'] = $CT;
        return $cartTypes;
    }


    /**
     * Создадим статусы заказов
     * @return array[Order_Status] массив созданных или существующих статусов заказов
     */
    public function createOrderStatuses()
    {
        $orderStatuses = array();
        foreach (array('progress' => 'IN_PROGRESS', 'completed' => 'COMPLETED', 'canceled' => 'CANCELED') as $key => $val) {
            $OS = Order_Status::importByURN($key);
            if (!$OS->id) {
                $OS = new Order_Status(array('name' => $this->view->_($val), 'urn' => $key));
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
     * @return array('priceloader' => PriceLoader, 'imageloader' => ImageLoader) созданные загрузчики
     */
    public function createLoaders(Material_Type $catalogType, Page $catalog)
    {
        $loaders = array();
        $IL = ImageLoader::importByURN('default');
        if (!$IL->id) {
            $IL = new ImageLoader(array(
                'mtype' => $catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'name' => $this->view->_('DEFAULT_IMAGELOADER'),
                'urn' => 'default',
                'sep_string' => '_',
                'interface_id' => (int)Snippet::importByURN('__raas_shop_imageloader_interface')->id,
            ));
            $IL->commit();
        }
        $loaders['imageloader'] = $IL;

        $PL = PriceLoader::importByURN('default');
        if (!$PL->id) {
            $PL = new PriceLoader(array(
                'mtype' => (int)$catalogType->id,
                'ufid' => $catalogType->fields['article']->id,
                'name' => $this->view->_('DEFAULT_PRICELOADER'),
                'urn' => 'default',
                'cat_id' => (int)$catalog->id,
                'interface_id' => (int)Snippet::importByURN('__raas_shop_priceloader_interface')->id,
            ));
            $PL->commit();
            $i = 0;
            $PLC = new PriceLoader_Column(
                array('pid' => $PL->id, 'fid' => (int)$catalogType->fields['article']->id, 'priority' => ++$i)
            );
            $PLC->commit();
            $PLC = new PriceLoader_Column(array('pid' => $PL->id, 'fid' => 'name', 'priority' => ++$i));
            $PLC->commit();
            $PLC = new PriceLoader_Column(array('pid' => $PL->id, 'fid' => 'description', 'priority' => ++$i));
            $PLC->commit();
            $PLC = new PriceLoader_Column(array(
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['related']->id,
                'callback' => "\$y = array_filter(array_map('trim', preg_split('/[;,]/umi', \$x)), 'trim');\n"
                           .  "$temp = array();\n"
                           .  "foreach (\$y as \$val) {\n"
                           .  "    \$SQL_query = \"SELECT pid FROM cms_data WHERE fid = " . (int)$catalogType->fields['article']->id . " AND value = '\" . \\RAAS\\CMS\\Material::_SQL()->real_escape_string(\$val) . \"'\";\n"
                           .  "    if (\$SQL_result = \\RAAS\\CMS\\Material::_SQL()->getvalue(\$SQL_query)) {\n"
                           .  "        \$temp[] = (int)\$SQL_result;\n"
                           .  "    }\n"
                           .  "}\n"
                           .  "return \$temp;",
                'callback_download' => "\$temp = array();\n"
                                    .  "foreach ((array)\$x as \$val) {\n"
                                    .  "    \$row = new \\RAAS\\CMS\\Material((int)\$val);\n"
                                    .  "    if (\$row->id) {\n"
                                    .  "        \$temp[] = \$row->article;\n"
                                    .  "    }\n"
                                    .  "}\n"
                                    .  "return implode(', ', \$temp);",
                'priority' => ++$i
            ));
            $PLC->commit();
            $PLC = new PriceLoader_Column(array(
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['available']->id,
                'callback' => "return (\$x && (trim(\$x) !== '0')) ? (int)(bool)preg_match('/налич/umi', \$x) : 0;",
                'callback_download' => "return (int)\$x ? 'в наличии' : 'под заказ';",
                'priority' => ++$i
            ));
            $PLC->commit();
            $PLC = new PriceLoader_Column(array(
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['price_old']->id,
                'callback' => "\$y = str_replace(',', '.', \$x); \$y = (float)preg_replace('/[^\\d\\.]+/i', '', trim(\$x)); return \$y;",
                'priority' => ++$i
            ));
            $PLC->commit();
            $PLC = new PriceLoader_Column(array(
                'pid' => $PL->id,
                'fid' => (int)$catalogType->fields['price']->id,
                'callback' => "\$y = str_replace(',', '.', \$x); \$y = (float)preg_replace('/[^\\d\\.]+/i', '', trim(\$y)); return \$y;",
                'priority' => ++$i
            ));
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
        $temp = Page::getSet(array('where' => array("pid = " . (int)$this->Site->id, "urn = 'catalog'")));
        if ($temp) {
            $catalog = $temp[0];
        } else {
            $categories = array();
            $catalog = $this->createPage(array('name' => $this->view->_('CATALOG'), 'urn' => 'catalog'), $this->Site);
            for ($i = 1; $i <= 3; $i++) {
                $categories[$i] = $this->createPage(array('name' => $this->view->_('CATEGORY') . ' ' . $i, 'urn' => 'category' . $i), $catalog);
                if ($i == 1) {
                    for ($j = 1; $j <= 3; $j++) {
                        $categories[$i . $j] = $this->createPage(array('name' => $this->view->_('CATEGORY') . ' ' . $i . $j, 'urn' => 'category' . $i . $j), $categories[$i]);
                        if ($j == 1) {
                            for ($k = 1; $k <= 3; $k++) {
                                $categories[$i . $j . $k] = $this->createPage(array('name' => $this->view->_('CATEGORY') . ' ' . $i . $j . $k, 'urn' => 'category' . $i . $j . $k), $categories[$i . $j]);
                            }
                        }
                    }
                }
            }
            foreach ($categories as $category) {
                $row = $this->nextImage;
                $att = $this->getAttachmentFromFilename($row['filename'], $row['url'], $category->fields['image']);
                $row = array('vis' => 1, 'name' => '', 'description' => '', 'attachment' => (int)$att->id);
                $category->fields['image']->addValue(json_encode($row));
            }
            $goods = array();
            for ($i = 0; $i < 10; $i++) {
                $temp = $this->nextText;
                $Item = new Material(array(
                    'pid' => (int)$catalogType->id,
                    'vis' => 1,
                    'name' => $this->view->_('GOODS_ITEM') . ' ' . ($i + 1),
                    'description' => $temp['text'],
                    'priority' => ($i + 1) * 10,
                    'sitemaps_priority' => 0.5
                ));
                $cats = array();
                $Item->cats = array(
                    $this->Site->id,
                    $categories[111]->id, $categories[112]->id, $categories[113]->id,
                    $categories[12]->id, $categories[13]->id,
                    $categories[2]->id, $categories[3]->id,
                );
                $Item->commit();
                $Item->fields['article']->addValue(dechex(crc32($i)));
                $Item->fields['price']->addValue($price = rand(100, 100000));
                $Item->fields['price_old']->addValue(($price % 2) ? (int)($price * (100 + rand(5, 25)) / 100) : 0);
                $Item->fields['videos']->addValue('http://www.youtube.com/watch?v=YVgc2PQd_bo');
                $Item->fields['videos']->addValue('http://www.youtube.com/watch?v=YVgc2PQd_bo');
                $Item->fields['spec']->addValue(1);
                $Item->fields['available']->addValue((int)(bool)($i % 4));
                $Item->fields['min']->addValue($i % 4 ? 1 : 2);
                $Item->fields['step']->addValue($i % 4 ? 1 : 2);
                foreach (array('test.doc', 'test.pdf') as $val) {
                    $att = new Attachment();
                    $att->copy = true;
                    $att->upload = $this->resourcesDir . '/' . $val;
                    $att->filename = $val;
                    $att->mime = 'application/binary';
                    $att->parent = $Item->fields['files'];
                    $att->image = 0;
                    $att->commit();
                    $row = array('vis' => 1, 'name' => '', 'description' => '', 'attachment' => (int)$att->id);
                    $Item->fields['files']->addValue(json_encode($row));
                }

                for ($j = 0; $j < (!$i ? 4 : 1); $j++) {
                    $row = $this->nextImage;
                    $att = $this->getAttachmentFromFilename($row['filename'], $row['url'], $catalogType->fields['images']);
                    $row = array('vis' => 1, 'name' => '', 'description' => '', 'attachment' => (int)$att->id);
                    $Item->fields['images']->addValue(json_encode($row));
                }
                $goods[] = $Item;
            }
            for ($i = 0; $i < 10; $i++) {
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
                $Item->fields['related']->addValue($goods[rand(0, 9)]->id);
            }

            $B = new Block_Material(array(
                'material_type' => (int)$catalogType->id,
                'nat' => 1,
                'pages_var_name' => 'page',
                'rows_per_page' => 20,
                'sort_field_default' => 'price',
                'sort_order_default' => 'asc',
            ));
            $catalogBlock = $this->createBlock($B, 'content', 'catalog_interface', 'catalog', $catalog, true);

            $B = new Block_Material(array(
                'material_type' => (int)$catalogType->id,
                'nat' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 0,
                'sort_field_default' => 'price',
                'sort_order_default' => 'asc',
            ));
            $catalogMain = $this->createBlock($B, 'content', 'catalog_interface', 'catalog', $this->Site, false);

            $B = new Block_PHP();
            $specBlock = $this->createBlock($B, 'content', null, 'spec', $this->Site, false);
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
        $temp = Page::getSet(array('where' => array("pid = " . (int)$this->Site->id, "urn = 'cart'")));
        if ($temp) {
            $cart = $temp[0];
        } else {
            $cart = $this->createPage(
                array(
                    'name' => $this->view->_('CART'),
                    'urn' => 'cart',
                    'cache' => 0,
                    'response_code' => 200
                ),
                $this->Site
            );
            $B = new Block_Cart(array('cart_type' => (int)$cartType->id));
            $this->createBlock($B, 'content', '__raas_shop_cart_interface', 'cart', $cart);

            $B = new Block_PHP();
            $this->createBlock($B, 'left', '', 'cart_main', $this->Site, true);
            $this->leftCartBlock = $B;
        }

        $temp = Page::getSet(array('where' => array("pid = " . (int)$ajax->id, "urn = 'cart'")));
        if ($temp) {
            $ajaxCart = $temp[0];
        } else {
            $ajaxCart = $this->createPage(
                array(
                    'name' => $this->view->_('CART'),
                    'urn' => 'cart',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200
                ),
                $ajax
            );
            $B = new Block_Cart(array('cart_type' => (int)$cartType->id));
            $this->createBlock($B, '', '__raas_shop_cart_interface', 'cart', $ajaxCart);
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
        $temp = Page::getSet(array('where' => array("pid = " . (int)$this->Site->id, "urn = 'favorites'")));
        if ($temp) {
            $favorites = $temp[0];
        } else {
            $favorites = $this->createPage(
                array(
                    'name' => $this->view->_('FAVORITES'),
                    'urn' => 'favorites',
                    'cache' => 0,
                    'response_code' => 200
                ),
                $this->Site
            );
            $B = new Block_Cart(array('cart_type' => (int)$cartType->id));
            $this->createBlock($B, 'content', '__raas_shop_cart_interface', 'cart', $favorites);

            $B = new Block_PHP();
            $this->createBlock($B, 'left', '', 'favorites_main', $this->Site, true);
            $this->leftFavoritesBlock = $B;
        }

        $temp = Page::getSet(array('where' => array("pid = " . (int)$ajax->id, "urn = 'favorites'")));
        if ($temp) {
            $ajaxFavorites = $temp[0];
        } else {
            $ajaxFavorites = $this->createPage(
                array(
                    'name' => $this->view->_('FAVORITES'),
                    'urn' => 'favorites',
                    'template' => 0,
                    'cache' => 0,
                    'response_code' => 200
                ),
                $ajax
            );
            $B = new Block_Cart(array('cart_type' => (int)$cartType->id));
            $this->createBlock($B, '', '__raas_shop_cart_interface', 'cart', $ajaxFavorites);
        }
        return $favorites;
    }


    /**
     * Выравниваем левые блоки
     */
    public function adjustBlocks()
    {
        $c = $this->SQL->getvalue("SELECT COUNT(*) FROM " . Block::_tablename() . " WHERE location = 'left'");
        $this->leftMenuBlock->swap(-$c, $this->Site);
        $this->leftFavoritesBlock->swap(-$c, $this->Site);
        $this->leftCartBlock->swap(-$c, $this->Site);
    }


    /**
     * Создать страницу Яндекс-Маркета
     * @param Material_Type $catalogType Тип материала каталога
     * @param Page $catalog Страница каталога
     * @return Page Созданная или существующая страница
     */
    public function createYandexMarket(Material_Type $catalogType, Page $catalog)
    {
        $temp = Page::getSet(array('where' => array("pid = " . (int)$this->Site->id, "urn = 'yml'")));
        if ($temp) {
            $yml = $temp[0];
        } else {
            $yml = $this->createPage(
                array('name' => $this->view->_('YANDEX_MARKET'), 'urn' => 'yml', 'template' => 0, 'response_code' => 200),
                $this->Site
            );

            $B = new Block_YML(array(
                'agency' => 'Volume Networks',
                'email' => 'info@volumnet.ru',
                'default_currency' => 'RUR',
                'meta_cats' => array_merge(array((int)$catalog->id), (array)$catalog->all_children_ids),
                'local_delivery_cost' => 0,
            ));
            $this->createBlock($B, '', '__raas_shop_yml_interface', 'yml', $yml);
            $B->addType(
                $catalogType,
                '',
                array(
                    'available' => array('field_id' => (int)$catalogType->fields['available']->id),
                    'price' => array('field_id' => (int)$catalogType->fields['price']->id),
                    'oldprice' => array('field_id' => (int)$catalogType->fields['price_old']->id),
                    'currencyId' => array('field_static_value' => 'RUR'),
                    'picture' => array('field_id' => (int)$catalogType->fields['images']->id),
                    'pickup' => array('field_static_value' => 1),
                    'delivery' => array('field_static_value' => 1),
                    'vendorCode' => array('field_id' => (int)$catalogType->fields['article']->id),
                    'name' => array('field_id' => 'name'),
                    'description' => array('field_id' => 'description'),
                    'rec' => (int)$catalogType->fields['related']->id,
                ),
                array(
                    array(
                        'param_name' => 'Спецпредложение',
                        'field_id' => (int)$catalogType->fields['spec']->id,
                        'field_callback' => "return \$x ? 'true' : 'false';"
                    )
                )
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
        $forms = $this->createForms($interfaces['__raas_shop_order_notify']);
        $catalogType = $this->createMaterialType();
        $ajax = array_shift(Page::getSet(array('where' => "urn = 'ajax' AND pid = " . (int)$this->Site->id)));
        $catalog = $this->createCatalog($catalogType);
        $menus = $this->createMenus($catalog);
        $cartTypes = $this->createCartTypes($catalogType, $catalogType->fields['price'], $forms['order']);
        $orderStatuses = $this->createOrderStatuses();
        $loaders = $this->createLoaders($catalogType, $catalog);
        $cart = $this->createCart($cartTypes['cart'], $ajax);
        $favorites = $this->createFavorites($cartTypes['favorites'], $ajax);
        $this->adjustBlocks();
        $yml = $this->createYandexMarket($catalogType, $catalog);
    }
}
