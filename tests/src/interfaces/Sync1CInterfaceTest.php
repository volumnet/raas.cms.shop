<?php
/**
 * Файл теста интерфейса синхронизации с 1С
 */
namespace RAAS\CMS\Shop;

use SimpleXMLElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use SOME\SOME;
use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Field;

/**
 * Класс теста интерфейса синхронизации с 1С
 */
#[CoversClass(Sync1CInterface::class)]
class Sync1CInterfaceTest extends BaseTest
{
    public static $tables = [
        'attachments',
        'cms_access',
        'cms_access_blocks_cache',
        'cms_access_materials_cache',
        'cms_access_pages_cache',
        'cms_blocks',
        'cms_blocks_form',
        'cms_blocks_html',
        'cms_blocks_material',
        'cms_blocks_material_filter',
        'cms_blocks_material_sort',
        'cms_blocks_menu',
        'cms_blocks_pages_assoc',
        'cms_blocks_search_material_types_assoc',
        'cms_blocks_search_pages_assoc',
        'cms_data',
        'cms_fieldgroups',
        'cms_fields',
        'cms_fields_form_vis',
        'cms_forms',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_menus',
        'cms_pages',
        'cms_shop_blocks_yml_pages_assoc',
        'cms_shop_cart_types_material_types_assoc',
        'cms_shop_imageloaders',
        'cms_shop_orders',
        'cms_shop_priceloaders',
        'cms_shop_priceloaders_columns',
        'cms_templates',
        'cms_users', // Только для одиночного теста
        'cms_users_blocks_login',
        'registry',
    ];


    /**
     * Последний ID# типов материалов по базе на момент установки
     * @var int
     */
    public static $materialTypesLastId = 0;

    /**
     * Последний ID# материалов по базе на момент установки
     * @var int
     */
    public static $materialsLastId = 0;

    /**
     * Последний ID# полей по базе на момент установки
     * @var int
     */
    public static $fieldsLastId = 0;


    public static function installTables()
    {
        parent::installTables();

        if (!static::$materialTypesLastId && !static::$materialsLastId && !static::$fieldsLastId) {
            $sqlQuery = "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
            $dbname = Application::i()->dbname;

            static::$materialTypesLastId = (int)Application::i()->SQL->getvalue([$sqlQuery, [$dbname, 'cms_material_types']]);
            static::$materialsLastId = (int)Application::i()->SQL->getvalue([$sqlQuery, [$dbname, 'cms_materials']]);
            static::$fieldsLastId = (int)Application::i()->SQL->getvalue([$sqlQuery, [$dbname, 'cms_fields']]);
        }
    }

