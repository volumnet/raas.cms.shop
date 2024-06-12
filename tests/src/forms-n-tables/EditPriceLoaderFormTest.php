<?php
/**
 * Тест класса EditPriceLoaderForm
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\FormTab;
use RAAS\User as RAASUser;
use RAAS\CMS\InterfaceField;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса EditPriceLoaderForm
 * @covers RAAS\CMS\Shop\EditPriceLoaderForm
 */
class EditPriceLoaderFormTest extends BaseTest
{
    public static $tables = [
        'cms_fields',
        'cms_material_types',
        'cms_pages',
        'cms_shop_priceloaders',
        'cms_shop_priceloaders_columns',
        'cms_snippet_folders',
        'cms_snippets',
    ];


    public static function setUpBeforeClass(): void
    {
        Application::i()->initPackages();
        parent::setUpBeforeClass();
    }


    /**
     * Тест конструктора класса
     */
    public function testConstruct()
    {
        $form = new EditPriceLoaderForm();
        $interfaceField = $form->children['interface_id'];

        $this->assertInstanceOf(InterfaceField::class, $interfaceField);
        $this->assertEquals(PriceloaderInterface::class, $interfaceField->default);
        $this->assertEquals(PriceloaderInterface::class, $interfaceField->meta['rootInterfaceClass']);
    }


    /**
     * Тест получения свойства view
     */
    public function testGetView()
    {
        $form = new EditPriceLoaderForm();

        $result = $form->view;

        $this->assertInstanceOf(ViewSub_Dev::class, $result);
    }


    /**
     * Тест получения наследуемых свойств
     */
    public function testGetDefault()
    {
        $form = new EditPriceLoaderForm(['Item' => new PriceLoader(1)]);

        $result = $form->Item;

        $this->assertInstanceOf(PriceLoader::class, $result);
        $this->assertEquals(1, $result->id);
    }


    /**
     * Тест конструктора класса - случай с существующим загрузчиком
     */
    public function testConstructWithExisting()
    {
        $form = new EditPriceLoaderForm(['Item' => new PriceLoader(1)]);
        $form->process();

        $this->assertEquals(4, $form->DATA['mtype']); // Каталог продукции
    }


    /**
     * Тест конструктора класса - случай экспорта
     */
    public function testConstructWithExport()
    {
        $oldPost = $_POST;
        $oldServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'name' => 'Тестовый загрузчик',
            'urn' => 'test',
            'mtype' => 4, // Каталог продукции
            'cat_id' => 15, // Каталог продукции
            'interface_id' => PriceloaderInterface::class,
            'update_materials' => 1,
            'cats_usage' => 2, // Не использовать категории
            'media_action' => 1, // Добавлять только к новым материалам
            'rows' => 1,
            'column_id' => ['', ''], // Для определения, что это новая колонка
            'column_fid' => [25, 'name'], // Артикул, наименование
            'ufid' => 25,
        ];
        $loader = new PriceLoader();
        $form = new EditPriceLoaderForm(['Item' => $loader]);
        $form->process();
        $loader->reload();

        $this->assertEquals('Тестовый загрузчик', $loader->name);
        $this->assertEquals('test', $loader->urn);
        $this->assertEquals(4, $loader->mtype);
        $this->assertEquals(15, $loader->cat_id);
        $this->assertEquals(PriceloaderInterface::class, $loader->interface_classname);
        $this->assertEquals(1, $loader->update_materials);
        $this->assertEquals(2, $loader->cats_usage);
        $this->assertEquals(1, $loader->media_action);
        $this->assertEquals(1, $loader->rows);
        $this->assertEquals(25, $loader->ufid);
        $this->assertCount(2, $loader->columns);
        $this->assertEquals(25, $loader->columns[0]->fid);
        $this->assertEquals('name', $loader->columns[1]->fid);
        $col1Id = $loader->columns[0]->id;
        $col2Id = $loader->columns[1]->id;

        $_POST = [
            'name' => 'Тестовый загрузчик',
            'urn' => 'test',
            'mtype' => 4, // Каталог продукции
            'cat_id' => 15, // Каталог продукции
            'interface_id' => 27, // Сниппет интерфейса загрузчика прайсов
            'update_materials' => 1,
            'cats_usage' => 2, // Не использовать категории
            'media_action' => 1, // Добавлять только к новым материалам
            'rows' => 1,
            'column_id' => [$col1Id, ''], // Для определения, что это новая колонка
            'column_fid' => [25, 26], // Артикул, стоимость
            'ufid' => 25,
        ];
        $form->process();
        $loader->reload();

        $this->assertCount(2, $loader->columns);
        $this->assertEquals(25, $loader->columns[0]->fid);
        $this->assertEquals($col1Id, $loader->columns[0]->id);
        $this->assertEquals(26, $loader->columns[1]->fid);
        $this->assertNotEquals($col2Id, $loader->columns[1]->id);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        PriceLoader::delete($loader);
    }
}
