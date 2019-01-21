<?php
/**
 * Файл теста класса фильтра каталога
 */
namespace RAAS\CMS\Shop;

use SOME\Pages;
use SOME\Singleton;
use RAAS\Exception;
use RAAS\Timer;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

/**
 * Класс теста фильтра каталога
 */
class CatalogFilterTest extends BaseDBTest
{
    /**
     * Тест получения всех свойств
     */
    public function testGetAllProperties()
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getAllProperties([3, 4], [25, 'testfield', new Material_Field(34)]);
        $ids = array_map(function ($x) {
            return $x->id;
        }, $result);

        $this->assertNotContains(25, $ids);
        $this->assertNotContains(47, $ids);
        $this->assertNotContains(34, $ids);
        $this->assertContains(31, $ids);
        $this->assertContains(16, $ids);
        $this->assertNotContains(27, $ids);
        $this->assertNotContains(29, $ids);
        $this->assertNotContains(35, $ids);
        $this->assertNotContains(17, $ids);
        $this->assertNotContains(48, $ids);
    }


    /**
     * Тест получения всех доступных ID# товаров
     */
    public function testGetCatalogGoodsIds()
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getCatalogGoodsIds([3, 4]);
        sort($result);

        $this->assertEquals([7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result);
    }


    /**
     * Тест получения всех доступных ID# товаров - случай с пустым набором типов
     */
    public function testGetCatalogGoodsIdsWithNoMaterialTypes()
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getCatalogGoodsIds([]);

        $this->assertEquals([], $result);
    }


    /**
     * Тест получения соответствие ID# страниц их родительским ID#
     */
    public function testGetPagesParents()
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getPagesParents();

        $this->assertEquals(16, $result[21]);
        $this->assertEquals(3, $result[6]);
        $this->assertEquals(0, $result[1]);
        $this->assertEmpty($result[0]);
    }


    /**
     * Тест получения исходной таблицы свойств
     */
    public function testBuildCache()
    {
        $filter = new CatalogFilter(new Material_Type());
        $mTypesIds = [4, 5];
        $fieldsIds = [26, 30, 31, 32];
        $materialsIds = [19, 18, 17, 16, 15, 14, 13, 12, 11, 10];

        $result = $filter->buildCache($mTypesIds, $fieldsIds, $materialsIds);
        $resultPriceIs83620 = array_unique($result[26][83620]);
        $resultPriceIs67175 = array_unique($result[26][67175]);
        $resultIsNotAvailable = array_unique($result[31][0]);
        $resultIsAvailable = array_unique($result[31][1]);
        $resultOnPage1 = array_unique($result['pages_ids'][1]);
        sort($resultPriceIs83620);
        sort($resultPriceIs67175);
        sort($resultIsNotAvailable);
        sort($resultIsAvailable);
        sort($resultOnPage1);

        $this->assertEquals([10], $resultPriceIs83620);
        $this->assertEquals([11], $resultPriceIs67175);
        $this->assertEquals([10, 14, 18], $resultIsNotAvailable);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $resultIsAvailable);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $resultOnPage1);
    }


    /**
     * Тест переноса товаров из дочерних категорий в родительские
     */
    public function testBubbleUpGoods()
    {
        $filter = new CatalogFilter(new Material_Type());
        $pagesMapping = [
            1 => [1, 2, 3],
            11 => [4, 5, 6],
            111 => [7, 8, 9],
            12 => [10, 11, 12],
            121 => [13, 14, 15],
        ];
        $parents = [1 => 0, 11 => 1, 111 => 11, 12 => 1, 121 => 12];

        $result = $filter->bubbleUpGoods($pagesMapping, $parents);

        $this->assertEquals([1, 11, 111, 12, 121], array_keys($result));
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], $result[1]);
        $this->assertEquals([4, 5, 6, 7, 8, 9], $result[11]);
        $this->assertEquals([7, 8, 9], $result[111]);
        $this->assertEquals([10, 11, 12, 13, 14, 15], $result[12]);
        $this->assertEquals([13, 14, 15], $result[121]);
    }


    /**
     * Тест проверки, есть ли у фильтра отмеченные опции
     */
    public function testGetFilterHasCheckedOptions()
    {
        $filter = new CatalogFilter(new Material_Type());
        $filterData = ['available' => 1, 'price_from' => 0, 'price_to' => 100000];

        $result = $filter->getFilterHasCheckedOptions($filterData);

        $this->assertTrue($result);
    }


    /**
     * Тест проверки, есть ли у фильтра отмеченные опции - случай с пустым фильтром
     */
    public function testGetFilterHasCheckedOptionsWithNoCheckedOptions()
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getFilterHasCheckedOptions([]);

        $this->assertFalse($result);
    }


    /**
     * Тест применения ограничения по каталогу
     */
    public function testApplyCatalog()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                '2' => [2, 4, 6, 8, 10],
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
                '6' => [6],
            ],
            '1' => [
                '1' => [2, 4, 6, 8, 10],
                '2' => [3, 6, 9],
                '3' => [4, 8],
                '4' => [5, 10],
                '5' => [6],
            ],
            '2' => [
                '1' => [3, 6, 9],
                '2' => [4, 8],
                '3' => [5, 10],
                '4' => [6],
            ],
            '3' => [
                '1' => [4, 8],
                '2' => [5, 10],
                '3' => [6],
            ],
            '4' => [
                '1' => [5, 10],
                '2' => [6],
            ],
            '5' => [
                '1' => [6],
            ],
            'pages_ids' => [
                '1' => [1, 2, 3],
                '2' => [4, 5, 6],
                '3' => [7, 8, 9, 10]
            ]
        ];

        $result = $filter->applyCatalog($propsMapping, 3);

        $this->assertEquals([
            '0' => ['1' => [7, 8, 9, 10], '2' => [8, 10], '3' => [9], '4' => [8], '5' => [10]],
            '1' => ['1' => [8, 10], '2' => [9], '3' => [8], '4' => [10]],
            '2' => ['1' => [9], '2' => [8], '3' => [10]],
            '3' => ['1' => [8], '2' => [10]],
            '4' => ['1' => [10]],
            '5' => [],
        ], $result);
    }


    /**
     * Тест применения фильтр к маппингу
     */
    public function testApplyFilter()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                '2' => [2, 4, 6, 8, 10],
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
                '6' => [6],
            ],
            '1' => [
                '1' => [2, 4, 6, 8, 10],
                '2' => [3, 6, 9],
                '3' => [4, 8],
                '4' => [5, 10],
                '5' => [6],
            ],
            '2' => [
                '1' => [3, 6, 9],
                '2' => [4, 8],
                '3' => [5, 10],
                '4' => [6],
            ],
            '3' => [
                '1' => [4, 8],
                '2' => [5, 10],
                '3' => [6],
            ],
            '4' => [
                '1' => [5, 10],
                '2' => [6],
            ],
            '5' => [
                '1' => [6],
            ]
        ];
        $filterData = ['0' => [2, 3], '1' => [2]];

        $result = $filter->applyFilter($propsMapping, $filterData);

        $this->assertEquals([
            '0' => ['2' => [2, 4, 6, 8, 10], '3' => [3, 6, 9]],
            '1' => ['2' => [3, 6, 9]],
        ], $result);
    }


    /**
     * Тест применения фильтр к маппингу - случай с фильтром от/до
     */
    public function testApplyFilterWithFromTo()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                '2' => [2, 4, 6, 8, 10],
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
                '6' => [6],
            ],
            '1' => [
                '1' => [2, 4, 6, 8, 10],
                '2' => [3, 6, 9],
                '3' => [4, 8],
                '4' => [5, 10],
                '5' => [6],
            ],
            '2' => [
                '1' => [3, 6, 9],
                '2' => [4, 8],
                '3' => [5, 10],
                '4' => [6],
            ],
            '3' => [
                '1' => [4, 8],
                '2' => [5, 10],
                '3' => [6],
            ],
            '4' => [
                '1' => [5, 10],
                '2' => [6],
            ],
            '5' => [
                '1' => [6],
            ]
        ];
        $filterData = ['0' => ['from' => 3, 'to' => 5], '1' => [2]];

        $result = $filter->applyFilter($propsMapping, $filterData);
        $this->assertEquals([
            '0' => [
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
            ],
            '1' => ['2' => [3, 6, 9]],
        ], $result);
    }


    /**
     * Тест применения фильтр к маппингу - случай с фильтром like
     */
    public function testApplyFilterWithLike()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                'sdjkfl;jksd;' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                's;dkfjweopji' => [2, 4, 6, 8, 10],
                'd;fjxcviope' => [3, 6, 9],
                'sldfppwer' => [4, 8],
                'sd;dkfjw' => [5, 10],
                'sd;vxpodft' => [6],
            ],
            '1' => [
                'sdf;oibvb' => [2, 4, 6, 8, 10],
                'sgfhvbghjgji' => [3, 6, 9],
                'xcvxcvbt;gkljg' => [4, 8],
                'dfkljgdkh' => [5, 10],
                'xcvxikoprf' => [6],
            ],
        ];
        $filterData = ['0' => ['like' => 'dkfjw']];

        $result = $filter->applyFilter($propsMapping, $filterData);
        $this->assertEquals([
            '0' => [
                's;dkfjweopji' => [2, 4, 6, 8, 10],
                'sd;dkfjw' => [5, 10],
            ],
        ], $result);
    }


    /**
     * Тест получения списка доступных ID# товаров по свойствам, если бы применялись только ограничения этого свойства
     */
    public function testReduceMappingToGoodsIds()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                '2' => [2, 4, 6, 8, 10],
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
                '6' => [6],
            ],
            '1' => [
                '1' => [2, 4, 6, 8, 10],
                '2' => [3, 6, 9],
                '3' => [4, 8],
                '4' => [5, 10],
                '5' => [6],
            ],
            '2' => [
                '1' => [3, 6, 9],
                '2' => [4, 8],
                '3' => [5, 10],
                '4' => [6],
            ],
            '3' => [
                '1' => [4, 8],
                '2' => [5, 10],
                '3' => [6],
            ],
            '4' => [
                '1' => [5, 10],
                '2' => [6],
            ],
            '5' => [
                '1' => [6],
            ]
        ];

        $result = $filter->reduceMappingToGoodsIds($propsMapping);

        $this->assertEquals([
            '0' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            '1' => [2, 4, 6, 8, 10, 3, 9, 5],
            '2' => [3, 6, 9, 4, 8, 5, 10],
            '3' => [4, 8, 5, 10, 6],
            '4' => [5, 10, 6],
            '5' => [6],
        ], $result);
    }


    /**
     * Тест получения списка доступных ID# товаров по свойствам,
     * если бы для каждого свойства применялись ограничения всех остальных свойств кроме него
     */
    public function testApplyCrossFilter()
    {
        $filter = new CatalogFilter(new Material_Type());
        $goodsIdsMapping = [
            '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            '2' => [2, 4, 6, 8, 10],
            '3' => [3, 6, 9],
        ];
        $categoryGoodsIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

        $result = $filter->applyCrossFilter($goodsIdsMapping, $categoryGoodsIds);

        $this->assertEquals([
            '1' => [6],
            '2' => [3, 6, 9],
            '3' => [2, 4, 6, 8, 10],
            '' => [6]
        ], $result);
    }


    /**
     * Тестирование получения маппинга сортировки
     */
    public function testGetSortMapping()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '0' => [
                '2' => [10, 8, 6, 4, 2],
                '4' => [4, 8, 11],
                '3' => [3, 6, 9, 12],
            ],
        ];
        $goodsIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];

        $result = $filter->getSortMapping($propsMapping, $goodsIds);

        $this->assertEquals([
            '0' => [10, 8, 6, 4, 2, 3, 9, 12, 11],
            '' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
        ], $result);
    }


    /**
     * Тест получения доступных свойств
     */
    public function testGetAvailableProperties()
    {
        $filter = new CatalogFilter(new Material_Type());
        $propsMapping = [
            '26' => [
                '1' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
                '2' => [2, 4, 6, 8, 10],
                '3' => [3, 6, 9],
                '4' => [4, 8],
                '5' => [5, 10],
                '6' => [6],
            ],
            '30' => [
                '1' => [2, 4, 6, 8, 10],
                '2' => [3, 6, 9],
                '3' => [4, 8],
                '4' => [5, 10],
                '5' => [6],
            ],
            '31' => [
                '1' => [3, 6, 9],
                '2' => [4, 8],
                '3' => [5, 10],
                '4' => [6],
            ],
            '32' => [
                '1' => [4, 8],
                '2' => [5, 10],
                '3' => [6],
            ],
            '33' => [
                '1' => [5, 10],
                '2' => [6],
            ],
            '34' => [
                '1' => [6],
            ]
        ];
        $filterData = ['26' => ['from' => 3, 'to' => 5], '30' => [2]];
        $crossFilter = [
            '26' => [3, 6, 9],
            '30' => [3, 4, 5, 6, 8, 9, 10],
            '' => [3, 4, 5, 6, 8, 9, 10]
        ];

        $result = $filter->getAvailableProperties($propsMapping, $crossFilter, $filterData);
        $result = array_map(function ($fieldData) {
            return array_map(function ($valueData) {
                $newValueData = $valueData;
                $newValueData['prop'] = $valueData['prop']->name;
                return $newValueData;
            }, $fieldData);
        }, $result);

        $this->assertEquals('Стоимость', $result['26']['1']['prop']);
        $this->assertTrue($result['26']['1']['enabled']);
        $this->assertFalse($result['26']['5']['enabled']);
        $this->assertEquals('Спецпредложение', $result['30']['1']['prop']);
        $this->assertEquals(1, $result['30']['1']['value']);
        $this->assertEquals(true, $result['30']['1']['doRich']);
        $this->assertTrue($result['30']['2']['checked']);
        $this->assertFalse($result['30']['1']['checked']);
    }


    /**
     * Тест построения кэша
     */
    public function testBuild()
    {
        $filter = new CatalogFilter(new Material_Type(4), true, ['article', new Material_Field(33)]);

        $filter->build();

        $this->assertEquals([4, 5], $filter->materialTypesIds);
        $this->assertEquals(['article', 33], $filter->ignoredFields);
        $this->assertEquals('Стоимость', $filter->properties[26]->name);
        $this->assertEquals('Стоимость', $filter->propertiesByURNs['price']->name);
        $this->assertNull($filter->properties[25]);
        $this->assertNull($filter->propertiesByURNs['article']);
        $this->assertTrue($filter->withChildrenGoods);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $filter->propsMapping['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $filter->propsMapping['31'][1]);
    }


    /**
     * Тест составления фильтра по переменным окружения
     */
    public function testGetFilter()
    {
        $filter = new CatalogFilter(new Material_Type(4));
        $filterData = [
            'price_old_from' => 10000,
            'price_old_to' => 20000,
            'article' => ['aaa', 'bbb', 'ccc'],
            'videos_like' => 'youtube',
            'available' => 1
        ];

        $filter->build();
        $result = $filter->getFilter($filterData);

        $this->assertEquals([
            '34' => ['from' => 10000, 'to' => 20000],
            '25' => ['aaa', 'bbb', 'ccc'],
            '28' => ['like' => 'youtube'],
            '31' => [1]
        ], $result);
    }


    /**
     * Тест составления фильтра по переменным окружения - случай с пустым значением
     */
    public function testGetFilterWithEmptyValue()
    {
        $filter = new CatalogFilter(new Material_Type(4));
        $filterData = [
            'price_old_from' => 10000,
            'price_old_to' => 20000,
            'article' => ['', '0'],
            'videos_like' => 'youtube',
            'available' => 1
        ];

        $filter->build();
        $result = $filter->getFilter($filterData);

        $this->assertEquals([
            '34' => ['from' => 10000, 'to' => 20000],
            '25' => ['0'],
            '28' => ['like' => 'youtube'],
            '31' => [1]
        ], $result);
    }


    /**
     * Тест составления фильтра по переменным окружения - случай, когда фильтр не инициализирован
     * @expectedException Exception
     */
    public function testGetFilterWithNoInit()
    {
        $filter = new CatalogFilter(new Material_Type(4));
        $filterData = [
            'price_from' => 10000,
            'price_to' => 20000,
            'article' => ['aaa', 'bbb', 'ccc'],
            'available' => 1
        ];

        $result = $filter->getFilter($filterData);
    }


    /**
     * Тест применения фильтра и каталога
     */
    public function testApply()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);

        $this->assertEquals(15, $filter->catalog->id);
        $this->assertEquals([
            '26' => ['from' => 10000, 'to' => 60000],
            '31' => [1]
        ], $filter->filter);
        $this->assertTrue($filter->filterHasCheckedOptions);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $filter->categoryGoodsIds);
        $this->assertEquals([16, 13, 15], $filter->sortMapping[25]);
        $this->assertEquals([13, 16, 15], $filter->sortMapping[26]);
        $this->assertEquals([13, 15, 16], $filter->sortMapping['']);
        $this->assertTrue($filter->availableProperties[31][1]['checked']);
        $this->assertEquals('В наличии', $filter->availableProperties[31][1]['prop']->name);
        $this->assertNull($filter->nonExistingProp);
    }


    /**
     * Тест получения переменных окружения по фильтру
     */
    public function testGetURLParamsFromFilter()
    {
        $filter = new CatalogFilter(new Material_Type());
        $filterData = [
            '26' => ['from' => 10000, 'to' => 60000],
            '31' => [1],
            '25' => ['6dd28e9b', '84b12bae', '1db87a14'],
            '28' => ['like' => 'youtube'],
        ];

        $result = $filter->getURLParamsFromFilter($filterData);

        $this->assertEquals(10000, $result['price_from']);
        $this->assertEquals(60000, $result['price_to']);
        $this->assertEquals(1, $result['available']);
        $this->assertEquals(['6dd28e9b', '84b12bae', '1db87a14'], $result['article']);
        $this->assertEquals('youtube', $result['videos_like']);
    }


    /**
     * Тест получения переменных окружения по фильтру - случай, когда данные берутся из фильтра
     */
    public function testGetURLParamsFromFilterWithFilterData()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => '10000',
            'price_to' => '60000',
            'available' => '1',
            'article' => ['6dd28e9b', '84b12bae', '1db87a14'],
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getURLParamsFromFilter();

        $this->assertEquals(10000, $result['price_from']);
        $this->assertEquals(60000, $result['price_to']);
        $this->assertEquals(1, $result['available']);
        $this->assertEquals(['6dd28e9b', '84b12bae', '1db87a14'], $result['article']);
    }


    /**
     * Тест получения канонического URL из фильтра
     */
    public function testGetCanonicalURLFromFilter()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => '10000',
            'price_to' => '60000',
            'available' => '1',
            'article' => ['6dd28e9b', '84b12bae', '1db87a14'],
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getCanonicalURLFromFilter();

        $this->assertEquals(
            '/catalog/?price_from=10000&price_to=60000&available=1&article%5B0%5D=6dd28e9b&article%5B1%5D=84b12bae&article%5B2%5D=1db87a14',
            $result
        );
    }


    /**
     * Тест получения канонического URL из фильтра - случай с отсутствием параметров
     */
    public function testGetCanonicalURLFromFilterWithNoFilter()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);

        $filter->build();
        $filter->apply($catalog);
        $result = $filter->getCanonicalURLFromFilter();

        $this->assertEquals('/catalog/', $result);
    }


    /**
     * Тест получения канонического URL из фильтра - случай, когда фильтр не инициализирован
     * @expectedException Exception
     */
    public function testGetCanonicalURLFromFilterWithNoInit()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filterData = [
            '26' => ['from' => 10000, 'to' => 60000],
            '31' => [1],
            '25' => ['6dd28e9b', '84b12bae', '1db87a14'],
        ];

        $filter->build();
        $result = $filter->getCanonicalURLFromFilter($filterData);
    }


    /**
     * Тест получения ID# товаров с сортировкой
     */
    public function testGetIds()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getIds('article');

        $this->assertEquals([16, 13, 15], $result);
    }


    /**
     * Тест получения ID# товаров с сортировкой - случай обратного порядка
     */
    public function testGetIdsWithReverse()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getIds('article', -1);

        $this->assertEquals([15, 13, 16], $result);
    }


    /**
     * Тест получения ID# товаров с сортировкой - случай когда фильтр не инициализирован
     * @expectedException Exception
     */
    public function testGetIdsWithFilterNotApplied()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);

        $result = $filter->getIds('article');
    }


    /**
     * Тест получения ID# товаров с сортировкой - случай сортировки по умолчанию (по порядку отображения)
     */
    public function testGetIdsWithDefaultSort()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getIds();

        $this->assertEquals([13, 15, 16], $result);
    }


    /**
     * Тест получения материалов с сортировкой
     */
    public function testGetMaterials()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $pages = new Pages(2, 1);
        $result = $filter->getMaterials($pages, 'article');

        $this->assertEquals(13, $result[0]->id);
    }


    /**
     * Тест получения материалов с сортировкой - случай без постраничной разбивки
     */
    public function testGetMaterialsWithoutPagination()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getMaterials(null, 'article');

        $this->assertEquals(16, $result[0]->id);
        $this->assertEquals(13, $result[1]->id);
        $this->assertEquals(15, $result[2]->id);
    }


    /**
     * Тест получения материалов с сортировкой - случай с пустой выборкой
     */
    public function testGetMaterialsWithEmptySet()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = ['price_from' => 1, 'price_to' => 2];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getMaterials();

        $this->assertEquals([], $result);
    }


    /**
     * Тест экспорта
     */
    public function testExport()
    {
        $filter = new CatalogFilter(new Material_Type(4), true, ['article', new Material_Field(33)]);

        $filter->build();
        $result = $filter->export();

        $this->assertEquals('Каталог продукции', $result['materialType']['name']);
        $this->assertEquals([4, 5], $result['materialTypesIds']);
        $this->assertEquals(['article', 33], $result['ignoredFields']);
        $this->assertEquals('Стоимость', $result['properties'][26]['name']);
        $this->assertNull($result['properties'][25]);
        $this->assertNull($result['propertiesByURNs']['article']);
        $this->assertTrue($result['withChildrenGoods']);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result['propsMapping']['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $result['propsMapping']['31'][1]);
    }


    /**
     * Тест импорта
     */
    public function testImport()
    {
        $filterData = [
            'materialType' => ['id' => 4, 'name' => 'Каталог продукции'],
            'withChildrenGoods' => true,
            'ignoredFields' => ['article', 33],
            'materialTypesIds' => [4, 5],
            'properties' => [26 => ['id' => 26, 'name' => 'Стоимость', 'urn' => 'price']],
            'catalogGoodsIds' => [10, 11, 12, 13, 14, 15, 16, 17, 18, 19],
            'propsMapping' => [
                '31' => [1 => [11, 12, 13, 15, 16, 17, 19]],
                'pages_ids' => [15 => [10, 11, 12, 13, 14, 15, 16, 17, 18, 19]],
            ],
        ];

        $result = CatalogFilter::import($filterData);

        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals(['article', 33], $result->ignoredFields);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertNull($result->properties[25]);
        $this->assertNull($result->propertiesByURNs['article']);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result->propsMapping['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $result->propsMapping['31'][1]);
    }


    /**
     * Тест импорта - случай с некорректными данными
     * @expectedException Exception
     */
    public function testImportWithInvalidData()
    {
        $filterData = ['aaa', 'bbb', 'ccc'];

        $result = CatalogFilter::import($filterData);
    }


    /**
     * Тест получения пути к файлу по умолчанию
     */
    public function testGetDefaultFilename()
    {
        $result = CatalogFilter::getDefaultFilename(4);

        $this->assertEquals(Package::i()->cacheDir . '/system/catalogfilter4.php', $result);
    }


    /**
     * Тест сохранения файла
     */
    public function testSave()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.php';

        $filter->build();
        $filter->save();

        $this->assertFileExists($filename);

        $result = include $filename;
        $this->assertEquals('Каталог продукции', $result['materialType']['name']);
        $this->assertEquals([4, 5], $result['materialTypesIds']);
        $this->assertEquals('Стоимость', $result['properties'][26]['name']);
        $this->assertTrue($result['withChildrenGoods']);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result['propsMapping']['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $result['propsMapping']['31'][1]);
    }


    /**
     * Тест сохранения файла - случай с некорректным именем файла
     * @expectedException Exception
     */
    public function testSaveWithInvalidFilepath()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filename = __DIR__ . '/../coverage';

        $filter->build();
        $filter->save($filename);
    }


    /**
     * Тест загрузки
     */
    public function testLoad()
    {
        $result = CatalogFilter::load(new Material_Type(4));

        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result->propsMapping['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $result->propsMapping['31'][1]);
    }


    /**
     * Тест загрузки - случай с несуществующим файлом
     * @expectedException Exception
     */
    public function testLoadWithInvalidFilepath()
    {
        $result = CatalogFilter::load(new Material_Type(4), $this->getResourcesDir . '/aaa.php');
    }


    /**
     * Тест загрузки или построения
     */
    public function testLoadOrBuild()
    {
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.php';
        @unlink($filename);

        $this->assertFileNotExists($filename);

        $result = CatalogFilter::loadOrBuild(new Material_Type(4), true);

        $this->assertFileExists($filename);
        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals([10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $result->propsMapping['pages_ids'][15]);
        $this->assertEquals([11, 12, 13, 15, 16, 17, 19], $result->propsMapping['31'][1]);
    }


    /**
     * Тест счетчика товаров
     */
    public function testCount()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->count(new Page(17));

        $this->assertEquals(10, $result);
    }


    /**
     * Тест счетчика товара - без дочерних категорий
     */
    public function testCountWithoutChildrenCats()
    {
        $filter = new CatalogFilter(new Material_Type(4), false);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->count(new Page(17));

        $this->assertEquals(0, $result);
    }
}
