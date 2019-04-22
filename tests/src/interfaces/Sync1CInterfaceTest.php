<?php
/**
 * Файл теста интерфейса синхронизации с 1С
 */
namespace RAAS\CMS\Shop;

use SimpleXMLElement;
use SOME\SOME;
use RAAS\Attachment;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Field;

/**
 * Класс теста интерфейса синхронизации с 1С
 */
class Sync1CInterfaceTest extends BaseDBTest
{
    /**
     * Перестройка перед тестом
     */
    public static function setUpBeforeClass()
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
        $goodsFile = $this->getResourcesDir() . '/import0_1.update.xml';
        $goodsXSLFile = $this->getResourcesDir() . '/import.xsl';
        $offersFile = $this->getResourcesDir() . '/offers0_1.update.xml';
        $offersXSLFile = $this->getResourcesDir() . '/offers.xsl';

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
    public function isSpecialFieldDataProvider()
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
     * @dataProvider isSpecialFieldDataProvider
     */
    public function testIsSpecialField($data, $key, $condition, $defaultValue, $expected)
    {
        $interface = new Sync1CInterface();

        $result = $interface->isSpecialField($data, $key, $condition, $defaultValue);

        $this->assertEquals($expected, $result);
    }


    /**
     * Провайдер данных для теста поиска сущности по полю
     * @return array<[
     *             string Класс сущности
     *             string Наименование нативного поля
     *             mixed Значение поля
     *             array<string[] поле => mixed значение> Набор дополнительных условий для выборки
     *             int ID# выбранной сущности
     *         ]>
     */
    public function findEntityByFieldDataProvider()
    {
        return [
            [Material_Type::class, 'name', 'Особые товары', [], 5],
            [Material_Type::class, 'name', 'Особые товары', ['pid' => 4], 5],
            [Material_Type::class, 'name', 'Особые товары', ['pid' => 0], null],
            [Page::class, 'name', 'Категория 111', [], 18],
            [Page::class, 'name', 'Категория 111', ['pid' => 17], 18],
            [Page::class, 'name', 'Категория 111', ['pid' => 1], null],
            [Material::class, 'name', 'Товар 1', [], 10],
            [Material::class, 'name', 'Товар 1', [
                "SELECT COUNT(*) FROM cms_materials_pages_assoc WHERE id = Material.id AND pid = 18"
            ], 10 ],
            [Material::class, 'name', 'Товар 1', [
                "SELECT COUNT(*) FROM cms_materials_pages_assoc WHERE id = Material.id AND pid = 17"
            ], null],
            [Material_Field::class, 'name', '', [], null],
            [Material_Field::class, 'name', null, [], null],
            [Material_Field::class, 'name', 'Особое поле', ['pid' => [4, 5]], 48],
        ];
    }


    /**
     * Тест поиска сущности по полю
     * @param string $classname Класс сущности
     * @param string $fieldName Наименование нативного поля
     * @param mixed $value Значение поля
     * @param array<string[] поле => mixed значение> $context Набор дополнительных условий для выборки
     * @param int $expectedId ID# сущности
     * @dataProvider findEntityByFieldDataProvider
     */
    public function testFindEntityByField($classname, $fieldName, $value, array $context, $expectedId)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findEntityByField($classname, $fieldName, $value, $context);

        $this->assertEquals($expectedId, $result->id);
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
        $filename = $this->getResourcesDir() . '/aaa.json';
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
        $filename = $this->getResourcesDir() . '/bbb.json';

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
        $filename = $this->getResourcesDir() . '/aaa.json';

        $interface->saveMapping($filename, $mapping);
        $result = (array)json_decode(file_get_contents($filename), true);
        unlink($filename);

