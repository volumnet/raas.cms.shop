<?php
/**
 * Тест класса EditImageLoaderForm
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use RAAS\Application;
use RAAS\Field as RAASField;
use RAAS\FormTab;
use RAAS\User as RAASUser;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Тест класса EditImageLoaderForm
 * @covers RAAS\CMS\Shop\EditImageLoaderForm
 */
class EditImageLoaderFormTest extends BaseTest
{
    public static $tables = [
        'cms_fields',
        'cms_material_types',
        'cms_pages',
        'cms_shop_imageloaders',
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
        $form = new EditImageLoaderForm();
        $interfaceField = $form->children['interface_id'];
        $snippet = Snippet::importByURN('__raas_shop_imageloader_interface');

        $this->assertInstanceOf(RAASField::class, $interfaceField);
        $this->assertEquals($snippet->id, $interfaceField->default);
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
        $form = new EditImageLoaderForm(['Item' => new ImageLoader(1)]);

        $result = $form->Item;

        $this->assertInstanceOf(ImageLoader::class, $result);
        $this->assertEquals(1, $result->id);
    }


    /**
     * Тест конструктора класса - случай с существующим загрузчиком
     */
    public function testConstructWithExisting()
    {
        $form = new EditImageLoaderForm(['Item' => new ImageLoader(1)]);
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
            'interface_id' => 26, // Сниппет интерфейса загрузчика изображений
            'ifid' => 27, // Изображение
            'ufid' => 25,
            'sep_string' => '_',
        ];
        $loader = new ImageLoader();
        $form = new EditImageLoaderForm(['Item' => $loader]);
        $form->process();
        $loader->reload();

        $this->assertEquals('Тестовый загрузчик', $loader->name);
        $this->assertEquals('test', $loader->urn);
        $this->assertEquals(4, $loader->mtype);
        $this->assertEquals(26, $loader->interface_id);
        $this->assertEquals(25, $loader->ufid);
        $this->assertEquals(27, $loader->ifid);
        $this->assertEquals('_', $loader->sep_string);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        ImageLoader::delete($loader);
    }
}
