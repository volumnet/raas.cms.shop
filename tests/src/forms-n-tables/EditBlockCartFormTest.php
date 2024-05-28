<?php
/**
 * Тест класса EditBlockCartForm
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
 * Тест класса EditBlockCartForm
 * @covers RAAS\CMS\Shop\EditBlockCartForm
 */
class EditBlockCartFormTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_access_blocks_cache',
        'cms_blocks',
        'cms_blocks_pages_assoc',
        'cms_groups',
        'cms_pages',
        'cms_shop_blocks_cart',
        'cms_shop_cart_types',
        'cms_snippet_folders',
        'cms_snippets',
        'cms_templates',
        'cms_users',
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
        $form = new EditBlockCartForm(['meta' => ['Parent' => new Page(1)]]);
        $interfaceField = $form->children['serviceTab']->children['interface_id'];
        $widgetField = $form->children['commonTab']->children['widget_id'];
        $cartTypeField = $form->children['commonTab']->children['cart_type'];
        $snippet = Snippet::importByURN('__raas_shop_cart_interface');

        $this->assertInstanceOf(RAASField::class, $interfaceField);
        $this->assertEquals($snippet->id, $interfaceField->default);
        $this->assertInstanceOf(RAASField::class, $widgetField);
        $this->assertInstanceOf(RAASField::class, $cartTypeField);
        $this->assertEquals('select', $cartTypeField->type);

        $this->assertInstanceOf(FormTab::class, $form->children['epayTab']);
        $this->assertInstanceOf(RAASField::class, $form->children['epayTab']->children['epay_interface_id']);
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
            'name' => 'Тестовый блок',
            'cats' => [1],
            'location' => 'content',
            'epay_pass1' => 'pass1',
            'epay_pass2' => 'pass2',
        ];
        $block = new Block_Cart();
        $form = new EditBlockCartForm(['Item' => $block, 'meta' => ['Parent' => new Page(1)]]);
        $form->process();

        $this->assertEquals('pass1', $block->epay_pass1);
        $this->assertEquals('pass2', $block->epay_pass2);

        $_POST = $oldPost;
        $_SERVER = $oldServer;
        Block_Cart::delete($block);
    }
}
