<?php
/**
 * Файл теста трейта наследования полей страницы
 */
namespace RAAS\CMS\Shop;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use SOME\BaseTest;
use RAAS\CMS\Page;

/**
 * Класс теста трейта наследования полей страницы
 */
class InheritPageTraitTest extends BaseTest
{
    public static $tables = [
        'cms_access',
        'cms_access_pages_cache',
        'cms_blocks',
        'cms_blocks_material',
        'cms_blocks_pages_assoc',
        'cms_blocks_search_pages_assoc',
        'cms_data',
        'cms_fields',
        'cms_material_types',
        'cms_material_types_affected_pages_for_materials_cache',
        'cms_material_types_affected_pages_for_self_cache',
        'cms_materials',
        'cms_materials_affected_pages_cache',
        'cms_materials_pages_assoc',
        'cms_pages',
        'cms_shop_blocks_yml_pages_assoc',
        'cms_shop_orders',
        'cms_shop_priceloaders',
        'cms_users',
    ];

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
     * Проверка наследования нативных полей страницы
     */
    public function testInheritPageNativeFields()
    {
        $trait = new class {
            use InheritPageTrait;
        };
        $page = new Page(['pid' => 3]);

        $trait->inheritPageNativeFields($page);

        $this->assertEquals(1, $page->template);
        $this->assertEquals(1, $page->inherit_template);
        $this->assertEquals(0.75, $page->sitemaps_priority);
        $this->assertEquals(1, $page->inherit_sitemaps_priority);
    }


    /**
     * Проверка наследования кастомных полей страницы
     */
    public function testInheritPageCustomFields()
    {
        $trait = new class {
            use InheritPageTrait;
        };
        $parentPage = new Page(['pid' => 1, 'name' => 'Test parent']);
        $parentPage->commit();
        $parentPage->fields['_description_']->addValue('Test description');
        $parentPage->fields['_description_']->inheritValues();
        $childPage = new Page(['pid' => $parentPage->id, 'name' => 'Test child']);
        $childPage->commit();

        $trait->inheritPageCustomFields($childPage);

        $sqlQuery = "SELECT * FROM cms_data WHERE pid IN (" . $childPage->id . ", " . $parentPage->id . ")";
        $sqlResult = Page::_SQL()->get($sqlQuery);

        $childPage = new Page($childPage->id);

        $this->assertEquals('Test description', $childPage->_description_);
        $this->assertEmpty($childPage->noindex);

        Page::delete($parentPage);
        Page::delete($childPage);
    }
}
