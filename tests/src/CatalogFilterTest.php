<?php
/**
 * Файл теста класса фильтра каталога
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use SOME\File;
use SOME\Pages;
use SOME\Singleton;
use RAAS\Application;
use RAAS\Exception;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

/**
 * Класс теста фильтра каталога
 */
#[CoversClass(CatalogFilter::class)]
class CatalogFilterTest extends BaseTest
{
    public static $tables = [
        'cms_data',
        'cms_dictionaries',
        'cms_fields',
        'cms_material_types',
        'cms_materials',
        'cms_materials_pages_assoc',
        'cms_pages',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $material = new Material(10);
        $material->fields['testfield']->deleteValues();
        $material->fields['testfield']->addValue('value1');
    }

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
        $this->assertContains(35, $ids);
        $this->assertNotContains(27, $ids);
        $this->assertNotContains(29, $ids);
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

        $this->assertEquals(
            [
                '7' => 7,
                '8' => 8,
                '9' => 9,
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result
        );
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
        $this->assertEmpty($result[0] ?? null);
    }


    /**
     * Тест метода getAvailabilityOrderByValue
     * @param mixed $value Исходное значение поля "Наличие"
     * @param mixed $expected Ожидаемое значение
     */
    #[TestWith([1, '1'])]
    #[TestWith([2, '1'])]
    #[TestWith([0, '0'])]
    #[TestWith(['', '0'])]
    public function testGetAvailabilityOrderByValue($value, $expected)
    {
        $filter = new CatalogFilter(new Material_Type());

        $result = $filter->getAvailabilityOrderByValue($value);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест получения исходной таблицы свойств
     */
    public function testBuildCache()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filter->useAvailabilityOrder = 'available';
        $mTypesIds = [4, 5];
        $fieldsIds = [26, 30, 31, 32];
        $materialsIds = [19, 18, 17, 16, 15, 14, 13, 12, 11, 10];

        $filter->build(); // Чтобы сформировалось свойства propertiesByURNs
        $result = $filter->buildCache($mTypesIds, $fieldsIds, $materialsIds);
        $resultPriceIs83620 = array_unique($result[26][83620]);
        $resultPriceIs67175 = array_unique($result[26][67175]);
        $resultIsNotAvailable = array_unique($result[31][0]);
        $resultIsAvailable = array_unique($result[31][1]);
        $resultOnPage1 = array_unique($result['pages_ids'][1]);

        $this->assertEquals(['10' => 10], $resultPriceIs83620);
        $this->assertEquals(['11' => 11], $resultPriceIs67175);
        $this->assertEquals(
            ['10' => 10, '14' => 14, '18' => 18],
            $resultIsNotAvailable
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $resultIsAvailable
        );
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $resultOnPage1
        );
    }


    /**
     * Тест переноса товаров из дочерних категорий в родительские
     */
    public function testBubbleUpGoods()
    {
        $filter = new CatalogFilter(new Material_Type());
        $pagesMapping = [
            1 => ['1' => 1, '2' => 2, '3' => 3],
            11 => ['4' => 4, '5' => 5, '6' => 6],
            111 => ['7' => 7, '8' => 8, '9' => 9],
            12 => ['10' => 10, '11' => 11, '12' => 12],
            121 => ['13' => 13, '14' => 14, '15' => 15],
        ];
        $parents = [1 => 0, 11 => 1, 111 => 11, 12 => 1, 121 => 12];

        $result = $filter->bubbleUpGoods($pagesMapping, $parents);

        $this->assertEquals([1, 11, 111, 12, 121], array_keys($result));
        $this->assertEquals(
            [
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '7' => 7,
                '8' => 8,
                '9' => 9,
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15
            ],
            $result[1]
        );
        $this->assertEquals(
            ['4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9],
            $result[11]
        );
        $this->assertEquals(
            ['7' => 7, '8' => 8, '9' => 9],
            $result[111]
        );
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15
            ],
            $result[12]
        );
        $this->assertEquals(
            ['13' => 13, '14' => 14, '15' => 15],
            $result[121]
        );
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
                '1' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '3' => ['3' => 3, '6' => 6, '9' => 9],
                '4' => ['4' => 4, '8' => 8],
                '5' => ['5' => 5, '10' => 10],
                '6' => ['6' => 6],
            ],
            '1' => [
                '1' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '2' => ['3' => 3, '6' => 6, '9' => 9],
                '3' => ['4' => 4, '8' => 8],
                '4' => ['5' => 5, '10' => 10],
                '5' => ['6' => 6],
            ],
            '2' => [
                '1' => ['3' => 3, '6' => 6, '9' => 9],
                '2' => ['4' => 4, '8' => 8],
                '3' => ['5' => 5, '10' => 10],
                '4' => ['6' => 6],
            ],
            '3' => [
                '1' => ['4' => 4, '8' => 8],
                '2' => ['5' => 5, '10' => 10],
                '3' => ['6' => 6],
            ],
            '4' => [
                '1' => ['5' => 5, '10' => 10],
                '2' => ['6' => 6],
            ],
            '5' => [
                '1' => ['6' => 6],
            ],
            'pages_ids' => [
                '1' => ['1' => 1, '2' => 2, '3' => 3],
                '2' => ['4' => 4, '5' => 5, '6' => 6],
                '3' => ['7' => 7, '8' => 8, '9' => 9, '10' => 10]
            ]
        ];

        $result = $filter->applyCatalog($propsMapping, 3);

        $this->assertEquals([
            '0' => [
                '1' => ['7' => 7, '8' => 8, '9' => 9, '10' => 10],
                '2' => ['8' => 8, '10' => 10],
                '3' => ['9' => 9],
                '4' => ['8' => 8],
                '5' => ['10' => 10]
            ],
            '1' => [
                '1' => ['8' => 8, '10' => 10],
                '2' => ['9' => 9],
                '3' => ['8' => 8],
                '4' => ['10' => 10]
            ],
            '2' => [
                '1' => ['9' => 9],
                '2' => ['8' => 8],
                '3' => ['10' => 10]
            ],
            '3' => [
                '1' => ['8' => 8],
                '2' => ['10' => 10]
            ],
            '4' => [
                '1' => ['10' => 10]
            ],
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
                '1' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '3' => ['3' => 3, '6' => 6, '9' => 9],
                '4' => ['4' => 4, '8' => 8],
                '5' => ['5' => 5, '10' => 10],
                '6' => ['6' => 6],
            ],
            '1' => [
                '1' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '2' => ['3' => 3, '6' => 6, '9' => 9],
                '3' => ['4' => 4, '8' => 8],
                '4' => ['5' => 5, '10' => 10],
                '5' => ['6' => 6],
            ],
            '2' => [
                '1' => ['3' => 3, '6' => 6, '9' => 9],
                '2' => ['4' => 4, '8' => 8],
                '3' => ['5' => 5, '10' => 10],
                '4' => ['6' => 6],
            ],
            '3' => [
                '1' => ['4' => 4, '8' => 8],
                '2' => ['5' => 5, '10' => 10],
                '3' => ['6' => 6],
            ],
            '4' => [
                '1' => ['5' => 5, '10' => 10],
                '2' => ['6' => 6],
            ],
            '5' => [
                '1' => ['6' => 6],
            ]
        ];
        $filterData = ['0' => [2, 3], '1' => [2]];

        $result = $filter->applyFilter($propsMapping, $filterData);

        $this->assertEquals([
            '0' => [
                '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '3' => ['3' => 3, '6' => 6, '9' => 9]
            ],
            '1' => [
                '2' => ['3' => 3, '6' => 6, '9' => 9]
            ],
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
                '1' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '3' => ['3' => 3, '6' => 6, '9' => 9],
                '4' => ['4' => 4, '8' => 8],
                '5' => ['5' => 5, '10' => 10],
                '6' => ['6' => 6],
            ],
            '1' => [
                '1' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '2' => ['3' => 3, '6' => 6, '9' => 9],
                '3' => ['4' => 4, '8' => 8],
                '4' => ['5' => 5, '10' => 10],
                '5' => ['6' => 6],
            ],
            '2' => [
                '1' => ['3' => 3, '6' => 6, '9' => 9],
                '2' => ['4' => 4, '8' => 8],
                '3' => ['5' => 5, '10' => 10],
                '4' => ['6' => 6],
            ],
            '3' => [
                '1' => ['4' => 4, '8' => 8],
                '2' => ['5' => 5, '10' => 10],
                '3' => ['6' => 6],
            ],
            '4' => [
                '1' => ['5' => 5, '10' => 10],
                '2' => ['6' => 6],
            ],
            '5' => [
                '1' => ['6' => 6],
            ]
        ];
        $filterData = ['0' => ['from' => 3, 'to' => 5], '1' => [2]];

        $result = $filter->applyFilter($propsMapping, $filterData);
        $this->assertEquals([
            '0' => [
                '3' => ['3' => 3, '6' => 6, '9' => 9],
                '4' => ['4' => 4, '8' => 8],
                '5' => ['5' => 5, '10' => 10],
            ],
            '1' => ['2' => ['3' => 3, '6' => 6, '9' => 9]],
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
                'sdjkfl;jksd;' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                's;dkfjweopji' => [
                    '2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10
                ],
                'd;fjxcviope' => ['3' => 3, '6' => 6, '9' => 9],
                'sldfppwer' => ['4' => 4, '8' => 8],
                'sd;dkfjw' => ['5' => 5, '10' => 10],
                'sd;vxpodft' => ['6' => 6],
            ],
            '1' => [
                'sdf;oibvb' => [
                    '2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10
                ],
                'sgfhvbghjgji' => ['3' => 3, '6' => 6, '9' => 9],
                'xcvxcvbt;gkljg' => ['4' => 4, '8' => 8],
                'dfkljgdkh' => ['5' => 5, '10' => 10],
                'xcvxikoprf' => ['6' => 6],
            ],
        ];
        $filterData = ['0' => ['like' => 'dkfjw']];

        $result = $filter->applyFilter($propsMapping, $filterData);
        $this->assertEquals([
            '0' => [
                's;dkfjweopji' => [
                    '2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10
                ],
                'sd;dkfjw' => ['5' => 5, '10' => 10],
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
                '1' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '3' => ['3' => 3, '6' => 6, '9' => 9],
                '4' => ['4' => 4, '8' => 8],
                '5' => ['5' => 5, '10' => 10],
                '6' => ['6' => 6],
            ],
            '1' => [
                '1' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '2' => ['3' => 3, '6' => 6, '9' => 9],
                '3' => ['4' => 4, '8' => 8],
                '4' => ['5' => 5, '10' => 10],
                '5' => ['6' => 6],
            ],
            '2' => [
                '1' => ['3' => 3, '6' => 6, '9' => 9],
                '2' => ['4' => 4, '8' => 8],
                '3' => ['5' => 5, '10' => 10],
                '4' => ['6' => 6],
            ],
            '3' => [
                '1' => ['4' => 4, '8' => 8],
                '2' => ['5' => 5, '10' => 10],
                '3' => ['6' => 6],
            ],
            '4' => [
                '1' => ['5' => 5, '10' => 10],
                '2' => ['6' => 6],
            ],
            '5' => [
                '1' => ['6' => 6],
            ]
        ];

        $result = $filter->reduceMappingToGoodsIds($propsMapping);

        $this->assertEquals([
            '0' => [
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '7' => 7,
                '8' => 8,
                '9' => 9,
                '10' => 10
            ],
            '1' => [
                '2' => 2,
                '4' => 4,
                '6' => 6,
                '8' => 8,
                '10' => 10,
                '3' => 3,
                '9' => 9,
                '5' => 5
            ],
            '2' => [
                '3' => 3,
                '6' => 6,
                '9' => 9,
                '4' => 4,
                '8' => 8,
                '5' => 5,
                '10' => 10
            ],
            '3' => ['4' => 4, '8' => 8, '5' => 5, '10' => 10, '6' => 6],
            '4' => ['5' => 5, '10' => 10, '6' => 6],
            '5' => ['6' => 6],
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
            '1' => [
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '7' => 7,
                '8' => 8,
                '9' => 9,
                '10' => 10
            ],
            '2' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
            '3' => ['3' => 3, '6' => 6, '9' => 9],
        ];
        $categoryGoodsIds = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            '10' => 10
        ];

        $result = $filter->applyCrossFilter($goodsIdsMapping, $categoryGoodsIds);

        $this->assertEquals([
            '1' => ['6' => 6],
            '2' => ['3' => 3, '6' => 6, '9' => 9],
            '3' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
            '' => ['6' => 6]
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
                '2' => ['10' => 10, '8' => 8, '6' => 6, '4' => 4, '2' => 2],
                '4' => ['4' => 4, '8' => 8, '11' => 11],
                '3' => ['3' => 3, '6' => 6, '9' => 9, '12' => 12],
            ],
        ];
        $goodsIds = [
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            '10' => 10,
            '11' => 11,
            '12' => 12
        ];

        $result = $filter->getSortMapping($propsMapping, $goodsIds);

        // 2024-03-13, AVS: добавил недостающие товары без значения свойства (согласно 2022-12-08, чтобы не было
        // дополнительной фильтрации при сортировке. По идее такого быть не должно, т.к. все товары должны обладать
        // свойствами)
        $this->assertEquals([
            '0' => [
                '10' => 10,
                '8' => 8,
                '6' => 6,
                '4' => 4,
                '2' => 2,
                '3' => 3,
                '9' => 9,
                '12' => 12,
                '11' => 11,
                '1' => 1,
                '5' => 5,
                '7' => 7,
            ],
            '' => [
                '1' => 1,
                '2' => 2,
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '7' => 7,
                '8' => 8,
                '9' => 9,
                '10' => 10,
                '11' => 11,
                '12' => 12
            ],
        ], $result);
    }


    /**
     * Тест получения доступных свойств
     */
    public function testGetAvailableProperties()
    {
        $filter = new CatalogFilter(new Material_Type(4));
        $filter->build(); // Чтобы определились propertiesByURNs
        $propsMapping = [
            '26' => [
                '1' => [
                    '1' => 1,
                    '2' => 2,
                    '3' => 3,
                    '4' => 4,
                    '5' => 5,
                    '6' => 6,
                    '7' => 7,
                    '8' => 8,
                    '9' => 9,
                    '10' => 10
                ],
                '2' => [
                    '2' => 2,
                    '4' => 4,
                    '6' => 6,
                    '8' => 8,
                    '10' => 10
                ],
                '3' => [
                    '3' => 3,
                    '6' => 6,
                    '9' => 9
                ],
                '4' => [
                    '4' => 4,
                    '8' => 8
                ],
                '5' => [
                    '5' => 5,
                    '10' => 10
                ],
                '6' => [
                    '6' => 6
                ],
            ],
            '30' => [
                '1' => ['2' => 2, '4' => 4, '6' => 6, '8' => 8, '10' => 10],
                '2' => ['3' => 3, '6' => 6, '9' => 9],
                '3' => ['4' => 4, '8' => 8],
                '4' => ['5' => 5, '10' => 10],
                '5' => ['6' => 6],
            ],
            '31' => [
                '' => ['4' => 4],
                '1' => ['3' => 3, '6' => 6, '9' => 9],
                '2' => ['4' => 4, '8' => 8],
                '3' => ['5' => 5, '10' => 10],
                '4' => ['6' => 6],
            ],
            '32' => [
                '1' => ['4' => 4, '8' => 8],
                '2' => ['5' => 5, '10' => 10],
                '3' => ['6' => 6],
            ],
            '33' => [
                '1' => ['5' => 5, '10' => 10],
                '2' => ['6' => 6],
            ],
            '34' => [
                '1' => ['6' => 6],
            ],
            '47' => [
                'value1' => ['10' => 10]
            ],
        ];
        $filterData = ['26' => ['from' => 3, 'to' => 5], '30' => [2]];
        $crossFilter = [
            '26' => ['3' => 3, '6' => 6, '9' => 9],
            '30' => [
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '8' => 8,
                '9' => 9,
                '10' => 10
            ],
            '47' => [
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '8' => 8,
                '9' => 9,
                '10' => 10
            ],
            '' => [
                '3' => 3,
                '4' => 4,
                '5' => 5,
                '6' => 6,
                '8' => 8,
                '9' => 9,
                '10' => 10
            ]
        ];
        $numericFieldsIds = ['26' => 26, '32' => 32, '34' => 34];
        $richValues = ['47' => ['value1' => 'Запись 1']];

        $result = $filter->getAvailableProperties(
            $propsMapping,
            $crossFilter,
            $filterData,
            $numericFieldsIds,
            $richValues
        );
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
        $this->assertEquals('В наличии', $result['31']['0']['prop']);
        $this->assertEquals('Запись 1', $result['47']['value1']['doRich']);
        $this->assertEquals('Минимальное количество', $result['32']['1']['prop']);
        $this->assertEmpty($result['32']['1']['doRich'] ?? null);
    }


    /**
     * Тест построения кэша
     */
    public function testBuild()
    {
        $filter = new CatalogFilter(
            new Material_Type(4),
            true,
            ['article', new Material_Field(33)]
        );

        $filter->build();

        $this->assertEquals([4, 5], $filter->materialTypesIds);
        $this->assertEquals(['article', 33], $filter->ignoredFields);
        $this->assertEquals('Стоимость', $filter->properties[26]->name);
        $this->assertEquals('Стоимость', $filter->propertiesByURNs['price']->name);
        $this->assertNull($filter->properties[25] ?? null);
        $this->assertNull($filter->propertiesByURNs['article'] ?? null);
        $this->assertTrue($filter->withChildrenGoods);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $filter->propsMapping['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $filter->propsMapping['31'][1]
        );
        // 2024-03-13, AVS: добавил richValues для материалов (оно есть по именам)
        $this->assertEquals([
            '47' => [
                'value1' => 'Запись 1',
            ],
            '35' => [
                '18' => 'Товар 9',
                '19' => 'Товар 10',
                '10' => 'Товар 1',
                '11' => 'Товар 2',
                '16' => 'Товар 7',
                '15' => 'Товар 6',
                '12' => 'Товар 3',
                '17' => 'Товар 8',
                '13' => 'Товар 4',
                '14' => 'Товар 5',
            ]
        ], $filter->richValues);
        $this->assertEquals(
            ['26' => 26, '32' => 32, '34' => 34],
            $filter->numericFieldsIds
        );
        $this->assertEquals(
            [5609, 25712, 30450, 49651, 54096, 61245, 67175, 71013, 83620, 85812],
            array_keys($filter->propsMapping[26])
        );
        $this->assertEquals(10, $filter->counter[16]);
        $this->assertEquals(10, $filter->counter[24]);
        $this->assertEquals(0, $filter->selfCounter[16]);
        $this->assertEquals(10, $filter->selfCounter[24]);
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
            'available' => 0
        ];

        $filter->build();
        $result = $filter->getFilter($filterData);

        $this->assertEquals([
            '34' => ['from' => 10000, 'to' => 20000],
            '25' => ['aaa', 'bbb', 'ccc'],
            '28' => ['like' => 'youtube'],
            '31' => ['0', '']
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
     */
    public function testGetFilterWithNoInit()
    {
        $this->expectException(Exception::class);

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
     * Тест формирования rich-значений для свойств, у которых они заведомо могут
     * отличаться от сырых значений
     */
    public function testGetRichValues()
    {
        $filter = new CatalogFilter(new Material_Type(4));
        $propsMapping = [
            '25' => [
                'aaa' => ['1' => 1, '2' => 2, '3' => 3],
                'bbb' => ['1' => 1, '2' => 2, '3' => 3],
                'ccc' => ['1' => 1, '2' => 2, '3' => 3],
            ],
            '30' => [
                '0' => ['1' => 1, '2' => 2],
                '1' => ['3' => 3, '4' => 4],
            ],
            '47' => [
                'value1' => ['1' => 1],
                'value2' => ['2' => 2],
            ],
        ];
        $properties = [
            '25' => new Material_Field(25),
            '30' => new Material_Field(30),
            '47' => new Material_Field(47),
        ];

        $result = $filter->getRichValues($propsMapping, $properties);

        $this->assertEquals([
            '47' => [
                'value1' => 'Запись 1',
                'value2' => 'Запись 2',
            ],
        ], $result);
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
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $filter->categoryGoodsIds
        );
        $this->assertEquals(
            ['16' => 16, '13' => 13, '15' => 15],
            $filter->sortMapping[25]
        );
        $this->assertEquals(
            ['13' => 13, '16' => 16, '15' => 15],
            $filter->sortMapping[26]
        );
        $this->assertEquals(
            ['13' => 13, '15' => 15, '16' => 16],
            $filter->sortMapping['']
        );
        $this->assertTrue($filter->availableProperties[31][1]['checked']);
        $this->assertEquals(
            'В наличии',
            $filter->availableProperties[31][1]['prop']->name
        );
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
     */
    public function testGetCanonicalURLFromFilterWithNoInit()
    {
        $this->expectException(Exception::class);

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
     * Тест получения канонического URL из фильтра - случай с эксклюзивным доп. параметром
     */
    public function testGetCanonicalURLFromFilterWithAdditionalExclusive()
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
        $result = $filter->getCanonicalURLFromFilter([], 'article', 'aaa', true);

        $this->assertEquals('/catalog/?price_from=10000&price_to=60000&available=1&article%5B0%5D=aaa', $result);
    }


    /**
     * Тест получения канонического URL из фильтра - случай с добавлением неэксклюзивного доп. параметра
     */
    public function testGetCanonicalURLFromFilterWithAdditionalNotExclusiveAdd()
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
        $result = $filter->getCanonicalURLFromFilter([], 'article', 'aaa', false);

        $this->assertEquals(
            '/catalog/?price_from=10000&price_to=60000&available=1&article%5B0%5D=6dd28e9b&article%5B1%5D=84b12bae&article%5B2%5D=1db87a14&article%5B3%5D=aaa',
            $result
        );
    }


    /**
     * Тест получения канонического URL из фильтра - случай с удалением неэксклюзивного доп. параметра
     */
    public function testGetCanonicalURLFromFilterWithAdditionalNotExclusiveRemove()
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
        $result = $filter->getCanonicalURLFromFilter([], 'article', '84b12bae', false);

        $this->assertEquals(
            '/catalog/?price_from=10000&price_to=60000&available=1&article%5B0%5D=6dd28e9b&article%5B1%5D=1db87a14',
            $result
        );
    }


    /**
     * Тест метода multisort()
     */
    public function testMultisort()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filter->build();
        $result = $filter->multisort(['25'], ['13' => 13, '15' => 15, '16' => 16]); // 25 - Артикул

        $this->assertEquals(['16' => 16, '13' => 13, '15' => 15], $result);
    }


    /**
     * Тест метода multisort() - случай с сортирующей функцией
     */
    public function testMultisortWithOrderFunction()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filter->build();
        $result = $filter->multisort([['25', 'strnatcasecmp']], ['13' => 13, '15' => 15, '16' => 16]); // 25 - Артикул

        $this->assertEquals(['16' => 16, '13' => 13, '15' => 15], $result);
    }


    /**
     * Тест метода multisort() - случай с перегруппирующей функцией
     */
    public function testMultisortWithRegroupFunction()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filter->build();
        $result = $filter->multisort(
            [[
                '25',  // 25 - Артикул
                function ($x) {
                    return mb_substr($x, 1);
                },
                true
            ]],
            ['13' => 13, '15' => 15, '16' => 16]
        );

        $this->assertEquals(['15' => 15, '16' => 16, '13' => 13], $result);
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
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getIds('article', -1);

        $this->assertEquals([14, 15, 13, 16], $result);
    }


    /**
     * Тест получения ID# товаров с сортировкой - случай с первичной сортировкой по наличию
     */
    public function testGetIdsWithAvailabilityOrder()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filter->useAvailabilityOrder = 'available';
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->getIds('article', -1);

        $this->assertEquals([15, 13, 16, 14], $result);
    }


    /**
     * Тест получения ID# товаров с сортировкой - случай когда фильтр не инициализирован
     */
    public function testGetIdsWithFilterNotApplied()
    {
        $this->expectException(Exception::class);

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
        $this->assertNull($result['properties'][25] ?? null);
        $this->assertNull($result['propertiesByURNs']['article'] ?? null);
        $this->assertTrue($result['withChildrenGoods']);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result['propsMapping']['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $result['propsMapping']['31'][1]
        );
        // 2024-03-13, AVS: добавил richValues для материалов (оно есть по именам)
        $this->assertEquals([
            '47' => [
                'value1' => 'Запись 1',
            ],
            '35' => [
                '18' => 'Товар 9',
                '19' => 'Товар 10',
                '10' => 'Товар 1',
                '11' => 'Товар 2',
                '16' => 'Товар 7',
                '15' => 'Товар 6',
                '12' => 'Товар 3',
                '17' => 'Товар 8',
                '13' => 'Товар 4',
                '14' => 'Товар 5',
            ],
        ], $result['richValues']);
        $this->assertEquals(
            ['26' => 26, '32' => 32, '34' => 34],
            $result['numericFieldsIds']
        );
        $this->assertEquals(10, $result['counter'][16]);
        $this->assertEquals(10, $result['counter'][24]);
        $this->assertEquals(0, $result['selfCounter'][16]);
        $this->assertEquals(10, $result['selfCounter'][24]);
    }


    /**
     * Тест импорта
     */
    public function testImport()
    {
        $filterData = [
            'materialType' => new Material_Type(
                ['id' => 4, 'name' => 'Каталог продукции']
            ),
            'withChildrenGoods' => true,
            'ignoredFields' => ['article', 33],
            'materialTypesIds' => [4, 5],
            'properties' => [
                26 => new Material_Field(
                    ['id' => 26, 'name' => 'Стоимость', 'urn' => 'price']
                )
            ],
            'catalogGoodsIds' => [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            'propsMapping' => [
                '31' => [
                    1 => [
                        '11' => 11,
                        '12' => 12,
                        '13' => 13,
                        '15' => 15,
                        '16' => 16,
                        '17' => 17,
                        '19' => 19
                    ]
                ],
                'pages_ids' => [
                    15 => [
                        '10' => 10,
                        '11' => 11,
                        '12' => 12,
                        '13' => 13,
                        '14' => 14,
                        '15' => 15,
                        '16' => 16,
                        '17' => 17,
                        '18' => 18,
                        '19' => 19
                    ]
                ],
            ],
            'richValues' => [
                '47' => [
                    'value1' => 'Запись 1',
                ],
            ],
            'numericFieldsIds' => ['26' => 26, '32' => 32, '34' => 34],
            'counter' => [16 => 10, 24 => 10],
            'selfCounter' => [16 => 0, 24 => 10],
        ];

        $result = CatalogFilter::import($filterData);

        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals(['article', 33], $result->ignoredFields);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertNull($result->properties[25] ?? null);
        $this->assertNull($result->propertiesByURNs['article'] ?? null);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result->propsMapping['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $result->propsMapping['31'][1]
        );
        $this->assertEquals([
            '47' => [
                'value1' => 'Запись 1',
            ],
        ], $result->richValues);
        $this->assertEquals(
            ['26' => 26, '32' => 32, '34' => 34],
            $result->numericFieldsIds
        );
        $this->assertEquals(10, $result->counter[16]);
        $this->assertEquals(10, $result->counter[24]);
        $this->assertEquals(0, $result->selfCounter[16]);
        $this->assertEquals(10, $result->selfCounter[24]);
    }


    /**
     * Тест импорта - случай с некорректными данными
     */
    public function testImportWithInvalidData()
    {
        $this->expectException(Exception::class);

        $filterData = ['aaa', 'bbb', 'ccc'];

        $result = CatalogFilter::import($filterData);
    }


    /**
     * Тест получения пути к файлу по умолчанию
     */
    public function testGetDefaultFilename()
    {
        $result = CatalogFilter::getDefaultFilename(4, false);

        $this->assertEquals(Package::i()->cacheDir . '/system/catalogfilter4.noch.php', $result);
    }


    /**
     * Тест получения пути к файлу по умолчанию - случай с товарами из дочерних категорий
     */
    public function testGetDefaultFilenameWithChildren()
    {
        $result = CatalogFilter::getDefaultFilename(4, true);

        $this->assertEquals(Package::i()->cacheDir . '/system/catalogfilter4.wch.php', $result);
    }


    /**
     * Тест сохранения файла
     */
    public function testSave()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.wch.php';

        File::unlink(dirname($filename));

        $filter->build();
        $filter->save();

        $this->assertFileExists($filename);

        $result = include $filename;
        $this->assertEquals('Каталог продукции', $result['materialType']['name']);
        $this->assertEquals([4, 5], $result['materialTypesIds']);
        $this->assertEquals('Стоимость', $result['properties'][26]['name']);
        $this->assertTrue($result['withChildrenGoods']);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result['propsMapping']['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $result['propsMapping']['31'][1]
        );
    }


    /**
     * Тест сохранения файла - случай уже существующего файла - проверим что перезаписывается
     */
    public function testSaveWithExistingFile()
    {
        $filter = new CatalogFilter(new Material_Type(4), true);
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.wch.php';

        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }
        file_put_contents($filename, 'aaa');

        $result = file_get_contents($filename);

        $this->assertEquals('aaa', $result);

        $filter->build();
        $filter->save();

        $this->assertFileExists($filename);

        $result = file_get_contents($filename);

        $this->assertNotEquals('aaa', $result);
    }


    /**
     * Тест сохранения файла - случай с некорректным именем файла
     */
    public function testSaveWithInvalidFilepath()
    {
        $this->expectException(Exception::class);

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
        $result = CatalogFilter::load(new Material_Type(4), true, null, 'available');

        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result->propsMapping['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $result->propsMapping['31'][1]
        );
        $this->assertEquals('available', $result->useAvailabilityOrder);
    }


    /**
     * Тест загрузки - случай с несуществующим файлом
     */
    public function testLoadWithInvalidFilepath()
    {
        $this->expectException(Exception::class);

        $result = CatalogFilter::load(
            new Material_Type(4),
            false,
            static::getResourcesDir() . '/aaa.php'
        );
    }


    /**
     * Тест загрузки - случай с пустым файлом
     */
    public function testLoadWithEmptyFile()
    {
        $this->expectException(Exception::class);
        $filename = tempnam(sys_get_temp_dir(), 'raas_');
        touch($filename);

        $result = CatalogFilter::load(new Material_Type(4), false, $filename);

        unlink($filename);
    }


    /**
     * Тест загрузки или построения
     */
    public function testLoadOrBuild()
    {
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.wch.php';
        @unlink($filename);

        $this->assertFileDoesNotExist($filename);

        $result = CatalogFilter::loadOrBuild(new Material_Type(4), true, [], null, true, 'available');

        $this->assertFileExists($filename);
        $this->assertEquals('Каталог продукции', $result->materialType->name);
        $this->assertEquals([4, 5], $result->materialTypesIds);
        $this->assertEquals('Стоимость', $result->properties[26]->name);
        $this->assertEquals('Стоимость', $result->propertiesByURNs['price']->name);
        $this->assertTrue($result->withChildrenGoods);
        $this->assertEquals(
            [
                '10' => 10,
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '14' => 14,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '18' => 18,
                '19' => 19
            ],
            $result->propsMapping['pages_ids'][15]
        );
        $this->assertEquals(
            [
                '11' => 11,
                '12' => 12,
                '13' => 13,
                '15' => 15,
                '16' => 16,
                '17' => 17,
                '19' => 19
            ],
            $result->propsMapping['31'][1]
        );
        $this->assertEquals('available', $result->useAvailabilityOrder);
    }


    /**
     * Тест счетчика товаров
     * @param bool $filterWithChildren фильтр с учетом дочерних категорий
     * @param int $pageId ID# категории
     * @param bool $counterWithChildren счетчик с учетом дочерних категорий
     * @param int $expected ожидаемое количество товаров
     */
    #[TestWith([true, 17, true, 10])]
    #[TestWith([true, 17, false, 0])]
    #[TestWith([false, 17, true, 10])]
    #[TestWith([false, 17, false, 0])]
    public function testCount(
        $filterWithChildren,
        $pageId,
        $counterWithChildren,
        $expected
    ) {
        $filter = new CatalogFilter(new Material_Type(4), $filterWithChildren);
        $catalog = new Page(15);
        $filterData = [
            'price_from' => 10000,
            'price_to' => 60000,
            'available' => 1
        ];

        $filter->build();
        $filter->apply($catalog, $filterData);
        $result = $filter->count(new Page($pageId), $counterWithChildren);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест метода clearCaches
     */
    public function testClearCaches()
    {
        $filename = Package::i()->cacheDir . '/system/catalogfilter4.noch.php';
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }
        touch($filename);

        $this->assertFileExists($filename);

        CatalogFilter::clearCaches();

        $this->assertFileDoesNotExist($filename);
    }
}
