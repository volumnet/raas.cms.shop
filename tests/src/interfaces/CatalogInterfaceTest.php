<?php
/**
 * Файл теста интерфейса каталога
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use SOME\Pages;
use RAAS\Application;
use RAAS\CMS\Block;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\Page;
use RAAS\CMS\Page_Field;
use RAAS\CMS\Snippet;

/**
 * Класс теста интерфейса каталога
 * @covers RAAS\CMS\Shop\CatalogInterface
 */
class CatalogInterfaceTest extends BaseTest
{
    public static $tables = [
        'cms_blocks',
        'cms_blocks_material',
        'cms_blocks_material_filter',
        'cms_blocks_material_sort',
        'cms_material_types',
        'cms_pages',
        'cms_fields',
        'cms_materials',
        'cms_data',
        'cms_materials_pages_assoc',
    ];

    /**
     * Очистка после выполнения теста
     */
    public static function tearDownAfterClass(): void
    {
        $filename = CatalogFilter::getDefaultFilename(4, false);
        if (is_file($filename)) {
            @unlink($filename);
        }
        $filename = CatalogFilter::getDefaultFilename(4, true);
        if (is_file($filename)) {
            @unlink($filename);
        }
        parent::tearDownAfterClass();
    }

    /**
     * Тест получения поля $_GET параметров для фильтрации
     */
    public function testGetFilterParams()
    {
        $block = Block::spawn(34);
        $block->filter = [
            ['var' => 'name', 'relation' => 'LIKE', 'field' => 'name'],
            ['var' => 'article', 'relation' => 'LIKE', 'field' => '25'],
            ['var' => 'old_price_f', 'relation' => '>=', 'field' => '34'],
            ['var' => 'old_price_t', 'relation' => '<=', 'field' => '34'],
            ['var' => 'av', 'relation' => '=', 'field' => '31'],
            ['var' => '-step', 'relation' => '=', 'field' => '33'],
            ['var' => 'search_string', 'relation' => 'FULLTEXT', 'field' => '25'],
        ];
        $materialType = new Material_Type(4);
        $page = new Page(18);
        $get = [
            'name' => 'test',
            'article' => 'testarticle',
            'step' => 3,
            'av' => 1,
            'price_from' => 10000,
            'price_to' => 30000,
            'old_price_f' => 20000,
            'old_price_t' => 40000,
            'videos_like' => 'youtube'
        ];
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getFilterParams($block, $catalogFilter, $get);

        $this->assertEquals([
            'article_like' => 'testarticle',
            'available' => 1,
            'price_from' => 10000,
            'price_to' => 30000,
            'price_old_from' => 20000,
            'price_old_to' => 40000,
            'videos_like' => 'youtube'
        ], $result);
    }


    /**
     * Тест установки (если нет) фильтра каталога
     */
    public function testSetCatalogFilter()
    {
        self::tearDownAfterClass();
        $block = Block::spawn(34);
        $block->filter = [
            ['var' => 'name', 'relation' => 'LIKE', 'field' => 'name'],
            ['var' => 'article', 'relation' => 'LIKE', 'field' => '25'],
            ['var' => 'old_price_f', 'relation' => '>=', 'field' => '34'],
            ['var' => 'old_price_t', 'relation' => '<=', 'field' => '34'],
            ['var' => 'av', 'relation' => '=', 'field' => '31'],
            ['var' => '-step', 'relation' => '=', 'field' => '33'],
        ];
        $block->params = 'withChildrenGoods=1';
        $page = new Page(18);
        $get = [
            'name' => 'test',
            'article' => 'testarticle',
            'step' => 3,
            'av' => 1,
            'price_from' => 10000,
            'price_to' => 30000,
            'old_price_f' => 20000,
            'old_price_t' => 40000,
            'videos_like' => 'youtube'
        ];
        $interface = new CatalogInterface();

        $interface->setCatalogFilter($block, $page, $get);

        $this->assertInstanceOf(CatalogFilter::class, $page->catalogFilter);
        $this->assertEquals(true, $page->catalogFilter->withChildrenGoods);
        $this->assertEquals([4, 5], $page->catalogFilter->materialTypesIds);
        $this->assertEquals([
            '25' => ['like' => 'testarticle'],
            '31' => [1],
            '26' => ['from' => 10000, 'to' => 30000],
            '34' => ['from' => 20000, 'to' => 40000],
            '28' => ['like' => 'youtube'],
        ], $page->catalogFilter->filter);
    }


