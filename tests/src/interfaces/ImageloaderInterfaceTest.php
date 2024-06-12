<?php
/**
 * Файл теста интерфейса загрузчика изображений
 */
namespace RAAS\CMS\Shop;

use SOME\BaseTest;
use SOME\CSV;
use SOME\ZipArchive;
use RAAS\Attachment;
use RAAS\Exception;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс теста интерфейса загрузчика изображений
 * @covers RAAS\CMS\Shop\ImageloaderInterface
 */
class ImageloaderInterfaceTest extends BaseTest
{
    public static $tables = [
        'attachments',
        'cms_data',
        'cms_fields',
        'cms_material_types',
        'cms_materials',
        'cms_shop_imageloaders',
        'cms_shop_priceloaders',
        'cms_snippets',
        'cms_templates',
        'registry',
    ];

    /**
     * Получает интерфейс загрузчика изображений
     * @return ImageLoader
     */
    public function getInterface()
    {
        $loader = new ImageLoader(1);
        $interface = new ImageloaderInterface($loader);
        $interface->debug = true;
        return $interface;
    }


    /**
     * Чистит тестовые вложения у материалов 10, 11
     */
    public function clearMaterialAssets()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $field = $loader->Image_Field;
        $materials = Material::getSet(['where' => "pid = 4", 'orderBy' => "id"]); // 4 - каталог продукции
        foreach ($materials as $material) {
            $material->fields['images']->deleteValues();
        }
        $field->clearLostAttachments();
    }


    /**
     * Добавляет тестовые вложения у материалов 10, 11
     */
    public function addMaterialAssets()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $field = $loader->Image_Field;
        $maxsize = (int)Package::i()->registryGet('maxsize');
        $tnsize = (int)Package::i()->registryGet('tnsize');
        $dir = Module::i()->resourcesDir . '/fish/products';

        $this->clearMaterialAssets();

        $material1 = new Material(10);
        $material2 = new Material(11);

        $att1 = Attachment::createFromFile($dir . '/1.jpg', $field, $maxsize, $tnsize, 'image/jpeg');
        $att2 = Attachment::createFromFile($dir . '/2.jpg', $field, $maxsize, $tnsize, 'image/jpeg');
        $material1->fields['images']->addValue(
            json_encode(['vis' => 1, 'name' => '', 'description' => '', 'attachment' => $att1->id])
        );
        $material1->fields['images']->addValue(
            json_encode(['vis' => 1, 'name' => '', 'description' => '', 'attachment' => $att2->id])
        );
        $att3 = Attachment::createFromFile($dir . '/3.jpg', $field, $maxsize, $tnsize, 'image/jpeg');
        $att4 = Attachment::createFromFile($dir . '/4.jpg', $field, $maxsize, $tnsize, 'image/jpeg');
        $material2->fields['images']->addValue(
            json_encode(['vis' => 1, 'name' => '', 'description' => '', 'attachment' => $att3->id])
        );
        $material2->fields['images']->addValue(
            json_encode(['vis' => 1, 'name' => '', 'description' => '', 'attachment' => $att4->id])
        );
    }


    /**
     * Тест функции process (заглушки)
     */
    public function testProcess()
    {
        $interface = $this->getInterface();

        $result = $interface->process();

        $this->assertNull($result);
    }


    /**
     * Тест получения загрузчика
     */
    public function testTestGetLoader()
    {
        $loader = new ImageLoader(1);

        $interface = new ImageloaderInterface($loader);

        $this->assertEquals($loader, $interface->loader);
    }


    /**
     * Тест получения несуществующего свойства
     */
    public function testTestGetWithNonExistingProperty()
    {
        $loader = new ImageLoader(1);

        $interface = new ImageloaderInterface($loader);

        $this->assertNull($interface->aaa);
    }


    /**
     * Тест метода getItemsByUniqueField()
     */
    public function testGetItemsByUniqueField()
    {
        $interface = $this->getInterface();

        $result = $interface->getItemsByUniqueField($interface->loader, 'f4dbdf21');

        $this->assertCount(1, $result);
        $this->assertInstanceOf(Material::class, $result[0]);
        $this->assertEquals(10, $result[0]->id);
    }


    /**
     * Тест метода getItemsByUniqueField() - случай с некорректным указанием типа материалов
     */
    public function testGetItemsByUniqueFieldWithInvalidTypeLoader()
    {
        $interface = new ImageloaderInterface(new ImageLoader(['mtype' => 9999, 'ufid' => 'name']));

        $result = $interface->getItemsByUniqueField($interface->loader, 'f4dbdf21');

        $this->assertCount(0, $result);
    }


    /**
     * Тест метода getItemsByUniqueField() - случай без указания уникального поля
     */
    public function testGetItemsByUniqueFieldWithNoUniqueField()
    {
        $interface = new ImageloaderInterface(new ImageLoader());

        $result = $interface->getItemsByUniqueField($interface->loader, 'f4dbdf21');

        $this->assertCount(0, $result);
    }


    /**
     * Тест метода clear()
     */
    public function testClear()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $this->clearMaterialAssets();

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertEmpty($material1->images);
        $this->assertEmpty($material2->images);

        $this->addMaterialAssets();

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertCount(2, $material1->images);
        $this->assertCount(2, $material2->images);

        $log = [];

        $interface->clear($interface->loader, $log, [10, 11], false);

        // Здесь только удаляет ссылки, основная очистка происходит в upload(), поэтому здесь не проверяем
        // отсутствие самих вложений

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertEmpty($material1->images);
        $this->assertEmpty($material2->images);

        $this->assertCount(1, $log);
    }


    /**
     * Тест метода clear() - тестовый режим
     */
    public function testClearWithTest()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $this->clearMaterialAssets();

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertEmpty($material1->images);
        $this->assertEmpty($material2->images);

        $this->addMaterialAssets();

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertCount(2, $material1->images);
        $this->assertCount(2, $material2->images);

        $log = [];

        $interface->clear($interface->loader, $log, [10, 11], true);

        // Здесь только удаляет ссылки, основная очистка происходит в upload(), поэтому здесь не проверяем
        // отсутствие самих вложений

        $material1 = new Material(10);
        $material2 = new Material(11);
        $this->assertCount(2, $material1->images);
        $this->assertCount(2, $material2->images);

        $this->assertCount(3, $log);
        $this->assertStringContainsString('edit_material&id=10', $log[0]['text']);
        $this->assertStringContainsString('edit_material&id=11', $log[1]['text']);

        $this->clearMaterialAssets();
    }


    /**
     * Тест метода applyFile()
     */
    public function testApplyFile()
    {
        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $fileData = [
            'name' => '1.jpg',
            'originalName' => '1.jpg',
            'tmp_name' => Module::i()->resourcesDir . '/fish/products/1.jpg',
            'type' => 'image/jpeg',
            'materials' => [new Material(10)],
        ];
        $log = [];

        $result = $interface->applyFile($fileData, $loader, $log, false);

        $this->assertInstanceOf(Attachment::class, $result);

        $material = new Material(10);
        $images = $material->images;

        $this->assertCount(1, $images);
        $this->assertEquals($result->id, $images[0]->id);

        $this->assertCount(1, $log);
        $this->assertStringContainsString('edit_material&id=10', $log[0]['text']);

        $this->clearMaterialAssets();
    }


    /**
     * Тест метода processFile()
     */
    public function testProcessFile()
    {
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $fileData = [
            'name' => 'f4dbdf21_1.jpg',
            'tmp_name' => Module::i()->resourcesDir . '/fish/products/1.jpg',
        ];
        $affectedMaterialsIds = [];

        $result = $interface->processFile($fileData, $loader, $affectedMaterialsIds);

        $this->assertEquals('f4dbdf21_1.jpeg', $result[0]['name']);
        $this->assertEquals('f4dbdf21_1.jpg', $result[0]['originalName']);
        $this->assertEquals(Module::i()->resourcesDir . '/fish/products/1.jpg', $result[0]['tmp_name']);
        $this->assertEquals('image/jpeg', $result[0]['type']);
        $this->assertCount(1, $result[0]['materials']);
        $this->assertEquals(10, $result[0]['materials'][0]->id);
        $this->assertEquals([10 => 10], $affectedMaterialsIds);
    }


    /**
     * Тест метода processFile() - случай с ZIP-файлом
     */
    public function testProcessFileWithZip()
    {
        $tmpname = tempnam(sys_get_temp_dir(), '');
        unlink($tmpname);
        $zip = new ZipArchive();
        $zip->open($tmpname, ZipArchive::CREATE);
        $dir = Module::i()->resourcesDir . '/fish/products';
        $zip->addFile($dir . '/1.jpg', 'f4dbdf21_1.jpg');
        $zip->addFile($dir . '/2.jpg', 'f4dbdf21_2.jpg');
        $zip->addFile($dir . '/3.jpg', '83dcefb7_1.jpg');
        $zip->addFile($dir . '/4.jpg', '83dcefb7_2.jpg');
        $zip->close();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $fileData = [
            'name' => 'images.zip',
            'tmp_name' => $tmpname,
        ];
        $affectedMaterialsIds = [];

        $result = $interface->processFile($fileData, $loader, $affectedMaterialsIds);

        $this->assertEquals('83dcefb7_1.jpeg', $result[0]['name']);
        $this->assertEquals('83dcefb7_1.jpg', $result[0]['originalName']);
        $this->assertFileExists($result[0]['tmp_name']);
        $this->assertEquals('image/jpeg', $result[0]['type']);
        $this->assertCount(1, $result[0]['materials']);
        $this->assertEquals(11, $result[0]['materials'][0]->id);

        $this->assertEquals('83dcefb7_2.jpeg', $result[1]['name']);
        $this->assertEquals('83dcefb7_2.jpg', $result[1]['originalName']);
        $this->assertFileExists($result[1]['tmp_name']);
        $this->assertEquals('image/jpeg', $result[1]['type']);
        $this->assertCount(1, $result[1]['materials']);
        $this->assertEquals(11, $result[1]['materials'][0]->id);

        $this->assertEquals('f4dbdf21_1.jpeg', $result[2]['name']);
        $this->assertEquals('f4dbdf21_1.jpg', $result[2]['originalName']);
        $this->assertFileExists($result[2]['tmp_name']);
        $this->assertEquals('image/jpeg', $result[2]['type']);
        $this->assertCount(1, $result[2]['materials']);
        $this->assertEquals(10, $result[2]['materials'][0]->id);

        $this->assertEquals('f4dbdf21_2.jpeg', $result[3]['name']);
        $this->assertEquals('f4dbdf21_2.jpg', $result[3]['originalName']);
        $this->assertFileExists($result[3]['tmp_name']);
        $this->assertEquals('image/jpeg', $result[3]['type']);
        $this->assertCount(1, $result[3]['materials']);
        $this->assertEquals(10, $result[3]['materials'][0]->id);

        $this->assertEquals([10 => 10, 11 => 11], $affectedMaterialsIds);
    }


    /**
     * Тест метода applyFiles()
     */
    public function testApplyFiles()
    {
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
        $preprocessor = new Snippet(['urn' => 'testpreprocessor', 'description' => '<' . '?php' . ' $GLOBALS["preprocessorData"][] = $files; ']);
        $preprocessor->commit();
        $postprocessor = new Snippet(['urn' => 'testpostprocessor', 'description' => '<' . '?php' . ' $GLOBALS["postprocessorData"][] = $files; ']);
        $postprocessor->commit();

        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $field = $loader->Image_Field;
        $field->preprocessor_id = $preprocessor->id;
        $field->postprocessor_id = $postprocessor->id;
        $field->commit();
        $filesData = [
            [
                'name' => '1.jpg',
                'originalName' => '1.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/1.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(10)],
            ],
            [
                'name' => '2.jpg',
                'originalName' => '2.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/2.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(10)],
            ],
            [
                'name' => '3.jpg',
                'originalName' => '3.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/3.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(11)],
            ],
            [
                'name' => '4.jpg',
                'originalName' => '4.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/4.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(11)],
            ],
        ];

        $log = [];

        $interface->applyFiles($filesData, $loader, $log, false);

        $material1 = new Material(10);
        $material2 = new Material(11);
        $images1 = $material1->images;
        $images2 = $material2->images;

        $this->assertCount(2, $images1);
        $this->assertEquals('1.jpg', $images1[0]->filename);
        $this->assertEquals('2.jpg', $images1[1]->filename);
        $this->assertCount(2, $images2);
        $this->assertEquals('3.jpg', $images2[0]->filename);
        $this->assertEquals('4.jpg', $images2[1]->filename);

        $this->assertCount(4, $log);
        $this->assertStringContainsString('edit_material&id=10', $log[0]['text']);
        $this->assertStringContainsString('edit_material&id=10', $log[1]['text']);
        $this->assertStringContainsString('edit_material&id=11', $log[2]['text']);
        $this->assertStringContainsString('edit_material&id=11', $log[3]['text']);

        $this->assertEquals([[
            Module::i()->resourcesDir . '/fish/products/1.jpg',
            Module::i()->resourcesDir . '/fish/products/2.jpg',
            Module::i()->resourcesDir . '/fish/products/3.jpg',
            Module::i()->resourcesDir . '/fish/products/4.jpg',
        ]], $GLOBALS['preprocessorData']);
        $this->assertEquals([[
            $images1[0]->file,
            $images1[1]->file,
            $images2[0]->file,
            $images2[1]->file,
        ]], $GLOBALS['postprocessorData']);

        $this->clearMaterialAssets();

        $field->preprocessor_id = 0;
        $field->postprocessor_id = 0;
        $field->commit();
        Snippet::delete($preprocessor);
        Snippet::delete($postprocessor);
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
    }


    /**
     * Тест метода applyFiles() - случай с указанием классов файловых процессоров
     */
    public function testApplyFilesWithProcessorsClassnames()
    {
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $field = $loader->Image_Field;
        $field->preprocessor_classname = PreprocessorMock::class;
        $field->postprocessor_classname = PostprocessorMock::class;
        $field->commit();
        $filesData = [
            [
                'name' => '1.jpg',
                'originalName' => '1.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/1.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(10)],
            ],
            [
                'name' => '2.jpg',
                'originalName' => '2.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/2.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(10)],
            ],
            [
                'name' => '3.jpg',
                'originalName' => '3.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/3.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(11)],
            ],
            [
                'name' => '4.jpg',
                'originalName' => '4.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/4.jpg',
                'type' => 'image/jpeg',
                'materials' => [new Material(11)],
            ],
        ];

        $log = [];

        $interface->applyFiles($filesData, $loader, $log, false);

        $material1 = new Material(10);
        $material2 = new Material(11);
        $images1 = $material1->images;
        $images2 = $material2->images;

        $this->assertEquals([[
            Module::i()->resourcesDir . '/fish/products/1.jpg',
            Module::i()->resourcesDir . '/fish/products/2.jpg',
            Module::i()->resourcesDir . '/fish/products/3.jpg',
            Module::i()->resourcesDir . '/fish/products/4.jpg',
        ]], $GLOBALS['preprocessorData']);
        $this->assertEquals([[
            $images1[0]->file,
            $images1[1]->file,
            $images2[0]->file,
            $images2[1]->file,
        ]], $GLOBALS['postprocessorData']);

        $this->clearMaterialAssets();

        $field->preprocessor_classname = '';
        $field->postprocessor_classname = '';
        $field->commit();
        unset($GLOBALS['preprocessorData'], $GLOBALS['postprocessorData']);
    }


    /**
     * Тест метода upload()
     */
    public function testUpload()
    {
        $this->addMaterialAssets(); // Чтобы проверить clear, иначе не пройдет
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $filesData = [
            [
                'name' => 'f4dbdf21_1.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/1.jpg',
            ],
            [
                'name' => 'f4dbdf21_2.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/2.jpg',
            ],
            [
                'name' => '83dcefb7_1.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/3.jpg',
            ],
            [
                'name' => '83dcefb7_2.jpg',
                'tmp_name' => Module::i()->resourcesDir . '/fish/products/4.jpg',
            ],
        ];

        $result = $interface->upload($filesData, false, true);

        $material1 = new Material(10);
        $material2 = new Material(11);
        $images1 = $material1->images;
        $images2 = $material2->images;

        $this->assertCount(2, $images1);
        $this->assertEquals('f4dbdf21_1.jpg', $images1[0]->filename);
        $this->assertEquals('f4dbdf21_2.jpg', $images1[1]->filename);
        $this->assertCount(2, $images2);
        $this->assertEquals('83dcefb7_1.jpg', $images2[0]->filename);
        $this->assertEquals('83dcefb7_2.jpg', $images2[1]->filename);

        $this->assertCount(5, $result['log']); // 1-й - очистка
        $this->assertStringContainsString('edit_material&id=10', $result['log'][1]['text']);
        $this->assertStringContainsString('edit_material&id=10', $result['log'][2]['text']);
        $this->assertStringContainsString('edit_material&id=11', $result['log'][3]['text']);
        $this->assertStringContainsString('edit_material&id=11', $result['log'][4]['text']);
        $this->assertTrue($result['ok']);

        $this->clearMaterialAssets();
    }


    /**
     * Тест метода upload() - случай без файлов
     */
    public function testUploadWithNoFiles()
    {
        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $result = $interface->upload([], false, true);

        $this->assertEquals('MISSING', $result['localError'][0]['name']);
        $this->assertEquals('files', $result['localError'][0]['value']);
    }


    /**
     * Тест метода upload() - случай без файлов
     */
    public function testUploadWithInvalidFiles()
    {
        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;
        $filesData = [
            [
                'name' => 'f4dbdf21_1.jpg',
                'tmp_name' => $this->getResourcesDir() . '/test.xls',
            ],
        ];

        $result = $interface->upload($filesData, false, true);

        $this->assertEquals('INVALID', $result['localError'][0]['name']);
        $this->assertEquals('files', $result['localError'][0]['value']);
    }


    /**
     * Тест метода exportData()
     */
    public function testExportData()
    {
        $this->addMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $material1 = new Material(10);
        $material2 = new Material(11);
        $images1 = $material1->images;
        $images2 = $material2->images;

        $result = $interface->exportData($loader);

        $this->assertCount(4, $result);
        $this->assertEquals('f4dbdf21_1.jpg', $result[$images1[0]->file]);
        $this->assertEquals('f4dbdf21_2.jpg', $result[$images1[1]->file]);
        $this->assertEquals('83dcefb7_1.jpg', $result[$images2[0]->file]);
        $this->assertEquals('83dcefb7_2.jpg', $result[$images2[1]->file]);
    }


    /**
     * Тест метода export()
     */
    public function testExport()
    {
        $filesData = [
            Module::i()->resourcesDir . '/fish/products/1.jpg' => '11.jpg',
            Module::i()->resourcesDir . '/fish/products/2.jpg' => '22.jpg',
            Module::i()->resourcesDir . '/fish/products/3.jpg' => '33.jpg',
            Module::i()->resourcesDir . '/fish/products/4.jpg' => '44.jpg',
        ];
        $interface = $this->getInterface();

        $result = $interface->export($filesData, $interface->loader);

        $zip = new ZipArchive();
        $zip->open($result, ZipArchive::RDONLY);
        $this->assertEquals(file_get_contents(Module::i()->resourcesDir . '/fish/products/1.jpg'), $zip->getFromName('11.jpg'));
        $this->assertEquals(file_get_contents(Module::i()->resourcesDir . '/fish/products/2.jpg'), $zip->getFromName('22.jpg'));
        $this->assertEquals(file_get_contents(Module::i()->resourcesDir . '/fish/products/3.jpg'), $zip->getFromName('33.jpg'));
        $this->assertEquals(file_get_contents(Module::i()->resourcesDir . '/fish/products/4.jpg'), $zip->getFromName('44.jpg'));
        $zip->close();
    }


    /**
     * Тест метода download()
     */
    public function testDownload()
    {
        $this->addMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $material1 = new Material(10);
        $material2 = new Material(11);
        $images1 = $material1->images;
        $images2 = $material2->images;

        $result = $interface->download();

        $zip = new ZipArchive();
        $zip->open($result, ZipArchive::RDONLY);
        $this->assertEquals(file_get_contents($images1[0]->file), $zip->getFromName('f4dbdf21_1.jpg'));
        $this->assertEquals(file_get_contents($images1[1]->file), $zip->getFromName('f4dbdf21_2.jpg'));
        $this->assertEquals(file_get_contents($images2[0]->file), $zip->getFromName('83dcefb7_1.jpg'));
        $this->assertEquals(file_get_contents($images2[1]->file), $zip->getFromName('83dcefb7_2.jpg'));
        $zip->close();

        $this->clearMaterialAssets();
    }


    /**
     * Тест метода download() - случай с отсутствием файлов
     */
    public function testDownloadWithNoFiles()
    {
        $this->clearMaterialAssets();
        $interface = $this->getInterface();
        $loader = $interface->loader;

        $result = $interface->download();

        $this->assertEquals('INVALID', $result['localError'][0]['name']);
        $this->assertEquals('loader', $result['localError'][0]['value']);
    }


    /**
     * Тест метода download() - случай когда у загрузчика отсутствует поле изображений
     */
    public function testDownloadWithInvalidLoader()
    {
        $this->clearMaterialAssets();
        $loader = new ImageLoader();
        $interface = new ImageloaderInterface($loader);
        $interface->debug = true;

        $result = $interface->download();

        $this->assertEquals('INVALID', $result['localError'][0]['name']);
        $this->assertEquals('loader', $result['localError'][0]['value']);
    }
}