    /**
     * Перестройка перед тестом
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        Page::_SQL()->update(Page::_tablename(), "id = 3", [
            'cache' => 1,
            'inherit_cache' => 1,
            'inherit_template' => 1,
            'inherit_sitemaps_priority' => 1,
            'sitemaps_priority' => 0.75
        ]);
        Page::_SQL()->add('cms_data', [
            [
                'pid' => 3,
                'fid' => 1,
                'fii' => 0,
                'value' => 'Test description',
                'inherited' => 1
            ],
            [
                'pid' => 3,
                'fid' => 3,
                'fii' => 0,
                'value' => 1,
                'inherited' => 0
            ]
        ]);
    }

    /**
     * Тест получения данных
     */
    public function testLoadData()
    {
        $interface = new Sync1CInterface();
        $goodsFile = static::getResourcesDir() . '/import0_1.update.xml';
        $goodsXSLFile = static::getResourcesDir() . '/import.xsl';
        $offersFile = static::getResourcesDir() . '/offers0_1.update.xml';
        $offersXSLFile = static::getResourcesDir() . '/offers.xsl';

        $result = $interface->loadData($goodsFile, $offersFile, $goodsXSLFile, $offersXSLFile);

        $this->assertEquals('Каталог продукции', $result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertFalse($result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['@config']['update']['pid']);
        $this->assertEquals('Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertEquals('354656', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['article']);
        $this->assertEquals('104', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['price']);
        $this->assertEquals('0', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['available']);
    }

    /**
     * Тест получения маппинга по артикулам
     */
    public function testGetArticlesMapping()
    {
        $interface = new Sync1CInterface();
        $materialType = new Material_Type(4);
        $articleFieldURN = 'article';

        $result = $interface->getArticlesMapping($materialType, $articleFieldURN);

        $this->assertEquals(10, $result['f4dbdf21']);
        $this->assertEquals(16, $result['1db87a14']);
        $this->assertEquals(19, $result['8d076785']);
    }


    /**
     * Провайдер данных материала
     * @return array<[
     *             array Данные по одному материалу
     *             string Идентификатор поля (URN или специальный идентификатор)
     *             'update'|'create'|'delete'|'map' Настройка, которую проверяем
     *             mixed Если настройка не задана, значение по умолчанию
     *             mixed Ожидаемый результат
     *         ]>
     */
    public static function isSpecialFieldDataProvider()
    {
        $materialData = [
            'id' => 'b323ca07-96e9-11e8-9a9f-6cf04909dac2',
            'pid' => '4',
            '@config' => ['update' => ['pid' => false, 'pages_ids' => true]],
            'name' => 'Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок',
            'description' => 'Блок-кубик Attache серии Fanasy для записей выполнен из белой офсетной бумаги. Голубой пластиковый стакан обеспечивает удобство использования и порядок на рабочем столе. Блок-кубик упакован в термоусадочную пленку. Размер изделия (ШхДхВ): 90х90х50 мм Плотность бумаги: офсет 70-80 г/м2. Белизна: 86-92 %.',
            'pages_ids' =>
            ['e7a0df86-96e8-11e8-9a9f-6cf04909dac2' => 'e7a0df86-96e8-11e8-9a9f-6cf04909dac2'],
            'fields' => [
                'article' => '354656',
                'id:ce78d3b0-d5cc-11e8-9aa9-6cf04909dac2' => 'ce78d3b5-d5cc-11e8-9aa9-6cf04909dac2',
                'id:ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2' => '90х90х50 мм',
                'id:ce78d3e2-d5cc-11e8-9aa9-6cf04909dac2' => 'Российская Федерация',
                'id:ce78d3eb-d5cc-11e8-9aa9-6cf04909dac2' => 'пластик',
                'id:ce78d429-d5cc-11e8-9aa9-6cf04909dac2' => 'белый',
                'id:ce78d43d-d5cc-11e8-9aa9-6cf04909dac2' => '80',
                'id:f13e6d9c-d5cc-11e8-9aa9-6cf04909dac2' => '86-92 %',
                'id:f13e6d9d-d5cc-11e8-9aa9-6cf04909dac2' => 'Да',
                'id:f13e6d9e-d5cc-11e8-9aa9-6cf04909dac2' => 'f13e6d9f-d5cc-11e8-9aa9-6cf04909dac2',
                'price' => '104',
                'available' => '0',
            ],
        ];
        return [
            [$materialData, 'pid', 'update', true, false],
            [$materialData, 'pages_ids', 'update', false, true],
            [$materialData, 'article', 'update', false, false],
        ];
    }


    /**
     * Тест специальных настроек поля
     * @param array $data Данные по одному материалу
     * @param string $key Идентификатор поля (URN или специальный идентификатор)
     * @param 'update'|'create'|'delete'|'map' $condition Настройка, которую проверяем
     * @param mixed $defaultValue Если настройка не задана, значение по умолчанию
     * @param mixed $expected Ожидаемый результат
     */
    #[DataProvider('isSpecialFieldDataProvider')]
    public function testIsSpecialField($data, $key, $condition, $defaultValue, $expected)
    {
        $interface = new Sync1CInterface();

        $result = $interface->isSpecialField($data, $key, $condition, $defaultValue);

        $this->assertEquals($expected, $result);
    }


    /**
     * Тест поиска сущности по полю
     * @param string $classname Класс сущности
     * @param string $fieldName Наименование нативного поля
     * @param mixed $value Значение поля
     * @param array<string[] поле => mixed значение> $context Набор дополнительных условий для выборки
     * @param int $expectedId ID# сущности
     */
    #[TestWith([Material_Type::class, 'name', 'Особые товары', [], 5])]
    #[TestWith([Material_Type::class, 'name', 'Особые товары', ['pid' => 4], 5])]
    #[TestWith([Material_Type::class, 'name', 'Особые товары', ['pid' => 0], null])]
    #[TestWith([Page::class, 'name', 'Категория 111', [], 18])]
    #[TestWith([Page::class, 'name', 'Категория 111', ['pid' => 17], 18])]
    #[TestWith([Page::class, 'name', 'Категория 111', ['pid' => 1], null])]
    #[TestWith([Material::class, 'name', 'Товар 1', [], 10])]
    #[TestWith([Material::class, 'name', 'Товар 1', [
        "SELECT COUNT(*) FROM cms_materials_pages_assoc WHERE id = Material.id AND pid = 18"
    ], 10 ])]
    #[TestWith([Material::class, 'name', 'Товар 1', [
        "SELECT COUNT(*) FROM cms_materials_pages_assoc WHERE id = Material.id AND pid = 17"
    ], null])]
    #[TestWith([Material_Field::class, 'name', '', [], null])]
    #[TestWith([Material_Field::class, 'name', null, [], null])]
    #[TestWith([Material_Field::class, 'name', 'Особое поле', ['pid' => [4, 5]], 48])]
    public function testFindEntityByField($classname, $fieldName, $value, array $context, $expectedId)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findEntityByField($classname, $fieldName, $value, $context);

        $this->assertEquals($expectedId, $result->id ?? null);
    }


    /**
     * Тест получения сообщения действия по сущности
     */
    public function testLogEntityMessage()
    {
        $interface = new Sync1CInterface();
        $entity = new Material_Type(1);

        $result = $interface->logEntityMessage($entity);

        $this->assertEquals('Updated Material_Type #1 (Наши преимущества)', $result);
    }


    /**
     * Тест получения сообщения действия по сущности (случай с созданием)
     */
    public function testLogEntityMessageWithCreate()
    {
        $interface = new Sync1CInterface();
        $entity = new Page(30);
        $entity->new = true;

        $result = $interface->logEntityMessage($entity, 10, 20);

        $this->assertEquals('Created Page #30 (Регистрация) - 10/20', $result);
    }


    /**
     * Тест получения сообщения действия по сущности (случай с удалением)
     */
    public function testLogEntityMessageWithDelete()
    {
        $interface = new Sync1CInterface();
        $entity = new Material(2);
        $entity->deleted = true;

        $result = $interface->logEntityMessage($entity, 30, 40);

        $this->assertEquals('Deleted Material #2 (Качество исполнения) - 30/40', $result);
    }


    /**
     * Тест получения сообщения действия по сущности (случай с удалением)
     */
    public function testLogEntityMessageWithOrphan()
    {
        $interface = new Sync1CInterface();
        $entity = new Page(2);
        $entity->orphan = true;

        $result = $interface->logEntityMessage($entity, 30, 40);

        $this->assertEquals('Orphan skipped Page #2 (О компании) - 30/40', $result);
    }


    /**
     * Тест загрузки маппинга
     */
    public function testLoadMapping()
    {
        $interface = new Sync1CInterface();
        $mapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $json = json_encode($mapping);
        $filename = static::getResourcesDir() . '/aaa.json';
        file_put_contents($filename, $json);

        $result = $interface->loadMapping($filename);
        unlink($filename);

        $this->assertEquals($mapping, $result);
    }


    /**
     * Тест загрузки маппинга (случай с пустым или несуществующим файлом)
     */
    public function testLoadMappingWithNoFile()
    {
        $interface = new Sync1CInterface();
        $filename = static::getResourcesDir() . '/bbb.json';

        $result = $interface->loadMapping($filename);

        $this->assertEquals([], $result);
    }


    /**
     * Тест сохранения маппинга
     */
    public function testSaveMapping()
    {
        $interface = new Sync1CInterface();
        $mapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $filename = static::getResourcesDir() . '/aaa.json';

        $interface->saveMapping($filename, $mapping);
        $result = (array)json_decode(file_get_contents($filename), true);
        unlink($filename);

        $this->assertEquals($mapping, $result);
    }


    /**
     * Тест Ищем сущность по ID# данных по маппингу
     * @param string $classname Класс сущности
     * @param array $data Набор данных
     * @param string $fieldName Ключ, по значению которого ищем
     * @param array $mapping Полный маппинг по всем классам
     * @param int|null $expectedId ID# объекта класса $classname, либо null, если не найден
     */
    #[TestWith([
        Material_Type::class,
        ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
        'id',
        [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 5]],
        5
    ])]
    #[TestWith([
        Material_Type::class,
        ['pid' => 4, '@config' => ['map' => ['pid' => false]]],
        'pid',
        [Page::class => ['sdklfjweiorjoisdmnfl' => 5]],
        4
    ])]
    #[TestWith([
        Material_Type::class,
        ['id' => 'zxjhcoihwoer', '@config' => ['map' => ['pid' => true]]],
        'pid',
        [Page::class => ['sdklfjweiorjoisdmnfl' => 5]],
        null
    ])]
    #[TestWith([
        Material_Type::class,
        ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
        'id',
        [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 999]],
        null
    ])]
    public function testFindEntityById($classname, array $data, $fieldName, array $mapping, $expectedId)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findEntityById($classname, $data, $fieldName, $mapping);

        $this->assertEquals($expectedId, $result->id ?? null);
    }


    /**
     * Провайдер данных для функции testFindOrCreateEntity
     * @return array<[
     *             string Класс сущности
     *             array Данные по сущности
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             string Наименование поля ID#
     *             string Наименование поля, по которому ищем
     *             string Наименование родительского класса
     *             string Наименование поля ID# родителя
     *             SOME Родительская сущность по умолчанию
     *             bool Учитывать дочерние элементы для родительского, в качестве родительских
     *             array Массив проверки полей сущности
     *             array Массив проверки записей маппинга по данной сущности
     *         ]>
     */
    public static function findOrCreateEntityDataProvider()
    {
        static::installTables();
        return [
            [
                Material_Type::class,
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 5]],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(),
                false,
                ['id' => 5, 'pid' => 4],
                [],
            ],
            [
                Material_Type::class,
                ['id' => 5, '@config' => ['map' => ['id' => false]]],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 5]],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(),
                false,
                ['id' => 5, 'pid' => 4],
                [],
            ],
            [
                Material_Type::class,
                ['id' => 'aaa', 'name' => 'Особые товары', 'pid' => 'sdklfjweiorjoisdmnfl'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(),
                false,
                ['id' => 5, 'pid' => 4],
                ['aaa' => 5],
            ],
            [
                Material_Type::class,
                ['id' => 'aaa', 'name' => 'Особые товары', 'pid' => 'sdklfjweiorjoisdmnfl'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3]],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(),
                false,
                ['id' => null],
                [],
            ],
            [
                Material_Type::class,
                ['id' => 'aaa', 'name' => 'Особые товары', 'pid' => 'sdklfjweiorjoisdmnfl'],
                [Material_Type::class => []],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(3),
                false,
                ['id' => null],
                [],
            ],
            [
                Material_Field::class,
                ['id' => 'zxlkjsdruowisdn', 'name' => 'Особое поле'],
                [],
                'id',
                'name',
                Material_Type::class,
                'pid',
                new Material_Type(4),
                true,
                ['id' => 48, 'pid' => 5],
                [],
            ],
        ];
    }


    /**
     * Тест поиска или создания сущности
     * @param string $classname Класс сущности
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $idN Наименование поля ID#
     * @param string $searchField Наименование поля, по которому ищем
     * @param string $parentClassname Наименование родительского класса
     * @param string $pidN Наименование поля ID# родителя
     * @param SOME $defaultParent Родительская сущность по умолчанию
     * @param bool $withParentChildren Учитывать дочерние элементы для родительского, в качестве родительских
     * @param array<string[] => mixed> $expectedTest Массив проверки полей сущности
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по данной сущности
     */
    #[DataProvider('findOrCreateEntityDataProvider')]
    public function testFindOrCreateEntity(
        $classname,
        array $data,
        array $mapping,
        $idN,
        $searchField,
        $parentClassname,
        $pidN,
        SOME $defaultParent,
        $withParentChildren,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateEntity(
            $classname,
            $data,
            $mapping,
            $idN,
            $searchField,
            $parentClassname,
            $pidN,
            $defaultParent,
            $withParentChildren
        );

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[$classname][$key]);
        }
    }

    /**
     * Провайдер данных для функции testUpdateEntity
     * @return array<[
     *             SOME Сущность для обновления
     *             array Данные по сущности для обновления
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             array<string[] => mixed> Массив проверки полей сущности
     *         ]>
     */
    public static function updateEntityDataProvider()
    {
        static::installTables();
        return [
            [
                new Material(10),
                [
                    'id' => 10,
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'create' => ['pid' => true, 'name' => true, 'description' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => true, 'description' => false, 'urn' => true]
                    ]
                ],
                [Material_Type::class => ['asdsdiofusf' => 5]],
                ['id' => 10, 'pid' => 5, 'name' => 'Name', 'description' => '', 'urn' => 'urn']
            ],
            [
                new Material(10),
                [
                    'id' => 10,
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'create' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['asdsdiofusf' => 5]],
                ['id' => 10, 'pid' => 4, 'name' => 'Товар 1', 'description' => 'Description', 'urn' => 'tovar_1']
            ],
            [
                new Material(),
                [
                    'id' => 'aaa',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'create' => ['pid' => true, 'name' => true, 'description' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => true, 'description' => false, 'urn' => true]
                    ]
                ],
                [Material_Type::class => ['asdsdiofusf' => 5]],
                ['id' => null, 'pid' => 5, 'name' => 'Name', 'description' => '', 'urn' => 'urn']
            ],
            [
                new Material(),
                [
                    'id' => 'aaa',
                    'pid' => 5,
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['pid' => false],
                        'create' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['asdsdiofusf' => 5]],
                ['id' => null, 'pid' => null, 'name' => null, 'description' => 'Description', 'urn' => null]
            ],
            [
                new Material(),
                [
                    'id' => 'aaa',
                    'pid' => 5,
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['pid' => false],
                        'create' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'description' => true, 'urn' => false]
                    ]
                ],
                [],
                ['id' => null, 'pid' => null, 'name' => null, 'description' => 'Description', 'urn' => null]
            ],
            [
                new Material(),
                [
                    'id' => 'ccc',
                    'pid' => 'ddd',
                    'name' => 'Name',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['pid' => true],
                        'create' => ['pid' => true, 'name' => true, 'description' => true, 'urn' => true],
                        'update' => ['pid' => true, 'name' => true, 'description' => true, 'urn' => true]
                    ]
                ],
                [Material_Type::class => ['ddd' => 5]],
                ['id' => null, 'pid' => 5, 'name' => 'Name', 'description' => 'Description', 'urn' => 'urn']
            ],
            [
                new Material(),
                [
                    'id' => 'ccc1',
                    'name' => 'Name',
                    'pid' => 'ddd',
                    'description' => 'Description',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['pid' => true],
                        'create' => ['pid' => false, 'description' => true, 'urn' => true],
                        'update' => ['pid' => true, 'name' => true, 'description' => true, 'urn' => true]
                    ]
                ],
                [Material_Type::class => ['ddd' => 5]],
                ['id' => null, 'name' => 'Name', 'description' => 'Description', 'urn' => 'urn']
            ],
        ];
    }


    /**
     * Тест обновления нативных полей сущности (без сохранения) с учетом конфигурации по обновлению
     * @param SOME $entity Сущность для обновления
     * @param array $data Данные по сущности для обновления
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param array<string[] => mixed> $expectedTest Массив проверки полей сущности
     */
    #[DataProvider('updateEntityDataProvider')]
    public function testUpdateEntity(SOME $entity, array $data, array $mapping, array $expectedTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->updateEntity($entity, $data, $mapping);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $entity->$key);
        }
    }


    /**
     * Провайдер данных для функции testUpdatePages
     * @return array<[
     *             Material Материал для обновления
     *             array Данные по материалу для обновления
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             array<int> Массив проверки ID# страниц
     *         ]>
     */
    public static function updatePagesDataProvider()
    {
        static::installTables();
        return [
            [
                new Material(10),
                [
                    'pages_ids' => ['aaa', 'bbb', 'ccc'],
                    '@config' => ['update' => ['pages_ids' => true]],
                ],
                [Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6]],
                [4, 5, 6]
            ],
            [
                new Material(10),
                [
                    'pages_ids' => ['aaa', 'bbb', 'ccc'],
                ],
                [Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6]],
                [1, 18, 19, 20, 21, 22, 23, 24, 4, 5, 6]
            ],
            [
                new Material(),
                [
                    'pages_ids' => ['aaa', 'bbb', 'ccc'],
                ],
                [Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6]],
                [4, 5, 6]
            ],
        ];
    }


    /**
     * Тест обновления привязки к страницам
     * @param Material $entity Материал для обновления
     * @param array $data Данные по материалу для обновления
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param array<int> $expectedTest Массив проверки ID# страниц
     * @param Page $defaultParent Родительская страница по умолчанию (если не найдены) - тогда материал устанавливается скрытым
     */
    #[DataProvider('updatePagesDataProvider')]
    public function testUpdatePages(Material $entity, array $data, array $mapping, $expectedTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->updatePages($entity, $data, $mapping, new Page(15));

        $this->assertEquals($expectedTest, (array)$entity->cats);
    }


    /**
     * Тест обновления привязки к страницам - случай, когда материал глобальный
     */
    public function testUpdatePagesWithGlobalMaterial()
    {
        $interface = new Sync1CInterface();
        $material = new Material(7);

        $result = $interface->updatePages(
            $material,
            [
                'pages_ids' => ['aaa', 'bbb', 'ccc'],
            ],
            [Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6]],
            new Page(15)
        );

        $this->assertFalse($result);
    }


    /**
     * Тест обновления привязки к страницам - случай, когда страницы не заданы
     */
    public function testUpdatePagesWithNoPages()
    {
        $interface = new Sync1CInterface();
        $material = new Material();

        $result = $interface->updatePages(
            $material,
            [
                'pages_ids' => ['aaa', 'bbb', 'ccc'],
            ],
            [],
            new Page(15)
        );

        $this->assertEquals([15], $material->cats);
        $this->assertEquals(0, $material->vis);
    }


    /**
     * Провайдер данных для функции testUpdateCustomField
     * @return array<[
     *             Field Поле, значение которого нужно обновить
     *             string Ключ поля в данных по сущности
     *             array Данные по сущности
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             string Путь к папке с файлами для медиа-полей,
     *             array<string> Массив проверки значений
     *         ]>
     */
    public static function updateCustomFieldDataProvider()
    {
        static::installTables();
        $material = new Material(10);
        $dir = static::getResourcesDir() . '';
        return [
            [
                $material->fields['price'],
                'id:p-r-i-c-e',
                ['fields' => ['id:p-r-i-c-e' => 10]],
                [],
                $dir,
                [10]
            ],
            [
                $material->fields['videos'],
                'id:v-i-d-e-o-s',
                ['fields' => ['id:v-i-d-e-o-s' => ['aaa', 'bbb', 'ccc']]],
                [],
                $dir,
                ['aaa', 'bbb', 'ccc']
            ],
            [
                $material->fields['videos'],
                'id:v-i-d-e-o-s',
                [
                    'fields' => ['id:v-i-d-e-o-s' => ['aaa', 'bbb', 'ccc']],
                    '@config' => ['map' => ['id:v-i-d-e-o-s' => true]]
                ],
                ['source' => [28 => ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz']]],
                $dir,
                ['xxx', 'yyy', 'zzz']
            ],
            [
                $material->fields['related'],
                'id:r-e-l-a-t-e-d',
                ['fields' => ['id:r-e-l-a-t-e-d' => ['aaa', 'bbb', 'ccc']]],
                [Material::class => ['aaa' => 11, 'bbb' => 12, 'ccc' => 13]],
                $dir,
                [11, 12, 13]
            ],
            [
                $material->fields['files'],
                'id:f-i-l-e-s',
                ['fields' => ['id:f-i-l-e-s' => ['import.xsl', 'offers.xsl']]],
                [],
                $dir,
                ['import.xsl', 'offers.xsl']
            ],
        ];
    }


    /**
     * Тест обновления значения кастомного поля
     * @param Field $field Поле, значение которого нужно обновить
     * @param string $fieldKey Ключ поля в данных по сущности
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param array<string[] => mixed> $expectedTest Массив проверки полей сущности
     */
    #[DataProvider('updateCustomFieldDataProvider')]
    public function testUpdateCustomField(Field $field, $fieldKey, array $data, array $mapping, $dir, array $expectedTest)
    {
        $t = $this;
        $interface = new Sync1CInterface();

        $result = $interface->updateCustomField($field, $fieldKey, $data, $mapping, $dir);
        $values = $field->getValues(true);
        if (in_array($field->datatype, ['file', 'image'])) {
            $values = array_map(function ($x) use ($t) {
                $t->assertFileExists($x->file);
                return $x->filename;
            }, $values);
        } elseif ($field->datatype == 'material') {
            $values = array_map(function ($x) {
                return $x->id;
            }, $values);
        }

        $this->assertEquals((array)$expectedTest, $values);
    }


    /**
     * Провайдер данных для функции testUpdateCustomFields
     * @return array<[
     *             SOME Сущность, для которой обновляем поля
     *             bool Сущность только что создана
     *             array Данные по сущности
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             string Путь к папке с файлами для медиа-полей
     *             array<string[] => mixed> Массив проверки полей сущности через $entity->fields[$key]->getValues(true)
     *         ]>
     */
    public static function updateCustomFieldsDataProvider()
    {
        static::installTables();
        $dir = static::getResourcesDir() . '';
        return [
            [
                new Material(11),
                false,
                [
                    'fields' => [
                        'price' => 10,
                        'id:v-i-d-e-o-s' => ['aaa', 'bbb', 'ccc'],
                        'id:r-e-l-a-t-e-d' => ['aaa', 'bbb', 'ccc'],
                        'files' => ['import.xsl', 'offers.xsl'],
                    ],
                    '@config' => ['map' => ['id:v-i-d-e-o-s' => true]]
                ],
                [
                    'source' => [28 => ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz']],
                    Material::class => ['aaa' => 11, 'bbb' => 12, 'ccc' => 13],
                    Material_Field::class => ['v-i-d-e-o-s' => 28, 'r-e-l-a-t-e-d' => 35]
                ],
                $dir,
                [
                    'price' => [10],
                    'videos' => ['xxx', 'yyy', 'zzz'],
                    'related' => [11, 12, 13],
                    'files' => ['import.xsl', 'offers.xsl']
                ]
            ],
            [
                new Material(12),
                false,
                [
                    'fields' => [
                        'price' => 10,
                        'id:v-i-d-e-o-s' => ['aaa', 'bbb', 'ccc'],
                        'id:r-e-l-a-t-e-d' => ['aaa', 'bbb', 'ccc'],
                        'files' => ['import.xsl', 'offers.xsl'],
                    ],
                    '@config' => [
                        'update' => [
                            'price' => true,
                            'id:v-i-d-e-o-s' => false,
                            'id:r-e-l-a-t-e-d' => true,
                            'files' => false
                        ],
                        'map' => ['id:v-i-d-e-o-s' => true]
                    ]
                ],
                [
                    'source' => [28 => ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz']],
                    Material::class => ['aaa' => 11, 'bbb' => 12, 'ccc' => 13],
                    Material_Field::class => ['v-i-d-e-o-s' => 28, 'r-e-l-a-t-e-d' => 35]
                ],
                $dir,
                [
                    'price' => [10],
                    'videos' => [
                        'https://www.youtube.com/watch?v=YVgc2PQd_bo',
                        'https://www.youtube.com/watch?v=YVgc2PQd_bo'
                    ],
                    'related' => [11, 12, 13],
                    'files' => ['test.doc', 'test.pdf']
                ]
            ],
            [
                new Material(12),
                true,
                [
                    'fields' => [
                        'price' => 10,
                        'id:v-i-d-e-o-s' => ['aaa', 'bbb', 'ccc'],
                        'id:r-e-l-a-t-e-d' => ['aaa', 'bbb', 'ccc'],
                        'files' => ['import.xsl', 'offers.xsl'],
                        '@comment' => 'aaa',
                    ],
                    '@config' => [
                        'create' => [
                            'price' => true,
                            'id:v-i-d-e-o-s' => false,
                            'id:r-e-l-a-t-e-d' => true,
                            'files' => false
                        ],
                        'map' => ['id:v-i-d-e-o-s' => true]
                    ]
                ],
                [
                    'source' => [28 => ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz']],
                    Material::class => ['aaa' => 11, 'bbb' => 12, 'ccc' => 13],
                    Material_Field::class => ['v-i-d-e-o-s' => 28, 'r-e-l-a-t-e-d' => 35]
                ],
                $dir,
                [
                    'price' => [10],
                    'videos' => [
                        'https://www.youtube.com/watch?v=YVgc2PQd_bo',
                        'https://www.youtube.com/watch?v=YVgc2PQd_bo'
                    ],
                    'related' => [11, 12, 13],
                    'files' => ['test.doc', 'test.pdf']
                ]
            ],
        ];
    }


    /**
     * Тест обновления кастомных полей для сущности с учетом их настроек по созданию/обновлению
     * @param SOME $entity Сущность, для которой обновляем поля
     * @param bool $new Сущность только что создана
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param array<string[] => mixed> $expectedTest Массив проверки полей сущности через $entity->fields[$key]->getValues(true)
     */
    #[DataProvider('updateCustomFieldsDataProvider')]
    public function testUpdateCustomFields(SOME $entity, $new, array $data, array $mapping, $dir, array $expectedTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->updateCustomFields($entity, $new, $data, $mapping, $dir);

        foreach ($expectedTest as $key => $val) {
            $field = $entity->fields[$key];
            $values = $field->getValues(true);
            if (in_array($field->datatype, ['file', 'image'])) {
                $values = array_map(function ($x) {
                    return $x->filename;
                }, $values);
            } elseif ($field->datatype == 'material') {
                $values = array_map(function ($x) {
                    return $x->id;
                }, $values);
            }
            $this->assertEquals($val, $values);
        }
    }


    /**
     * Тест обновления кастомных полей для сущности с учетом их настроек
     * по созданию/обновлению - случай, когда сущность не имеет полей
     */
    public function testUpdateCustomFieldsWithEntityWithNoField()
    {
        $interface = new Sync1CInterface();

        $result = $interface->updateCustomFields(new Material(12), false, ['price' => 'aaa'], [], static::getResourcesDir());

        $this->assertFalse($result);
    }


    /**
     * Провайдер данных для функции testFindOrCreateMaterialType
     * @return array<[
     *             array Данные по типу материала
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых типов
     *             array<string[] => mixed> Массив проверки полей типа материала
     *             array<string[] => int> Массив проверки записей маппинга по типам материалов
     *         ]>
     */
    public static function findOrCreateMaterialTypeDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type(3);
        return [
            [
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 5]],
                $materialType,
                ['id' => 5],
                [],
            ],
            [
                ['id' => 5, '@config' => ['map' => ['id' => false]]],
                [],
                $materialType,
                ['id' => 5],
                [],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Особые товары'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                $materialType,
                ['id' => 5],
                ['zxjhcoihwoer' => 5],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Особые товары'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 5]],
                $materialType,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Особые товары'],
                [],
                $materialType,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
        ];
    }


    /**
     * Тест поиска/создания типа материала
     * @param array $data Данные по типу материала
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых типов
     * @param array<string[] => mixed> $expectedTest Массив проверки полей типа материала
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по типам материалов
     */
    #[DataProvider('findOrCreateMaterialTypeDataProvider')]
    public function testFindOrCreateMaterialType(
        array $data,
        array $mapping,
        Material_Type $defaultParent,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateMaterialType($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material_Type::class][$key] ?? null);
        }
    }


    /**
     * Провайдер данных для функции testFindOrCreateField
     * @return array<[
     *             array Данные по полю
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых полей
     *             array<string[] => mixed> Массив проверки полей кастомного поля
     *             array<string[] => int> Массив проверки записей маппинга по кастомным полям
     *         ]>
     */
    public static function findOrCreateFieldDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type(3);
        return [
            [
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                [Material_Field::class => ['sdklfjweiorjoisdmnfl' => 26]],
                $materialType,
                ['id' => 26],
                [],
            ],
            [
                ['id' => 28, '@config' => ['map' => ['id' => false]]],
                [],
                $materialType,
                ['id' => 28],
                [],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Изображение'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                $materialType,
                ['id' => 27],
                ['zxjhcoihwoer' => 27],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Новое поле'],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                $materialType,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Новое поле'],
                [],
                $materialType,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'name' => 'Особое поле'],
                [],
                new Material_Type(4),
                ['id' => 48],
                ['zxjhcoihwoer' => 48],
            ],
        ];
    }


    /**
     * Тест поиска/создания кастомного поля
     * @param array $data Данные по полю
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых полей
     * @param array<string[] => mixed> $expectedTest Массив проверки полей кастомного поля
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по кастомным полям
     */
    #[DataProvider('findOrCreateFieldDataProvider')]
    public function testFindOrCreateField(array $data, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateField($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material_Field::class][$key] ?? null);
        }
    }


    /**
     * Провайдер данных для функции testFindOrCreatePage
     * @return array<[
     *             array Данные по странице
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Page Родительская страница по умолчанию для вновь создаваемых типов
     *             array<string[] => mixed> Массив проверки полей страницы
     *             array<string[] => int> Массив проверки записей маппинга по страницам
     *         ]>
     */
    public static function findOrCreatePageDataProvider()
    {
        static::installTables();
        $page = new Page(1);
        return [
            [
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 15]],
                $page,
                ['id' => 15],
                [],
            ],
            [
                ['id' => 15, '@config' => ['map' => ['id' => false]]],
                [],
                $page,
                ['id' => 15],
                [],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Категория 11'],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 16]],
                $page,
                ['id' => 17],
                ['zxjhcoihwoer' => 17],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Категория 11'],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 15]],
                $page,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Категория 11'],
                [],
                $page,
                ['id' => null],
                ['zxjhcoihwoer' => null],
            ],
        ];
    }


    /**
     * Тест поиска/создания страницы
     * @param array $data Данные по странице
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Page $defaultParent Родительская страница по умолчанию для вновь создаваемых типов
     * @param array<string[] => mixed> $expectedTest Массив проверки полей страницы
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по страницам
     */
    #[DataProvider('findOrCreatePageDataProvider')]
    public function testFindOrCreatePage(array $data, array $mapping, Page $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreatePage($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Page::class][$key] ?? null);
        }
    }


    /**
     * Провайдер данных для функции testFindOrCreateMaterial
     * @return array<[
     *             array Данные по материалу
     *             array<string[] Артикул => int ID# товара> маппинг по артикулам
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых материалов
     *             array<string[] => mixed> Массив проверки полей материала
     *             array<string[] => int> Массив проверки записей маппинга по материалам
     *         ]>
     */
    public static function findOrCreateMaterialDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type(5);
        $articlesMapping = ['f4dbdf21' => 10, '83dcefb7' => 11];
        return [
            [
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                $articlesMapping,
                [Material::class => ['sdklfjweiorjoisdmnfl' => 13]],
                $materialType,
                ['id' => 13, 'vis' => 1],
                [],
            ],
            [
                ['id' => 15, '@config' => ['map' => ['id' => false]]],
                $articlesMapping,
                [],
                $materialType,
                ['id' => 15, 'vis' => 1],
                [],
            ],
            [
                [
                    'id' => 'zxjhcoihwoer',
                    'pid' => 'sdklfjweiorjoisdmnfl',
                    'name' => 'Некоторый товар',
                    'fields' => ['article' => 'f4dbdf21']
                ],
                $articlesMapping,
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                $materialType,
                ['id' => 10, 'vis' => 1],
                ['zxjhcoihwoer' => 10],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Новый товар'],
                $articlesMapping,
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 4]],
                $materialType,
                ['id' => null, 'pid' => 4, 'vis' => 1],
                ['zxjhcoihwoer' => null],
            ],
            [
                ['id' => 'zxjhcoihwoer', 'pid' => 'sdklfjweiorjoisdmnfl', 'name' => 'Новый товар'],
                $articlesMapping,
                [],
                $materialType,
                ['id' => null, 'vis' => 1],
                ['zxjhcoihwoer' => null],
            ],
        ];
    }


    /**
     * Тест поиска/создания материала
     * @param array $data Данные по материалу
     * @param array<string[] Артикул => int ID# товара> $articlesMapping маппинг по артикулам
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых материалов
     * @param array<string[] => mixed> $expectedTest Массив проверки полей материала
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по материалам
     */
    #[DataProvider('findOrCreateMaterialDataProvider')]
    public function testFindOrCreateMaterial(array $data, array $articlesMapping, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateMaterial($data, $articlesMapping, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material::class][$key] ?? null);
        }
    }


    /**
     * Провайдер данных для функции testProcessMaterialType
     * @return array<[
     *             array Данные по типу материала
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых типов
     *             array<string[] => mixed> Массив проверки полей типа материала
     *             array<string[] => int> Массив проверки записей маппинга по типам материалов
     *         ]>
     */
    public static function processMaterialTypeDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type();
        return [
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['id' => true],
                        'create' => ['pid' => true, 'name' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => false, 'urn' => true]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                ['id' => 3, 'pid' => 1, 'name' => 'Новости', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 0,
                    'name' => 'Name1',
                    'urn' => 'urn1',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => true, 'name' => true, 'urn' => false],
                        'update' => ['pid' => true, 'name' => true, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                ['id' => 3, 'pid' => 0, 'name' => 'Name1', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'aaa',
                    'pid' => 0,
                    'name' => 'Name1',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => false, 'name' => false, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                ['id' => 3, 'pid' => 0, 'name' => 'Name1', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'bbb',
                    'pid' => 3,
                    'name' => 'Name1',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => false, 'name' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                // 2024-03-13, AVS: поменял на динамическую переменную, т.к. в базе добавились два типа материалов
                // ("Вопрос-ответ к товарам" и "Отзывы к товарам")
                ['id' => static::$materialTypesLastId, 'pid' => 0, 'name' => 'Name1', 'urn' => 'name1', 'new' => true],
                ['bbb' => static::$materialTypesLastId],
            ],
        ];
    }


    /**
     * Тест обработки полученных данных по типу материалов (с сохранением и обновлением маппинга)
     * @param array $data Данные по типу материала
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых типов
     * @param array<string[] => mixed> $expectedTest Массив проверки полей типа материала
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по типам материалов
     */
    #[DataProvider('processMaterialTypeDataProvider')]
    public function testProcessMaterialType(
        array $data,
        array $mapping,
        Material_Type $defaultParent,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->processMaterialType($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material_Type::class][$key]);
        }
    }


    /**
     * Тест обработки полученных данных по типу материалов (случай с удалением)
     */
    public function testProcessMaterialTypeWithDelete()
    {
        $interface = new Sync1CInterface();
        $mapping = [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 2, 'asdsdiofusf' => 1]];

        $result = $interface->processMaterialType(
            [
                'id' => 'sdklfjweiorjoisdmnfl',
                'pid' => 'asdsdiofusf',
                'name' => 'Name',
                'urn' => 'urn',
                '@delete' => true,
                '@config' => [
                    'map' => ['id' => true],
                    'create' => ['pid' => true, 'name' => false, 'urn' => true],
                    'update' => ['pid' => true, 'name' => false, 'urn' => true]
                ]
            ],
            $mapping,
            new Material_Type()
        );

        $this->assertInstanceOf(Material_Type::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertTrue($result->deleted);
        $materialType = new Material_Type(2);
        $this->assertEmpty($materialType->id);
    }


    /**
     * Провайдер данных для функции testProcessField
     * @return array<[
     *             array Данные по полю
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых полей
     *             array<string[] => mixed> Массив проверки полей кастомного поля
     *             array<string[] => int> Массив проверки записей маппинга по кастомным полям
     *         ]>
     */
    public static function processFieldDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type();
        return [
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['id' => true],
                        'create' => ['pid' => true, 'name' => false, 'urn' => true],
                        'update' => ['name' => false]
                    ]
                ],
                [
                    Material_Field::class => ['sdklfjweiorjoisdmnfl' => 47],
                    Material_Type::class => ['asdsdiofusf' => 1]
                ],
                $materialType,
                ['id' => 47, 'pid' => 4, 'name' => 'Тестовое поле справочника', 'urn' => 'urn'],
                // Подтягивается по маппингу Material_Field::class => ['sdklfjweiorjoisdmnfl' => 47],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf', // 2024-06-11, AVS: Поправил для установки корректного pid (см. ниже)
                    'name' => 'Name1',
                    'urn' => 'urn1',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => true],
                        'create' => ['pid' => true, 'name' => true, 'urn' => false],
                        'update' => ['pid' => true, 'name' => true, 'urn' => false]
                    ]
                ],
                [
                    Material_Field::class => ['sdklfjweiorjoisdmnfl' => 34],
                    Material_Type::class => ['asdsdiofusf' => 1]
                ],
                $materialType,
                ['id' => 34, 'pid' => 1, 'name' => 'Name1', 'urn' => 'price_old'],
                // Подтягивается по маппингу Material_Field::class => ['sdklfjweiorjoisdmnfl' => 34],
                // Обновляется по 'update' => ['pid' => true]
                [],
            ],
            [
                [
                    'id' => 'aaa',
                    'pid' => 0,
                    'name' => 'Name1',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => false, 'name' => false, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                ['id' => 34, 'pid' => 1, 'name' => 'Name1', 'urn' => 'price_old'],
                // 2024-06-11, AVS: не подтягивается из-за изменений в Material_Field
                // Сейчас они могут быть только с установленным pid
                // Поправил предыдущее для установки корректного pid
                // Подтягивается по имени Name1 в контексте нулевого pid (все типы материалов)
                [],
            ],
            [
                [
                    'id' => 'bbb',
                    'pid' => 3,
                    'name' => 'Name1',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => false, 'name' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $materialType,
                // 2024-03-13, AVS: поменял на динамическую переменную, т.к. количество полей увеличилось
                ['id' => static::$fieldsLastId, 'pid' => 0, 'name' => 'Name1', 'urn' => 'name1', 'new' => true],
                ['bbb' => static::$fieldsLastId],
                // Не подтягивается, т.к. pid другой
            ],
        ];
    }


    /**
     * Тест обработки полученных данных по кастомному полю (с сохранением и обновлением маппинга)
     * @param array $data Данные по полю
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых полей
     * @param array<string[] => mixed> $expectedTest Массив проверки полей кастомного поля
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по кастомным полям
     */
    #[DataProvider('processFieldDataProvider')]
    public function testProcessField(
        array $data,
        array $mapping,
        Material_Type $defaultParent,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->processField($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material_Field::class][$key]);
        }
    }


    /**
     * Тест обработки полученных данных по кастомному полю (с сохранением и обновлением маппинга) - выборка значений
     */
    public function testProcessFieldSelectValues()
    {
        $interface = new Sync1CInterface();
        $mapping = [
            Material_Field::class => ['sdklfjweiorjoisdmnfl' => 47],
            Material_Type::class => ['asdsdiofusf' => 1]
        ];

        $result = $interface->processField(
            [
                'id' => 'sdklfjweiorjoisdmnfl',
                'pid' => 'asdsdiofusf',
                'name' => 'Name',
                'urn' => 'urn',
                '@values' => [
                    'aaa' => 'xxx',
                    'bbb' => 'yyy',
                    'ccc' => 'zzz',
                ],
                '@config' => [
                    'map' => ['id' => true],
                    'create' => ['pid' => true, 'name' => false, 'urn' => true],
                    'update' => ['name' => false]
                ]
            ],
            $mapping,
            new Material_Type(4)
        );

        $this->assertEquals(
            ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'],
            $mapping['source'][47]
        );
    }


    /**
     * Тест обработки полученных данных по кастомному полю (с сохранением и обновлением маппинга) - случай с удалением
     */
    public function testProcessFieldWithDelete()
    {
        $interface = new Sync1CInterface();
        $mapping = [];

        $result = $interface->processField(
            ['id' => 30, '@delete' => true, '@config' => ['map' => ['id' => false]]],
            $mapping,
            new Material_Type()
        );

        $this->assertInstanceOf(Material_Field::class, $result);
        $this->assertEquals(30, $result->id);
        $this->assertTrue($result->deleted);
        $field = new Material_Field(30);
        $this->assertEmpty($field->id);
    }


    /**
     * Провайдер данных для функции testProcessPage
     * @return array<[
     *             array Данные по странице
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Page Родительская страница по умолчанию для вновь создаваемых типов
     *             string Путь к папке с файлами для медиа-полей
     *             array<string[] => mixed> Массив проверки полей страницы
     *             array<string[] => int> Массив проверки записей маппинга по страницам
     *         ]>
     */
    public static function processPageDataProvider()
    {
        static::installTables();
        $page = new Page(1);
        $dir = static::getResourcesDir() . '/';
        return [
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'urn' => 'urn',
                    '@config' => [
                        'map' => ['id' => true],
                        'create' => ['pid' => true, 'name' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => false, 'urn' => true]
                    ]
                ],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 22, 'asdsdiofusf' => 1]],
                $page,
                $dir,
                ['id' => 22, 'pid' => 1, 'name' => 'Категория 13', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 1,
                    'name' => 'skdjwpejsdf',
                    'urn' => 'urn1',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => true, 'name' => true, 'urn' => false],
                        'update' => ['pid' => true, 'name' => true, 'urn' => false]
                    ]
                ],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 22, 'asdsdiofusf' => 1]],
                $page,
                $dir,
                ['id' => 22, 'pid' => 1, 'name' => 'skdjwpejsdf', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'aaa',
                    'pid' => 1,
                    'name' => 'skdjwpejsdf',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => false, 'name' => false, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [Page::class => ['sdklfjweiorjoisdmnfl' => 3, 'asdsdiofusf' => 1]],
                $page,
                $dir,
                ['id' => 22, 'pid' => 1, 'name' => 'skdjwpejsdf', 'urn' => 'urn'],
                [],
            ],
            [
                [
                    'id' => 'bbb',
                    'pid' => 3,
                    'name' => 'Name1',
                    'urn' => 'urn2',
                    '@config' => [
                        'map' => ['id' => true, 'pid' => false],
                        'create' => ['pid' => true, 'name' => true, 'urn' => false],
                        'update' => ['pid' => false, 'name' => false, 'urn' => false]
                    ]
                ],
                [],
                $page,
                $dir,
                [
                    'id' => 34,
                    'pid' => 3,
                    'name' => 'Name1',
                    'urn' => 'name1',
                    'new' => true,
                    'template' => 1,
                    'inherit_template' => 1,
                    'lang' => 'ru',
                    'cache' => 1,
                    '_description_' => 'Test description',
                    'noindex' => 0,
                ],
                ['bbb' => 34],
            ],
        ];
    }


    /**
     * Тест обработки полученных данных по странице (с сохранением, кастомными полями и обновлением маппинга)
     * @param array $data Данные по странице
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Page $defaultParent Родительская страница по умолчанию для вновь создаваемых типов
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param array<string[] => mixed> $expectedTest Массив проверки полей страницы
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по страницам
     */
    #[DataProvider('processPageDataProvider')]
    public function testProcessPage(
        array $data,
        array $mapping,
        Page $defaultParent,
        $dir,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->processPage($data, $mapping, $defaultParent, $dir);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key, $key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Page::class][$key], $key);
        }
    }


    /**
     * Тест обработки полученных данных по странице — случай с категорией вне каталога
     *
     * (она не должна создаваться)
     */
    public function testProcessPageWithOrphanPage()
    {
        $interface = new Sync1CInterface();
        $mapping = [];

        $result = $interface->processPage([
            'id' => 'orphan',
            'pid' => '',
            'name' => 'Orphan page',
            'urn' => 'orphan-page',
            '@config' => [
                'map' => ['id' => true],
                'create' => ['pid' => true, 'name' => true, 'urn' => true],
                'update' => ['pid' => true, 'name' => false, 'urn' => true]
            ]
        ], $mapping, new Page(1), static::getResourcesDir() . '/');

        $this->assertEmpty($result->id);
        $this->assertEquals('Orphan page', $result->name);
        $this->assertEquals('orphan-page', $result->urn);
        $this->assertEmpty($mapping[Page::class]['orphan'] ?? null);
    }


    /**
     * Тест обработки полученных данных по странице
     * (с сохранением, кастомными полями и обновлением маппинга) - случай с удалением
     */
    public function testProcessPageWithDelete()
    {
        $interface = new Sync1CInterface();
        $mapping = [];

        $result = $interface->processPage(
            ['id' => 32, '@delete' => true, '@config' => ['map' => ['id' => false]]],
            $mapping,
            new Page(),
            static::getResourcesDir() . '/'
        );

        $this->assertInstanceOf(Page::class, $result);
        $this->assertEquals(32, $result->id);
        $this->assertTrue($result->deleted);
        $page = new Page(32);
        $this->assertEmpty($page->id);
    }


    /**
     * Провайдер данных для функции testProcessMaterial
     * @return array<[
     *             array Данные по материалу
     *             array<string[] Артикул => int ID# товара> маппинг по артикулам
     *             array<string[] Имя класса => array<
     *                 string[] Значение уникального поля => int ID# сущности
     *             >> Полный маппинг по всем классам
     *             Material_Type Родительский тип материала по умолчанию для вновь создаваемых материалов
     *             string Путь к папке с файлами для медиа-полей
     *             Page Страница по умолчанию, в которую загружаем
     *             array<string[] => mixed> Массив проверки полей материала
     *             array<string[] => int> Массив проверки записей маппинга по материалам
     *         ]>
     */
    public static function processMaterialDataProvider()
    {
        static::installTables();
        $materialType = new Material_Type(4);
        $articlesMapping = ['6dd28e9b' => 13, 'f3b61b38' => 14];
        $dir = static::getResourcesDir() . '/';
        $page = new Page(15);
        return [
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'urn' => 'urn13',
                    'pages_ids' => ['aaa', 'bbb', 'ccc'],
                    'fields' => [
                        'article' => '6dd28e9b',
                        'id:p-r-i-c-e' => 1111,
                    ],
                    '@config' => [
                        'map' => ['id' => true],
                        'create' => ['pid' => true, 'name' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => false, 'urn' => true, 'pages_ids' => true]
                    ]
                ],
                $articlesMapping,
                [
                    Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6],
                    Material_Field::class => ['p-r-i-c-e' => 26],
                    Material::class => ['sdklfjweiorjoisdmnfl' => 13],
                    Material_Type::class => ['asdsdiofusf' => 5]
                ],
                $materialType,
                $dir,
                $page,
                [
                    'id' => 13,
                    'pid' => 5,
                    'vis' => 1,
                    'name' => 'Товар 4',
                    'urn' => 'urn13',
                    'price' => 1111,
                    'pages_ids' => [4, 5, 6]
                ],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Name',
                    'urn' => 'urn14',
                    'pages_ids' => ['aaa'],
                    'fields' => [
                        'article' => 'f3b61b38',
                        'id:p-r-i-c-e' => 2222,
                    ],
                    '@config' => [
                        'map' => ['id' => true],
                        'create' => ['pid' => true, 'name' => false, 'urn' => true],
                        'update' => ['pid' => true, 'name' => false, 'urn' => true]
                    ]
                ],
                $articlesMapping,
                [
                    Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6],
                    Material_Field::class => ['p-r-i-c-e' => 26],
                    Material_Type::class => ['asdsdiofusf' => 5]
                ],
                $materialType,
                $dir,
                $page,
                [
                    'id' => 14,
                    'pid' => 5,
                    'vis' => 1,
                    'name' => 'Товар 5',
                    'urn' => 'urn14',
                    'price' => 2222,
                    'pages_ids' => [1, 4, 18, 19, 20, 21, 22, 23, 24]
                ],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Новый товар',
                    'urn' => 'new-product',
                    'pages_ids' => ['aaa', 'bbb', 'ccc'],
                    'fields' => [
                        'article' => 'newproduct',
                        'id:p-r-i-c-e' => 3333,
                    ],
                    '@config' => [
                        'map' => ['id' => true],
                    ]
                ],
                $articlesMapping,
                [
                    Page::class => ['aaa' => 4, 'bbb' => 5, 'ccc' => 6],
                    Material_Field::class => ['p-r-i-c-e' => 26],
                    Material_Type::class => ['asdsdiofusf' => 5]
                ],
                $materialType,
                $dir,
                $page,
                // 2024-03-13, AVS: поменял на динамическую переменную, т.к. количество материалов увеличилось
                [
                    'id' => static::$materialsLastId,
                    'pid' => 5,
                    'vis' => 1,
                    'name' => 'Новый товар',
                    'urn' => 'new-product',
                    'price' => 3333,
                    'pages_ids' => [4, 5, 6]
                ],
                [],
            ],
            [
                [
                    'id' => 'sdklfjweiorjoisdmnfl',
                    'pid' => 'asdsdiofusf',
                    'name' => 'Новый товар 2',
                    'urn' => 'new-product2',
                    'fields' => [
                        'article' => 'newproduct2',
                        'id:p-r-i-c-e' => 3333,
                    ],
                    '@config' => [
                        'map' => ['id' => true],
                    ]
                ],
                $articlesMapping,
                [
                    Material_Field::class => ['p-r-i-c-e' => 26],
                    Material_Type::class => ['asdsdiofusf' => 5]
                ],
                $materialType,
                $dir,
                $page,
                // 2024-03-13, AVS: поменял на динамическую переменную, т.к. количество материалов увеличилось
                [
                    'id' => static::$materialsLastId + 1,
                    'pid' => 5,
                    'vis' => 0,
                    'name' => 'Новый товар 2',
                    'urn' => 'new-product2',
                    'price' => 3333 ,
                    'pages_ids' => [15],
                    'new' => true,
                ],
                [],
            ],

        ];
    }


    /**
     * Тест обработки полученных данных по материалу (с сохранением, кастомными полями и обновлением маппинга)
     * @param array $data Данные по материалу
     * @param array<string[] Артикул => int ID# товара> $articlesMapping маппинг по артикулам
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых материалов
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param Page $page Страница по умолчанию, в которую загружаем
     * @param array<string[] => mixed> $expectedTest Массив проверки полей материала
     * @param array<string[] => int> $mappingTest Массив проверки записей маппинга по материалам
     */
    #[DataProvider('processMaterialDataProvider')]
    public function testProcessMaterial(
        array $data,
        array $articlesMapping,
        array $mapping,
        Material_Type $defaultParent,
        $dir,
        Page $page,
        array $expectedTest,
        array $mappingTest
    ) {
        $interface = new Sync1CInterface();

        $result = $interface->processMaterial($data, $articlesMapping, $mapping, $defaultParent, $dir, $page);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material::class][$key]);
        }
    }


    /**
     * Тест обработки полученных данных по материалу
     * (с сохранением, кастомными полями и обновлением маппинга) - случай с удалением
     */
    public function testProcessMaterialWithDelete()
    {
        $interface = new Sync1CInterface();
        $mapping = [];

        $result = $interface->processMaterial(
            ['id' => 17, '@delete' => true, '@config' => ['map' => ['id' => false]]],
            [],
            $mapping,
            new Material_Type(),
            static::getResourcesDir() . '/',
            new Page(1)
        );

        $this->assertInstanceOf(Material::class, $result);
        $this->assertEquals(17, $result->id);
        $this->assertTrue($result->deleted);
        $page = new Material(17);
        $this->assertEmpty($page->id);
    }


    /**
     * Тест обработки полученных данных
     */
    public function testProcessData()
    {
        $data = [
            'materialTypes' => [
                'aaa' => ['id' => 'aaa'],
                'bbb' => ['id' => 'bbb'],
            ],
            'fields' => [
                'ccc' => ['id' => 'ccc'],
            ],
            'pages' => [
                'ddd' => ['id' => 'ddd'],
                'eee' => ['id' => 'eee'],
                'fff' => ['id' => 'fff'],
            ],
            'materials' => [
                'ggg' => ['id' => 'ggg'],
                'hhh' => ['id' => 'hhh'],
                'iii' => ['id' => 'iii'],
                'jjj' => ['id' => 'jjj'],
                'kkk' => ['id' => 'kkk'],
                'lll' => ['id' => 'lll'],
                'mmm' => ['id' => 'mmm'],
            ],
        ];
        $articlesMapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $mapping = [Material::class => ['aaa' => 1]];
        $dir = static::getResourcesDir() . '';
        $mappingFile = $dir . '/mapping.json';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->onlyMethods([
                'loadMapping',
                'processMaterialType',
                'processField',
                'processPage',
                'processMaterial',
                'saveMapping'
            ])->getMock();

        $interface->expects($this->once())->method('loadMapping')->with($mappingFile)->willReturn($mapping);

        $matcherProcessMaterialType = $this->exactly(2);
        $interface->expects($matcherProcessMaterialType)->method('processMaterialType')->willReturnCallback(
            function (
                array $data,
                array &$mappingArg,
                Material_Type $defaultParent
            ) use (
                $matcherProcessMaterialType,
                $mapping,
                $materialType
            ) {
                $this->assertSame($mapping, $mappingArg);
                $this->assertSame($materialType, $defaultParent);
                switch ($matcherProcessMaterialType->numberOfInvocations()) {
                    case 1:
                        $this->assertSame(['id' => 'aaa'], $data);
                        return new Material_Type(['id' => 1, 'name' => 'AAA']);
                        break;
                    case 2:
                        $this->assertSame(['id' => 'bbb'], $data);
                        return new Material_Type(['id' => 2, 'name' => 'BBB', 'new' => true]);
                        break;
                }
            }
        );

        $interface
            ->expects($this->once())
            ->method('processField')
            ->with(['id' => 'ccc'], $mapping, $materialType)
            ->willReturn(new Material_Field(['id' => 1, 'name' => 'AAA']));

        $matcherProcessPage = $this->exactly(3);
        $interface->expects($matcherProcessPage)->method('processPage')->willReturnCallback(
            function (
                array $data,
                array &$mappingArg,
                Page $defaultParent,
                $dirArg
            ) use (
                $matcherProcessPage,
                $mapping,
                $page,
                $dir
            ) {
                $this->assertSame($mapping, $mappingArg);
                $this->assertSame($page, $defaultParent);
                $this->assertSame($dir, $dirArg);
                switch ($matcherProcessPage->numberOfInvocations()) {
                    case 1:
                        $this->assertSame(['id' => 'ddd'], $data);
                        return new Page(['id' => 1, 'name' => 'AAA']);
                        break;
                    case 2:
                        $this->assertSame(['id' => 'eee'], $data);
                        return new Page(['id' => 2, 'name' => 'BBB', 'new' => true]);
                        break;
                    case 3:
                        $this->assertSame(['id' => 'fff'], $data);
                        return new Page(['id' => 3, 'name' => 'CCC', 'deleted' => true]);
                        break;
                }
            }
        );

        $matcherProcessMaterial = $this->exactly(7);
        $interface->expects($matcherProcessMaterial)->method('processMaterial')->willReturnCallback(
            function (
                array $data,
                array $articlesMappingArg,
                array &$mappingArg,
                Material_Type $defaultParent,
                $dirArg,
                Page $pageArg
            ) use (
                $matcherProcessMaterial,
                $articlesMapping,
                $mapping,
                $materialType,
                $dir,
                $page
            ) {
                $this->assertSame($articlesMapping, $articlesMappingArg);
                $this->assertSame($mapping, $mappingArg);
                $this->assertSame($materialType, $defaultParent);
                $this->assertSame($dir, $dirArg);
                $this->assertSame($page, $pageArg);
                switch ($matcherProcessMaterial->numberOfInvocations()) {
                    case 1:
                        $this->assertSame(['id' => 'ggg'], $data);
                        return new Material(['id' => 1, 'name' => 'AAA']);
                        break;
                    case 2:
                        $this->assertSame(['id' => 'hhh'], $data);
                        return new Material(['id' => 2, 'name' => 'BBB', 'new' => true]);
                        break;
                    case 3:
                        $this->assertSame(['id' => 'iii'], $data);
                        return new Material(['id' => 3, 'name' => 'CCC', 'deleted' => true]);
                        break;
                    case 4:
                        $this->assertSame(['id' => 'jjj'], $data);
                        return new Material(['id' => 4, 'name' => 'DDD']);
                        break;
                    case 5:
                        $this->assertSame(['id' => 'kkk'], $data);
                        return new Material(['id' => 5, 'name' => 'EEE', 'new' => true]);
                        break;
                    case 6:
                        $this->assertSame(['id' => 'lll'], $data);
                        return new Material(['id' => 6, 'name' => 'FFF', 'deleted' => true]);
                        break;
                    case 7:
                        $this->assertSame(['id' => 'mmm'], $data);
                        return new Material(['id' => 7, 'name' => 'GGG']);
                        break;
                }
            }
        );
        // $matcherSaveMapping = $this->exactly(9);
        $interface->expects($this->exactly(9))->method('saveMapping')->with($mappingFile, $mapping);

        $result = $interface->processData(
            $page,
            $materialType,
            $data,
            $articlesMapping,
            $dir,
            function ($x) use (&$log) {
                $log[] = $x;
            },
            $mappingFile,
            2
        );

        $this->assertEquals([
            Material_Type::class => [1 => 1, 2 => 2],
            Material_Field::class => [1 => 1],
            Page::class => [1 => 1, 2 => 2, 3 => 3],
            Material::class => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7],
        ], $result);
        $this->assertEquals([
            'Updated Material_Type #1 (AAA) - 1/2',
            'Created Material_Type #2 (BBB) - 2/2',
            'Updated Material_Field #1 (AAA) - 1/1',
            'Updated Page #1 (AAA) - 1/3',
            'Created Page #2 (BBB) - 2/3',
            'Deleted Page #3 (CCC) - 3/3',
            'Updated Material #1 (AAA) - 1/7',
            'Created Material #2 (BBB) - 2/7',
            'Deleted Material #3 (CCC) - 3/7',
            'Updated Material #4 (DDD) - 4/7',
            'Created Material #5 (EEE) - 5/7',
            'Deleted Material #6 (FFF) - 6/7',
            'Updated Material #7 (GGG) - 7/7',
        ], $log);
    }


    /**
     * Тест обработки полученных данных (случай с отсутствием $saveMappingAfterIterations)
     */
    public function testProcessDataWithNoSaveMappingAfterIterations()
    {
        $data = [
            'materialTypes' => [
                'aaa' => ['id' => 'aaa'],
                'bbb' => ['id' => 'bbb'],
            ],
            'fields' => [
                'ccc' => ['id' => 'ccc'],
            ],
            'pages' => [
                'ddd' => ['id' => 'ddd'],
                'eee' => ['id' => 'eee'],
                'fff' => ['id' => 'fff'],
            ],
            'materials' => [
                'ggg' => ['id' => 'ggg'],
                'hhh' => ['id' => 'hhh'],
                'iii' => ['id' => 'iii'],
                'jjj' => ['id' => 'jjj'],
                'kkk' => ['id' => 'kkk'],
                'lll' => ['id' => 'lll'],
                'mmm' => ['id' => 'mmm'],
            ],
        ];
        $articlesMapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $dir = static::getResourcesDir() . '';
        $mappingFile = $dir . '/mapping.json';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)->onlyMethods(['saveMapping'])->getMock();

        $interface->expects($this->exactly(4))->method('saveMapping');

        $result = $interface->processData(
            $page,
            $materialType,
            $data,
            $articlesMapping,
            $dir,
            function ($x) use (&$log) {
                $log[] = $x;
            },
            $mappingFile,
            0
        );
    }


    /**
     * Тест обработки полученных данных (случай с отсутствием $mappingFile)
     */
    public function testProcessDataWithNoMappingFile()
    {
        $data = [
            'materialTypes' => [
                'aaa' => ['id' => 'aaa'],
                'bbb' => ['id' => 'bbb'],
            ],
            'fields' => [
                'ccc' => ['id' => 'ccc'],
            ],
            'pages' => [
                'ddd' => ['id' => 'ddd'],
                'eee' => ['id' => 'eee'],
                'fff' => ['id' => 'fff'],
            ],
            'materials' => [
                'ggg' => ['id' => 'ggg'],
                'hhh' => ['id' => 'hhh'],
                'iii' => ['id' => 'iii'],
                'jjj' => ['id' => 'jjj'],
                'kkk' => ['id' => 'kkk'],
                'lll' => ['id' => 'lll'],
                'mmm' => ['id' => 'mmm'],
            ],
        ];
        $articlesMapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $dir = static::getResourcesDir() . '';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)->onlyMethods(['saveMapping'])->getMock();

        $interface->expects($this->never())->method('saveMapping');

        $result = $interface->processData(
            $page,
            $materialType,
            $data,
            $articlesMapping,
            $dir,
            function ($x) use (&$log) {
                $log[] = $x;
            },
            null,
            2
        );
    }


    /**
     * Тест удаления материалов
     */
    public function testClearMaterials()
    {
        $interface = new Sync1CInterface();
        $log = [];
        $item = new Material(15);
        $attachment = new Attachment(46);

        $this->assertEquals(15, $item->id);
        $this->assertEquals(46, $attachment->id);

        $interface->clearMaterials(
            new Material_Type(4),
            new Page(15),
            [10, 11, 12, 13, 14, 16, 17, 18, 19, 20, 21],
            function ($x) use (&$log) {
                $log[] = $x;
            }
        );

        $item = new Material(15);
        $attachment = new Attachment(46);

        $this->assertEmpty($item->id);
        $this->assertEmpty($attachment->id);
        $this->assertStringContainsString('Start clearing old materials', $log[0] ?? '');
        $this->assertStringContainsString('Deleted Material #15 (Товар 6) - 1/1', $log[1] ?? '');
    }


    /**
     * Тест удаления страниц
     */
    public function testClearPages()
    {
        $interface = new Sync1CInterface();
        $log = [];
        $page = new Page(24);

        $this->assertEquals(24, $page->id);

        $interface->clearPages(
            new Page(15),
            [16, 17, 18, 19, 20, 21, 22, 23],
            function ($x) use (&$log) {
                $log[] = $x;
            }
        );

        $page = new Page(24);

        $this->assertEmpty($page->id ?? null);
        $this->assertStringContainsString('Start clearing old pages', $log[0] ?? '');
        $this->assertStringContainsString('Deleted Page #24 (Категория 3) - 1/1', $log[1] ?? '');
    }


    /**
     * Тест удаления страниц/материалов
     */
    public function testClear()
    {
        $affected = [Material::class => [1, 2, 3], Page::class => [10, 11, 12]];
        $articlesMapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $mapping = [];
        $dir = static::getResourcesDir() . '';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $logger = function ($x) use (&$log) {
            $log[] = $x;
        };
        $clear = Sync1CInterface::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES;
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->onlyMethods(['clearMaterials', 'clearPages'])
            ->getMock();

        $interface->expects($this->once())->method('clearMaterials')->with($materialType, $page, [1, 2, 3], $logger);
        $interface->expects($this->once())->method('clearPages')->with($page, [10, 11, 12], $logger);

        $result = $interface->clear($page, $materialType, $clear, $logger, $affected);
    }


    /**
     * Тест загрузки прайса на сервер
     */
    public function testProcess()
    {
        $page = new Page(15);
        $materialType = new Material_Type(4);
        $goodsFile = static::getResourcesDir() . '/import0_1.xml';
        $offersFile = static::getResourcesDir() . '/offers0_1.xml';
        $goodsXSLFile = static::getResourcesDir() . '/import.xsl';
        $offersXSLFile = static::getResourcesDir() . '/offers.xsl';
        $mappingFile = static::getResourcesDir() . '/mapping.json';
        $logger = function ($x) {
        };
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->onlyMethods(['loadData', 'loadMapping', 'getArticlesMapping', 'processData', 'clear'])
            ->getMock();

        $interface->method('loadData')->willReturn(['materials' => ['id' => 'aaa']]);
        $interface->expects($this->once())
            ->method('loadData')
            ->with($goodsFile, $offersFile, $goodsXSLFile, $offersXSLFile);
        $interface->method('getArticlesMapping')->willReturn(['asdjkfh' => 1]);
        $interface->expects($this->once())->method('getArticlesMapping')->with($materialType, 'article');
        $interface->method('processData')->willReturn(
            [Material::class => [1, 2, 3], Page::class => [10, 20, 30], Attachment::class => [100, 200, 300]]
        );
        $interface->expects($this->once())->method('processData')->with(
            $page,
            $materialType,
            ['materials' => ['id' => 'aaa']],
            ['asdjkfh' => 1],
            static::getResourcesDir(),
            $logger,
            $mappingFile,
            100
        );

        $result = $interface->process(
            $page,
            $materialType,
            $goodsFile,
            $offersFile,
            $goodsXSLFile,
            $offersXSLFile,
            $mappingFile,
            'article',
            static::getResourcesDir(),
            Sync1CInterface::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
            $logger,
            100
        );
    }
}