    /**
     * Тест получения данных по товару для подстановки в шаблоны мета-тегов
     */
    public function testGetItemMetadata()
    {
        $material = new Material(10);
        $material->fields['related']->addValue(11);
        $material->fields['related']->addValue(12);
        $interface = new CatalogInterface();

        $result = $interface->getItemMetadata($material);

        $this->assertEquals(10, $result['id']);
        $this->assertEquals('Товар 1', $result['name']);
        $this->assertEquals('tovar_1', $result['urn']);
        $this->assertEquals('/catalog/category1/category11/category111/tovar_1/', $result['url']);
        $this->assertEquals('f4dbdf21', $result['article']);
        $this->assertEquals('83620', $result['price']);
        $this->assertEquals('https://www.youtube.com/watch?v=YVgc2PQd_bo', $result['videos']);
        $this->assertEquals('Товар 2', $result['related']);
        $this->assertEmpty($result['images']);
    }


    /**
     * Тест метода getPageMetadata()
     */
    public function testGetPageMetadata()
    {
        $field = new Page_Field(['datatype' => 'material', 'urn' => 'testmaterial']);
        $field->commit();
        $page = new Page(17);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $page->catalogFilter = $catalogFilter;
        $page->fields['testmaterial']->addValue(14); // Товар 5
        $interface = new CatalogInterface();

        $result = $interface->getPageMetadata($page);

        $this->assertEquals(17, $result['id']);
        $this->assertEquals('Категория 11', $result['name']);
        $this->assertEquals('category11', $result['urn']);
        $this->assertEquals('/catalog/category1/category11/', $result['url']);

        $this->assertEquals(10, $result['counter']);
        $this->assertEquals(0, $result['selfCounter']);
        $this->assertEquals(5609, $result['price_from']);
        $this->assertEquals(85812, $result['price_to']);
        $this->assertEquals('Товар 5', $result['testmaterial']);

        Page_Field::delete($field);
    }


    /**
     * Тест получения рекурсивного шаблона метатегов для товара из категорий
     */
    public function testGetMetaTemplate()
    {
        $page = new Page(18);
        $page->parent->meta_title_template = 'aaa';
        $interface = new CatalogInterface();

        $result = $interface->getMetaTemplate($page, 'meta_title_template');

        $this->assertEquals('aaa', $result);
    }


    /**
     * Тест получения рекурсивного шаблона метатегов для товара из категорий
     * случай с пустым тегом
     */
    public function testGetMetaTemplateWithEmpty()
    {
        $page = new Page(18);
        $interface = new CatalogInterface();

        $result = $interface->getMetaTemplate($page, 'meta_title_template');

        $this->assertEquals('', $result);
    }


    /**
     * Тест установки тегов страницы
     */
    public function testSetPageMetatags()
    {
        $block = Block::spawn(34);
        $block->params = 'metaTemplates=template';
        $page = new Page(18);
        $page->parent->meta_title_template = 'Купить {{name}}';
        $material = new Material(10);
        $interface = new CatalogInterface();

        $result = $interface->setPageMetatags($page, $material, $block);

        $this->assertEquals('Купить Товар 1', $page->meta_title);
    }


    /**
     * Тест метода setListMetatage
     */
    public function testSetListMetatags()
    {
        $block = Block::spawn(34);
        $block->params = 'listMetaTemplates=template';
        $page = new Page(18);
        $page->parent->meta_title_template = 'Купить - {{name}}';
        $interface = new CatalogInterface();

        $result = $interface->setListMetatags($page, $block);

        $this->assertEquals('Купить - Категория 111', $page->meta_title);
    }


