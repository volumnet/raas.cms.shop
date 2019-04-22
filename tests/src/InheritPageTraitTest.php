<?php
/**
 * Файл теста трейта наследования полей страницы
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

/**
 * Класс теста трейта наследования полей страницы
 */
class InheritPageTraitTest extends BaseDBTest
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
     * Проверка наследования нативных полей страницы
     */
    public function testInheritPageNativeFields()
    {
        $trait = $this->getMockForTrait(InheritPageTrait::class);
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
        $trait = $this->getMockForTrait(InheritPageTrait::class);
        $page = new Page(['pid' => 3, 'name' => 'aaa']);

        $trait->inheritPageCustomFields($page);

        $this->assertEquals('Test description', $page->_description_);
        $this->assertEmpty($page->noindex);

        Page::delete($page);
    }
}
