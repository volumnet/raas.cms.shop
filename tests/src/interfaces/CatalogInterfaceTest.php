<?php
/**
 * Файл теста интерфейса каталога
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;

/**
 * Класс теста интерфейса каталога
 */
class CatalogInterfaceTest extends BaseDBTest
{
    /**
     * Очистка после выполнения теста
     */
    public static function tearDownAfterClass()
    {
        $filename = CatalogFilter::getDefaultFilename(4);
        @unlink($filename);
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

        $result = $interface->getMaterialsSQL($block, $page, $sqlFrom, $sqlWhere, $sqlWhereBind);

        $this->assertEquals([], $sqlFrom);
        $this->assertEquals(["tM.id IN (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"], $sqlWhere);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $sqlWhereBind);
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
     * Тест получения SQL-инструкции по полнотекстовому поиску
     * @todo
     */
    public function testGetFullTextFilteringSQL()
    {
        $sqlFrom = $sqlFromBind = $sqlWhere = $sqlWhereBind = [];
        $filter = [
            ['var' => 'search_string', 'relation' => 'FULLTEXT', 'field' => 25],
        ];
        $get = [
            'search_string' => 'Товар',
        ];
        $interface = new CatalogInterface();

        $interface->getFullTextFilteringSQL($sqlFrom, $sqlFromBind, $sqlWhere, $sqlWhereBind, $filter, $get);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);
        $sqlWhere = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlWhere);

        $this->assertEquals([
            't25' => "LEFT JOIN cms_data AS `t25` ON `t25`.pid = tM.id AND `t25`.fid = ?",
        ], $sqlFrom);
        $this->assertEquals([25], $sqlFromBind);
        $this->assertEquals([
            'search_string' => "(((tM.name LIKE ?) OR (t25.value LIKE ?)))",
        ], $sqlWhere);
        $this->assertEquals(['%Товар%', '%Товар%'], $sqlWhereBind);
    }


    /**
     * Тест получения SQL-инструкций по фильтрации для одной записи фильтрации
     */
    public function testGetFilteringSQL()
    {
        $sqlFrom = $sqlFromBind = $sqlWhere = $sqlWhereBind = [];
        $filter = [
            ['var' => 'name', 'relation' => 'LIKE', 'field' => 'name'],
            ['var' => 'article', 'relation' => 'LIKE', 'field' => 25],
            ['var' => 'search_string', 'relation' => 'FULLTEXT', 'field' => 25],
            ['var' => 'price_from', 'relation' => '>=', 'field' => 26]
        ];
        $get = [
            'name' => 'Товар',
            'search_string' => 'Товар',
            'article' => 'AAA',
            'price_from' => '1000'
        ];
        $interface = new CatalogInterface();

        $interface->getFilteringSQL($sqlFrom, $sqlFromBind, $sqlWhere, $sqlWhereBind, $filter, $get);
        $sqlFrom = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlFrom);
        $sqlFromBind = array_map(function ($x) {
            return (int)$x;
        }, $sqlFromBind);
        $sqlWhere = array_map(function ($x) {
            return trim(preg_replace('/\\s+/umis', ' ', $x));
        }, $sqlWhere);

        $this->assertEquals([
            't25' => "LEFT JOIN cms_data AS `t25` ON `t25`.pid = tM.id AND `t25`.fid = ?",
        ], $sqlFrom);
        $this->assertEquals([25], $sqlFromBind);
        $this->assertEquals([
            'name' => "((tM.name LIKE ?))",
            'search_string' => "(((tM.name LIKE ?) OR (t25.value LIKE ?)))",
        ], $sqlWhere);
        $this->assertEquals(['%Товар%', '%Товар%', '%Товар%'], $sqlWhereBind);
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
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), ['article' => 'aaa']);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), ['article' => 'aaa']);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $block->sort_var_name = 'sort';
        $get = [];
        $sqlFrom = $sqlFromBind = [];
        $sqlSort = $sqlOrder = '';
        $interface = new CatalogInterface();
        $catalogFilter = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, false);
        $catalogFilter->apply(new Page(15), []);

        $result = $interface->getOrderSQL($block, $get, $sqlFrom, $sqlFromBind, $sqlSort, $sqlOrder, $catalogFilter);
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
        $this->assertEquals([0, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result['bind']);
    }


    /**
     * Тест обработки блоков комментариев к товару
     */
    public function testProcessComments()
    {
        $form = new Form(2);
        $form->material_type = 1;
        $form->commit();
        $block = Block::spawn(34);
        $block->params = 'commentFormBlock=7&commentsListBlock=13';
        $interface = new CatalogInterface();

        $result = $interface->processComments($block);

        $this->assertEquals(7, $result['commentFormBlock']->id);
        $this->assertEquals(13, $result['commentsListBlock']->id);
        $this->assertCount(3, $result['comments']);
        $this->assertEquals('Клиент-ориентированный подход', $result['comments'][0]->name);
        $this->assertContains('class="features-main-item', $result['commentsListText']);
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

        $result = $interface->processMaterial($block, $page, $item, [], []);

        $this->assertEquals('Товар 3', $page->name);
        $this->assertTrue($item->proceed);
        $this->assertEquals($item, $result['Item']);
        $this->assertEquals(11, $result['prev']->id);
        $this->assertEquals(13, $result['next']->id);

        $this->assertEquals(52, $result['commentFormBlock']->id);
        $this->assertEquals(51, $result['commentsListBlock']->id);
        $this->assertCount(3, $result['comments']);
        $this->assertEquals('Отзыв 1', $result['comments'][0]->name);
        $this->assertContains('class="goods-reviews', $result['commentsListText']);
        $this->assertEquals(2, $result['rating']);
        $this->assertEquals(53, $result['faqFormBlock']->id);
        $this->assertEquals(50, $result['faqListBlock']->id);
        $this->assertCount(3, $result['faq']);
        $this->assertEquals('Вопрос 1', $result['faq'][0]->name);
        $this->assertContains('class="goods-faq', $result['faqListText']);

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

        $this->assertCount(3, $result['Set']);
        $this->assertEquals(10, $result['Set'][0]->id);
        $this->assertEquals(11, $result['Set'][1]->id);
        $this->assertEquals(12, $result['Set'][2]->id);
        $this->assertEquals(1, $result['Pages']->page);
        $this->assertEquals(3, $result['Pages']->rows_per_page);
        $this->assertEquals(10, $result['Pages']->count);
        $this->assertEquals('name', $result['sort']);
        $this->assertEquals('asc', $result['order']);
        $this->assertEquals(4, $result['MType']->id);
        $this->assertCount(3, $result['subcats']);
        $this->assertEquals(16, $result['subcats'][0]->id);
        $this->assertEquals(10, $result['subcats'][0]->counter);
        $this->assertFalse($result['doSearch']);
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
        $this->assertEquals(10, $result['Set'][0]->id);
        $this->assertEquals(11, $result['Set'][1]->id);
        $this->assertEquals(12, $result['Set'][2]->id);
        $this->assertEquals(1, $result['Pages']->page);
        $this->assertEquals(20, $result['Pages']->rows_per_page);
        $this->assertEquals(10, $result['Pages']->count);
        $this->assertEquals('name', $result['sort']);
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
        $this->assertEquals(13, $result['next']->id);
        $this->assertEquals('Купить Товар 3', $page->meta_title);
        $this->assertEquals(['visited' => [12]], $interface->session);
    }
}