    /**
     * Тест получения SQL-инструкций по материалам
     */
    public function testGetMaterialsSQL()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $page->catalogFilter = $catalogFilter;
        $interface = new CatalogInterface();
        $sqlFrom = $sqlWhere = $sqlWhereBind = [];
        $filterIds = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];

        $result = $interface->getMaterialsSQL($block, $page, $sqlFrom, $sqlWhere, $sqlWhereBind, $filterIds);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals(["tM.id IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"], $sqlWhere);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $sqlWhereBind);
    }


    /**
     * Тест метода getFilterIds()
     */
    public function testGetFilterIds()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getFilterIds($block, [], $catalogFilter);

        $this->assertEquals([18, 14, 13, 16, 15, 17, 11, 12, 10, 19], $result);
    }


    /**
     * Тест метода getFilterIds() - случай с полнотекстовым поиском
     */
    public function testGetFilterIdsWithFullText()
    {
        $block = Block::spawn(34);
        $block->filter = [
            ['var' => 'article', 'relation' => 'FULLTEXT', 'field' => 25], // 25 - артикул
        ];
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface($block);

        $result = $interface->getFilterIds($block, ['article' => 'fa005713'], $catalogFilter);

        $this->assertEquals([18], $result);
    }


    /**
     * Тест метода getFilteringItemSQL()
     */
    public function testGetFilteringItemSQL()
    {
        $interface = new CatalogInterface();

        $result = $interface->getFilteringItemSQL('tM.name', '=', 'aaa');

        $this->assertEquals(["(tM.name = ?)", 'aaa'], $result);
    }


    /**
     * Тест метода getFilteringItemSQL() - случай с полнотекстовым поиском
     */
    public function testGetFilteringItemSQLWithFullText()
    {
        $interface = new CatalogInterface();

        $result = $interface->getFilteringItemSQL('tM.name', 'FULLTEXT', 'aaa');

        $this->assertEquals([], $result);
    }


    /**
     * Тест метода getFullTextIds()
     */
    public function testGetFullTextIds()
    {
        $block = Block::spawn(34);
        $interface = new CatalogInterface($block);

        $result = $interface->getFullTextIds(
            [['var' => 'article', 'relation' => 'FULLTEXT', 'field' => 25]],
            ['article' => 'fa005713']
        );

        $this->assertEquals([18], $result);
    }


    /**
     * Тест метода getFullTextIds() - случай с пустой поисковой строкой
     */
    public function testGetFullTextIdsWithEmptySearchString()
    {
        $block = Block::spawn(34);
        $interface = new CatalogInterface($block);

        $result = $interface->getFullTextIds(
            [['var' => 'article', 'relation' => 'FULLTEXT', 'field' => 25]],
            ['article' => ''],
        );

        $this->assertNull($result);
    }


    /**
     * Тест метода getRawFilterIds()
     */
    public function testGetRawFilterIds()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getRawFilterIds($block, [], $catalogFilter);

        $this->assertEquals([18, 14, 13, 16, 15, 17, 11, 12, 10, 19], $result);
    }


    /**
     * Тест метода getRawFilterIds() - случай с произвольной сортировкой
     */
    public function testGetRawFilterIdsWithCustomSort()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'price', 'field' => 26, 'relation' => 'asc'], // 26 - стоимость
        ];
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getRawFilterIds($block, ['sort' => 'price', 'order' => 'desc'], $catalogFilter);

        $this->assertEquals([19, 10, 12, 11, 17, 15, 16, 13, 14, 18], $result);
    }


    /**
     * Тест метода getRawFilterIds() - случай с произвольной случайной сортировкой
     */
    public function testGetRawFilterIdsWithCustomRandomSort()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'randomsort', 'field' => 'random', 'relation' => 'asc!'], // 26 - стоимость
        ];
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getRawFilterIds($block, ['sort' => 'randomsort'], $catalogFilter);

        $this->assertNotEquals([19, 10, 12, 11, 17, 15, 16, 13, 14, 18], $result); // Условно, теоретически может выпадать, но вероятность мала
        $this->assertEqualsCanonicalizing([19, 10, 12, 11, 17, 15, 16, 13, 14, 18], $result);
    }


    /**
     * Тест метода getRawFilterIds() - случай со стандартной случайной сортировкой
     */
    public function testGetRawFilterIdsWithDefaultRandomSort()
    {
        $block = Block::spawn(34);
        $block->sort_field_default = 'random';
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getRawFilterIds($block, [], $catalogFilter);

        $this->assertNotEquals([19, 10, 12, 11, 17, 15, 16, 13, 14, 18], $result); // Условно, теоретически может выпадать, но вероятность мала
        $this->assertEqualsCanonicalizing([19, 10, 12, 11, 17, 15, 16, 13, 14, 18], $result);
    }


    /**
     * Тест метода getRawFilterIds() - случай без стандартной сортировки
     */
    public function testGetRawFilterIdsWithoutDefaultSort()
    {
        $block = Block::spawn(34);
        $block->sort_field_default = '';
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page);
        $interface = new CatalogInterface();

        $result = $interface->getRawFilterIds($block, [], $catalogFilter);

        $this->assertEqualsCanonicalizing([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result); // Порядок не важен
    }


    /**
     * Тест получения SQL-инструкций по материалам - случай с отсутствием подходящих товаров
     */
    public function testGetMaterialsSQLWithNoIds()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $materialType = new Material_Type(4);
        $catalogFilter = CatalogFilter::loadOrBuild($materialType);
        $catalogFilter->apply($page, ['article' => 'aaa']);
        $page->catalogFilter = $catalogFilter;
        $interface = new CatalogInterface();
        $sqlFrom = $sqlWhere = $sqlWhereBind = [];

        $result = $interface->getMaterialsSQL($block, $page, $sqlFrom, $sqlWhere, $sqlWhereBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals(["0"], $sqlWhere);
        $this->assertEquals([], $sqlWhereBind);
    }


    /**
     * Тест получения SQL-инструкций по сортировке - кастомное поле по GET
     */
    public function testGetOrderSQL()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
            ['var' => 'byprice', 'relation' => 'desc', 'field' => 26],
        ];
        $get = ['sort' => 'byprice', 'order' => 'asc'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $filterIds = [18, 14, 13, 16, 15, 17, 11, 12, 10, 19];
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $filterIds);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("FIELD(tM.id, 18, 14, 13, 16, 15, 17, 11, 12, 10, 19)", trim($sqlSort));
        $this->assertEquals("", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - случай с пустой сортировкой
     */
    public function testGetOrderSQLWithNoIds()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
            ['var' => 'byprice', 'relation' => 'desc', 'field' => 26],
        ];
        $get = ['sort' => 'byprice', 'order' => 'asc'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("", trim($sqlSort));
        $this->assertEquals("", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - кастомное поле по GET в обратном порядке
     */
    public function testGetOrderSQLWithReverseSorting()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
            ['var' => 'byprice', 'relation' => 'desc', 'field' => 26],
        ];
        $get = ['sort' => 'byprice', 'order' => 'desc'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL(
            $block,
            $get,
            $sqlFrom,
            $sqlFromBind,
            $sqlSort,
            $sqlOrder,
            [19, 10, 12, 11, 17, 15, 16, 13, 14, 18]
        );
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("FIELD(tM.id, 19, 10, 12, 11, 17, 15, 16, 13, 14, 18)", trim($sqlSort));
        $this->assertEquals("", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - случай с нативным полем по GET
     */
    public function testGetOrderSQLWithNativeField()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->order_var_name = 'order';
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
            ['var' => 'byprice', 'relation' => 'desc', 'field' => 26],
        ];
        $get = ['sort' => 'byname', 'order' => 'asc'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("tM.name", trim($sqlSort));
        $this->assertEquals("DESC", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - случай с сортировкой по умолчанию (кастомное поле)
     */
    public function testGetOrderSQLWithDefaultSorting()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->sort_field_default = 26;
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
        ];
        $get = ['sort' => 'bydate'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $filterIds = [18, 14, 13, 16, 15, 17, 11, 12, 10, 19];
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $filterIds);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("FIELD(tM.id, 18, 14, 13, 16, 15, 17, 11, 12, 10, 19)", trim($sqlSort));
        $this->assertEquals("", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - случай с сортировкой по умолчанию (кастомное поле) и отсутствием товаров
     */
    public function testGetOrderSQLWithDefaultSortingAndNoIds()
    {
        $block = Block::spawn(34);
        $block->sort_var_name = 'sort';
        $block->sort_field_default = 26;
        $block->sort = [
            ['var' => 'byname', 'relation' => 'desc!', 'field' => 'name'],
        ];
        $get = ['sort' => 'bydate'];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("", trim($sqlSort));
        $this->assertEquals("", trim($sqlOrder));
    }


    /**
     * Тест получения SQL-инструкций по сортировке - случай с сортировкой по умолчанию (нативное поле)
     */
    public function testGetOrderSQLWithDefaultNativeSorting()
    {
        $block = Block::spawn(34);
        $block->sort_field_default = 'name';
        $get = [];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals([], $sqlFromBind);
        $this->assertEquals("tM.name", trim($sqlSort));
        $this->assertEquals("ASC", trim($sqlOrder));
    }


    /**
     * Тест получения частей SQL-выражения
     */
    public function testGetSQLParts()
    {
        $block = Block::spawn(34);
        $block->sort_field_default = 26;
        $page = new Page(15);
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;

        $ids = $interface->getIdsList($block, $page, []); // Для получения filterIds
        $result = $interface->getSQLParts($block, $page, []);
        foreach (['from', 'where'] as $key) {
            $result[$key] = array_map(function ($x) {
                return trim(preg_replace('/\\s+/umis', ' ', $x));
            }, $result[$key]);
        }
        foreach (['sort', 'order'] as $key) {
            $result[$key] = trim($result[$key]);
        }
        $result['bind'] = array_map(function ($x) {
            return (int)$x;
        }, $result['bind']);

        $this->assertEquals([
            'tA' => "LEFT JOIN cms_access_materials_cache AS tA ON tA.material_id = tM.id AND tA.uid = ?",
        ], $result['from']);
        $this->assertEquals([
             "(tA.allow OR (tA.allow IS NULL))",
             "tM.id IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        ], $result['where']);
        $this->assertEquals("FIELD(tM.id, 18, 14, 13, 16, 15, 17, 11, 12, 10, 19)", $result['sort']);
        $this->assertEquals("", $result['order']);
        $this->assertEqualsCanonicalizing([0, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result['bind']);
    }


    /**
     * Тест метода getSQLParts - случай с нативными полями
     */
    public function testGetSQLPartsWithNativeFilter()
    {
        $block = Block::spawn(34);
        $block->sort_field_default = 26;
        $block->filter = [
            ['var' => 'name', 'relation' => 'LIKE', 'field' => 'name'],
        ];
        $page = new Page(15);
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;

        $ids = $interface->getIdsList($block, $page, []); // Для получения filterIds
        $result = $interface->getSQLParts($block, $page, ['name' => 'товар 1']);
        foreach (['from', 'where'] as $key) {
            $result[$key] = array_map(function ($x) {
                return trim(preg_replace('/\\s+/umis', ' ', $x));
            }, $result[$key]);
        }

        $this->assertEquals("((tM.name LIKE ?))", $result['where']['name']);
        $this->assertContains('%товар 1%', $result['bind']);
    }


    /**
     * Провайдер данных для метода testCommentsFilterFunction
     * @return array <pre><code>array<[
     *     Material Комментарий
     *     Material Товар, по которому фильтруем
     *     bool Ожидаемое значение
     * ]></code></pre>
     */
    public function commentsFilterFunctionDataProvider(): array
    {
        return [
            [new Material(23), new Material(12), true],
            [new Material(24), new Material(10), false],
        ];
    }


    /**
     * Проверка метода commentsFilterFunction
     * @param Material $comment Комментарий
     * @param Material $item Товар, по которому фильтруем
     * @param bool $expected Ожидаемое значение
     * @dataProvider commentsFilterFunctionDataProvider
     */
    public function testCommentsFilterFunction(Material $comment, Material $item, bool $expected)
    {
        $interface = new CatalogInterface();

        $result = $interface->commentsFilterFunction($comment, $item);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест обработки блоков комментариев к товару
     */
    public function testProcessCommentsWithInterfaceSnippet()
    {
        $snippet = new Snippet(['urn' => 'test', 'description' => '<' . '?php
            $interface = new RAAS\CMS\MaterialInterface($Block, $Page, $_GET, $_POST, $_COOKIE, $_SESSION, $_SERVER, $_FILES);
            return $interface->process();
        ']);
        $snippet->commit();

        $commentsBlock = Block::spawn(51); // Отзывы к товарам (список)
        $commentsBlockInterfaceId = $commentsBlock->interface_id;
        $commentsBlock->interface_id = $snippet->id;
        $commentsBlock->interface_classname = '';
        $commentsBlock->commit();

        $commentsSnippet = new Snippet(56); // Отзывы к товарам
        $commentsSnippet->description = '<div class="goods-comments"></div>';
        $commentsSnippet->commit();

        $block = Block::spawn(34); // Каталог продукции
        $page = new Page(18); // Категория 111
        $material = new Material(12); // Товар 3 - к нему есть отзывы
        $interface = new CatalogInterface();

        $result = $interface->processComments($block, $page, $material);

        $this->assertEquals(52, $result['commentFormBlock']->id);
        $this->assertEquals(51, $result['commentsListBlock']->id);
        $this->assertCount(3, $result['comments']);
        $this->assertEquals('Отзыв 1', $result['comments'][0]->name);
        $this->assertStringContainsString('class="goods-comments', $result['commentsListText']);

        $commentsBlock->interface_id = 0;
        $commentsBlock->interface_classname = MaterialInterface::class;
        $commentsBlock->commit();

        Snippet::delete($snippet);
    }


    /**
     * Тест обработки блоков комментариев к товару - случай указания класса интерфейса
     */
    public function testProcessCommentsWithInterfaceClassname()
    {
        $commentsBlock = Block::spawn(51); // Отзывы к товарам (список)
        $commentsBlockInterfaceId = $commentsBlock->interface_id;
        $commentsBlock->interface_id = 0;
        $commentsBlock->interface_classname = MaterialInterface::class;
        $commentsBlock->commit();

        $block = Block::spawn(34); // Каталог продукции
        $page = new Page(18); // Категория 111
        $material = new Material(12); // Товар 3 - к нему есть отзывы
        $interface = new CatalogInterface();

        $commentsSnippet = new Snippet(56); // Отзывы к товарам
        $commentsSnippet->description = '<div class="goods-comments"></div>';
        $commentsSnippet->commit();

        $result = $interface->processComments($block, $page, $material);

        $this->assertEquals(52, $result['commentFormBlock']->id);
        $this->assertEquals($commentsBlock->id, $result['commentsListBlock']->id);
        $this->assertCount(3, $result['comments']);
        $this->assertEquals('Отзыв 1', $result['comments'][0]->name);
        $this->assertStringContainsString('class="goods-comments', $result['commentsListText']);

        $commentsBlock->interface_id = $commentsBlockInterfaceId;
        $commentsBlock->commit();
    }


    /**
     * Тест добавления товара в список просмотренных (в начало) с записью в сессию
     */
    public function testProcessVisited()
    {
        $session = ['visited' => []];
        $interface = new CatalogInterface();
        $item = new Material(10);

        $visited = $interface->processVisited($item, $session);

        $this->assertEquals([10], $visited);
        $this->assertEquals([10], $session['visited']);

        $item = new Material(11);

        $visited = $interface->processVisited($item, $session);

        $this->assertEquals([11, 10], $visited);
        $this->assertEquals([11, 10], $session['visited']);

        $item = new Material(12);

        $visited = $interface->processVisited($item, $session);

        $this->assertEquals([12, 11, 10], $visited);
        $this->assertEquals([12, 11, 10], $session['visited']);

        $item = new Material(11);

        $visited = $interface->processVisited($item, $session);

        $this->assertEquals([11, 12, 10], $visited);
        $this->assertEquals([11, 12, 10], $session['visited']);
    }


    /**
     * Тест определения, ведется ли сейчас поиск - случай, когда поиск не ведется
     */
    public function testIsSearch()
    {
        $block = Block::spawn(34);
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->isSearch($block, $catalogFilter, []);

        $this->assertFalse($result);
    }


    /**
     * Тест определения, ведется ли сейчас поиск - случай, когда поиск ведется по нативному полю
     */
    public function testIsSearchWithNativeFilter()
    {
        $block = Block::spawn(34);
        $block->filter = [
            ['var' => 'name', 'relation' => 'LIKE', 'field' => 'name'],
        ];
        $interface = new CatalogInterface();
        $get = ['name' => 'aaa'];
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), $get);

        $result = $interface->isSearch($block, $catalogFilter, $get);

        $this->assertTrue($result);
    }


    /**
     * Тест определения, ведется ли сейчас поиск - случай с полнотекстовым поиском
     */
    public function testIsSearchWithFullTextFilter()
    {
        $block = Block::spawn(34);
        $block->filter = [
            ['var' => 'search_string', 'relation' => 'FULLTEXT', 'field' => 25],
        ];
        $interface = new CatalogInterface();
        $get = ['search_string' => 'aaa'];
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), $get);

        $result = $interface->isSearch($block, $catalogFilter, $get);

        $this->assertTrue($result);
    }


    /**
     * Тест определения, ведется ли сейчас поиск - случай, когда поиск ведется по кастомному полю
     */
    public function testIsSearchWithCustomFilter()
    {
        $block = Block::spawn(34);
        $interface = new CatalogInterface();
        $get = ['article' => 'aaa'];
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), $get);

        $result = $interface->isSearch($block, $catalogFilter, $get);

        $this->assertTrue($result);
    }


    /**
     * Тест обработки одного материала
     */
    public function testProcessMaterial()
    {
        $form = new Form(2);
        $form->material_type = 1;
        $form->commit();
        $block = Block::spawn(34);
        $block->params = 'metaTemplates=template&withChildrenGoods=1&commentFormBlock=52&commentsListBlock=51&faqFormBlock=53&faqListBlock=50';
        $page = new Page(18);
        $page->initialURL = '/catalog/category1/category11/category111/tovar_3/';
        $page->parent->meta_title_template = 'Купить {{name}}';
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;
        $item = new Material(12);
        $interface = new CatalogInterface();

        $commentsSnippet = new Snippet(56); // Отзывы к товарам
        $commentsSnippet->description = '<div class="goods-comments"></div>';
        $commentsSnippet->commit();
        $faqSnippet = new Snippet(55); // Вопрос-ответ к товарам
        $faqSnippet->description = '<div class="goods-faq"></div>';
        $faqSnippet->commit();

        $result = $interface->processMaterial($block, $page, $item, [], []);

        $this->assertEquals('Товар 3', $page->name);
        $this->assertTrue($item->proceed);
        $this->assertEquals($item, $result['Item']);
        $this->assertEquals(11, $result['prev']->id);
        $this->assertEquals(10, $result['next']->id);

        $this->assertEquals(52, $result['commentFormBlock']->id);
        $this->assertEquals(51, $result['commentsListBlock']->id);
        $this->assertCount(3, $result['comments']);
        $this->assertEquals('Отзыв 1', $result['comments'][0]->name);
        $this->assertStringContainsString('class="goods-comments', $result['commentsListText']);
        $this->assertEquals(2, $result['rating']);
        $this->assertEquals(53, $result['faqFormBlock']->id);
        $this->assertEquals(50, $result['faqListBlock']->id);
        $this->assertCount(3, $result['faq']);
        $this->assertEquals('Вопрос 1', $result['faq'][0]->name);
        $this->assertStringContainsString('class="goods-faq', $result['faqListText']);

        $this->assertEquals('Купить Товар 3', $page->meta_title);
        $this->assertEquals(['visited' => [12]], $interface->session);
    }


    /**
     * Тест обработки одного материала - случай с неправильным legacy-адресом
     */
    public function testProcessMaterialWithInvalidLegacyAddress()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $page->initialURL = '/tovar_3/';
        $item = new Material(12);
        $interface = new CatalogInterface();

        $result = $interface->processMaterial($block, $page, $item, [], []);

        $this->assertEmpty($result);
    }


    /**
     * Тест обработки списка материалов
     */
    public function testProcessList()
    {
        $block = Block::spawn(34);
        $block->rows_per_page = 3;
        $page = new Page(15);
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;
        $interface = new CatalogInterface();

        $result = $interface->processList($block, $page, ['page' => 1]);
        // var_dump(array_map(function ($x) {
        //     return $x->id;
        // }, $result['Set'])); exit;

        $this->assertCount(3, $result['Set']);
        $this->assertEquals(18, $result['Set'][0]->id);
        $this->assertEquals(14, $result['Set'][1]->id);
        $this->assertEquals(13, $result['Set'][2]->id);
        $this->assertEquals(1, $result['Pages']->page);
        $this->assertEquals(3, $result['Pages']->rows_per_page);
        $this->assertEquals(10, $result['Pages']->count);
        $this->assertEquals('price', $result['sort']);
        $this->assertEquals('asc', $result['order']);
        $this->assertEquals(4, $result['MType']->id);
        $this->assertCount(3, $result['subcats']);
        $this->assertEquals(16, $result['subcats'][0]->id);
        $this->assertEquals(10, $result['subcats'][0]->counter);
        $this->assertFalse($result['doSearch']);
    }


    /**
     * Тест метода getList()
     */
    public function testGetList()
    {
        $block = Block::spawn(34);
        $pages = new Pages(1, 3);
        $page = new Page(15);
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;
        $interface = new CatalogInterface();

        $result = $interface->getList($block, $page, [], $pages);

        $this->assertCount(3, $result);
        $this->assertEquals(18, $result[0]->id);
        $this->assertEquals(14, $result[1]->id);
        $this->assertEquals(13, $result[2]->id);
    }


    /**
     * Тест метода getList() - случай без использования фильтра каталога
     */
    public function testGetListWithoutFilter()
    {
        $block = Block::spawn(34);
        $pages = new Pages(1, 3);
        $page = new Page(15);
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply($page, []);
        $page->catalogFilter = $catalogFilter;
        $interface = new CatalogInterface();
        $interface->useFilterIds = false;

        $result = $interface->getList($block, $page, [], $pages);

        $this->assertCount(3, $result);
        $this->assertEquals(18, $result[0]->id);
        $this->assertEquals(14, $result[1]->id);
        $this->assertEquals(13, $result[2]->id);
    }


    /**
     * Тест обработки интерфейса - случай со списком
     */
    public function testProcessWithList()
    {
        $block = Block::spawn(34);
        $page = new Page(18);
        $interface = new CatalogInterface($block, $page);

        $result = $interface->process();

        $this->assertCount(10, $result['Set']);
        $this->assertEquals(18, $result['Set'][0]->id);
        $this->assertEquals(14, $result['Set'][1]->id);
        $this->assertEquals(13, $result['Set'][2]->id);
        $this->assertEquals(1, $result['Pages']->page);
        $this->assertEquals(20, $result['Pages']->rows_per_page);
        $this->assertEquals(10, $result['Pages']->count);
        $this->assertEquals('price', $result['sort']);
        $this->assertEquals('asc', $result['order']);
        $this->assertEquals(4, $result['MType']->id);
        $this->assertCount(0, $result['subcats']);
        $this->assertFalse($result['doSearch']);
        $this->assertInstanceOf(CatalogFilter::class, $page->catalogFilter);
    }


    /**
     * Тест обработки интерфейса - случай с одним материалом
     */
    public function testProcessWithMaterial()
    {
        $form = new Form(2);
        $form->material_type = 1;
        $form->commit();
        $block = Block::spawn(34);
        $block->params = 'commentFormBlock=7&commentsListBlock=13&metaTemplates=template';
        $page = new Page(18);
        $page->initialURL = '/catalog/category1/category11/category111/tovar_3/';
        $page->Material = new Material(12);
        $page->parent->meta_title_template = 'Купить {{name}}';
        $interface = new CatalogInterface($block, $page);

        $result = $interface->process();

        $this->assertEquals('Товар 3', $page->name);
        $this->assertTrue($page->Material->proceed);
        $this->assertEquals($page->Material, $result['Item']);
        $this->assertEquals(11, $result['prev']->id);
        $this->assertEquals(10, $result['next']->id);
        $this->assertEquals('Купить Товар 3', $page->meta_title);
        $this->assertEquals(['visited' => [12]], $interface->session);
    }
}