        $this->assertEquals($mapping, $result);
    }


    /**
     * Провайдер данных для метода testFindEntityById
     * @return array<[
     *             string Класс сущности
     *             array Набор данных
     *             string Ключ, по значению которого ищем
     *             array Полный маппинг по всем классам
     *             int|null ID# объекта класса $classname, либо null, если не найден
     *         ]>
     */
    public function findEntityByIdDataProvider()
    {
        return [
            [
                Material_Type::class,
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                'id',
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 5]],
                5
            ],
            [
                Material_Type::class,
                ['pid' => 4, '@config' => ['map' => ['pid' => false]]],
                'pid',
                [Page::class => ['sdklfjweiorjoisdmnfl' => 5]],
                4
            ],
            [
                Material_Type::class,
                ['id' => 'zxjhcoihwoer', '@config' => ['map' => ['pid' => true]]],
                'pid',
                [Page::class => ['sdklfjweiorjoisdmnfl' => 5]],
                null
            ],
            [
                Material_Type::class,
                ['id' => 'sdklfjweiorjoisdmnfl', '@config' => ['map' => ['id' => true]]],
                'id',
                [Material_Type::class => ['sdklfjweiorjoisdmnfl' => 999]],
                null
            ],
        ];
    }


    /**
     * Тест Ищем сущность по ID# данных по маппингу
     * @param string $classname Класс сущности
     * @param array $data Набор данных
     * @param string $fieldName Ключ, по значению которого ищем
     * @param array $mapping Полный маппинг по всем классам
     * @param int|null $expectedId ID# объекта класса $classname, либо null, если не найден
     * @dataProvider findEntityByIdDataProvider
     */
    public function testFindEntityById($classname, array $data, $fieldName, array $mapping, $expectedId)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findEntityById($classname, $data, $fieldName, $mapping, $context);

        $this->assertEquals($expectedId, $result->id);
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
    public function findOrCreateEntityDataProvider()
    {
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
     * @dataProvider findOrCreateEntityDataProvider
     */
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
    public function updateEntityDataProvider()
    {
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
     * @dataProvider updateEntityDataProvider
     */
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
    public function updatePagesDataProvider()
    {
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
     * @dataProvider updatePagesDataProvider
     */
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
    public function updateCustomFieldDataProvider()
    {
        $material = new Material(10);
        $dir = $this->getResourcesDir() . '';
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
     * @dataProvider updateCustomFieldDataProvider
     */
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
    public function updateCustomFieldsDataProvider()
    {
        $dir = $this->getResourcesDir() . '';
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
     * @dataProvider updateCustomFieldsDataProvider
     */
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
    public function testUpdateCustomFieldsWithEntityWithNoField(SOME $entity, $new, array $data, array $mapping, $dir, array $expectedTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->updateCustomFields($entity, $new, ['price' => 'aaa'], [], $this->getResourcesDir());

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
    public function findOrCreateMaterialTypeDataProvider()
    {
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
     * @dataProvider findOrCreateMaterialTypeDataProvider
     */
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
            $this->assertEquals($val, $mapping[Material_Type::class][$key]);
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
    public function findOrCreateFieldDataProvider()
    {
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
     * @dataProvider findOrCreateFieldDataProvider
     */
    public function testFindOrCreateField(array $data, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateField($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material_Field::class][$key]);
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
    public function findOrCreatePageDataProvider()
    {
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
     * @dataProvider findOrCreatePageDataProvider
     */
    public function testFindOrCreatePage(array $data, array $mapping, Page $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreatePage($data, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Page::class][$key]);
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
    public function findOrCreateMaterialDataProvider()
    {
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
     * @dataProvider findOrCreateMaterialDataProvider
     */
    public function testFindOrCreateMaterial(array $data, array $articlesMapping, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
        $interface = new Sync1CInterface();

        $result = $interface->findOrCreateMaterial($data, $articlesMapping, $mapping, $defaultParent);

        foreach ($expectedTest as $key => $val) {
            $this->assertEquals($val, $result->$key);
        }
        foreach ($mappingTest as $key => $val) {
            $this->assertEquals($val, $mapping[Material::class][$key]);
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
    public function processMaterialTypeDataProvider()
    {
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
                ['id' => 6, 'pid' => 0, 'name' => 'Name1', 'urn' => 'name1', 'new' => true],
                ['bbb' => 6],
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
     * @dataProvider processMaterialTypeDataProvider
     */
    public function testProcessMaterialType(array $data, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
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
    public function processFieldDataProvider()
    {
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
                [
                    Material_Field::class => ['sdklfjweiorjoisdmnfl' => 34],
                    Material_Type::class => ['asdsdiofusf' => 1]
                ],
                $materialType,
                ['id' => 34, 'pid' => 0, 'name' => 'Name1', 'urn' => 'price_old'],
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
                ['id' => 34, 'pid' => 0, 'name' => 'Name1', 'urn' => 'price_old'],
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
                ['id' => 49, 'pid' => 0, 'name' => 'Name1', 'urn' => 'name1', 'new' => true],
                ['bbb' => 49],
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
     * @dataProvider processFieldDataProvider
     */
    public function testProcessField(array $data, array $mapping, Material_Type $defaultParent, array $expectedTest, array $mappingTest)
    {
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
    public function processPageDataProvider()
    {
        $page = new Page(1);
        $dir = $this->getResourcesDir() . '/';
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
     * @dataProvider processPageDataProvider
     */
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
        ], $mapping, new Page(1), $this->getResourcesDir() . '/');

        $this->assertEmpty($result->id);
        $this->assertEquals('Orphan page', $result->name);
        $this->assertEquals('orphan-page', $result->urn);
        $this->assertEmpty($mapping[Page::class]['orphan']);
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
            new Material_Type()
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
    public function processMaterialDataProvider()
    {
        $materialType = new Material_Type(4);
        $articlesMapping = ['6dd28e9b' => 13, 'f3b61b38' => 14];
        $dir = $this->getResourcesDir() . '/';
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
                [
                    'id' => 20,
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
                [
                    'id' => 21,
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
     * @dataProvider processMaterialDataProvider
     */
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
            $this->getResourcesDir() . '/',
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
        $dir = $this->getResourcesDir() . '';
        $mappingFile = $dir . '/mapping.json';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->setMethods([
                'loadMapping',
                'processMaterialType',
                'processField',
                'processPage',
                'processMaterial',
                'saveMapping'
            ])->getMock();

        $interface->method('loadMapping')->willReturn($mapping);
        $interface->expects($this->once())->method('loadMapping')->with($mappingFile);
        $interface->method('processMaterialType')->will($this->onConsecutiveCalls(
            new Material_Type(['id' => 1, 'name' => 'AAA']),
            new Material_Type(['id' => 2, 'name' => 'BBB', 'new' => true])
        ));
        $interface->expects($this->exactly(2))->method('processMaterialType')->withConsecutive(
            [['id' => 'aaa'], $mapping, $materialType],
            [['id' => 'bbb'], $mapping, $materialType]
        );
        $interface->method('processField')->will($this->onConsecutiveCalls(
            new Material_Field(['id' => 1, 'name' => 'AAA'])
        ));
        $interface->expects($this->once())->method('processField')->withConsecutive(
            [['id' => 'ccc'], $mapping, $materialType]
        );
        $interface->method('processPage')->will($this->onConsecutiveCalls(
            new Page(['id' => 1, 'name' => 'AAA']),
            new Page(['id' => 2, 'name' => 'BBB', 'new' => true]),
            new Page(['id' => 3, 'name' => 'CCC', 'deleted' => true])
        ));
        $interface->expects($this->exactly(3))->method('processPage')->withConsecutive(
            [['id' => 'ddd'], $mapping, $page, $dir],
            [['id' => 'eee'], $mapping, $page, $dir],
            [['id' => 'fff'], $mapping, $page, $dir]
        );
        $interface->method('processMaterial')->will($this->onConsecutiveCalls(
            new Material(['id' => 1, 'name' => 'AAA']),
            new Material(['id' => 2, 'name' => 'BBB', 'new' => true]),
            new Material(['id' => 3, 'name' => 'CCC', 'deleted' => true]),
            new Material(['id' => 4, 'name' => 'DDD']),
            new Material(['id' => 5, 'name' => 'EEE', 'new' => true]),
            new Material(['id' => 6, 'name' => 'FFF', 'deleted' => true]),
            new Material(['id' => 7, 'name' => 'GGG'])
        ));
        $interface->expects($this->exactly(7))->method('processMaterial')->withConsecutive(
            [['id' => 'ggg'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'hhh'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'iii'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'jjj'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'kkk'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'lll'], $articlesMapping, $mapping, $materialType, $dir, $page],
            [['id' => 'mmm'], $articlesMapping, $mapping, $materialType, $dir, $page]
        );
        $interface->expects($this->exactly(9))->method('saveMapping')->withConsecutive(
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping],
            [$mappingFile, $mapping]
        );

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
        $dir = $this->getResourcesDir() . '';
        $mappingFile = $dir . '/mapping.json';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->setMethods([
                'processMaterialType',
                'processField',
                'processPage',
                'processMaterial',
                'saveMapping'
            ])->getMock();

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
        $dir = $this->getResourcesDir() . '';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->setMethods([
                'processMaterialType',
                'processField',
                'processPage',
                'processMaterial',
                'saveMapping'
            ])->getMock();

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
        $this->assertContains('Start clearing old materials', $log[0]);
        $this->assertContains('Deleted Material #15 (Товар 6) - 1/1', $log[1]);
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

        $this->assertEmpty($page->id);
        $this->assertContains('Start clearing old pages', $log[0]);
        $this->assertContains('Deleted Page #24 (Категория 3) - 1/1', $log[1]);
    }


    /**
     * Тест удаления страниц/материалов
     */
    public function testClear()
    {
        $affected = [Material::class => [1, 2, 3], Page::class => [10, 11, 12]];
        $articlesMapping = ['aaa' => 'xxx', 'bbb' => 'yyy', 'ccc' => 'zzz'];
        $mapping = [];
        $dir = $this->getResourcesDir() . '';
        $log = [];
        $materialType = new Material_Type(4);
        $page = new Page(15);
        $logger = function ($x) use (&$log) {
            $log[] = $x;
        };
        $clear = Sync1CInterface::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES;
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->setMethods(['clearMaterials', 'clearPages'])
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
        $goodsFile = $this->getResourcesDir() . '/import0_1.xml';
        $offersFile = $this->getResourcesDir() . '/offers0_1.xml';
        $goodsXSLFile = $this->getResourcesDir() . '/import.xsl';
        $offersXSLFile = $this->getResourcesDir() . '/offers.xsl';
        $mappingFile = $this->getResourcesDir() . '/mapping.json';
        $logger = function ($x) {
        };
        $interface = $this->getMockBuilder(Sync1CInterface::class)
            ->setMethods(['loadData', 'loadMapping', 'getArticlesMapping', 'processData', 'clear'])
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
            $this->getResourcesDir(),
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
            $this->getResourcesDir(),
            Sync1CInterface::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES,
            $logger,
            100
        );
    }
}
