<?php
/**
 * Файл трейта наследования полей страницы
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\CMS\Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;

/**
 * Трейт наследования полей страницы
 */
trait InheritPageTrait
{
    /**
     * Наследует не установленные нативные страницы поля от родительской страницы
     * @param Page $page Страница, для которой наследуем
     */
    public function inheritPageNativeFields(Page $page)
    {
        $parent = $page->parent;
        if ($parent->id) {
            foreach ([
                'vis',
                'response_code',
                'template',
                'inherit_template',
                'lang',
                'inherit_lang',
                'cache',
                'inherit_cache',
                'changefreq',
                'inherit_changefreq',
            ] as $key) {
                if (!isset($page->$key)) {
                    $page->$key = $parent->$key;
                }
            }
            if (!isset($page->pvis)) {
                $page->pvis = (int)((int)$parent->vis && (int)$parent->pvis);
            }
            foreach (['sitemaps_priority', 'title', 'keywords', 'description'] as $key) {
                if ($parent->{'inherit_' . $key} && !isset($page->$key)) {
                    $page->$key = $parent->$key;
                }
                if (!isset($page->{'inherit_' . $key})) {
                    $page->{'inherit_' . $key} = $parent->{'inherit_' . $key};
                }
            }
        }
    }


    /**
     * Наследует кастомные поля страницы от родительской страницы
     * @param Page $page Страница, для которой наследуем
     */
    public function inheritPageCustomFields(Page $page)
    {
        if ($page->pid) {
            $sqlQuery = "INSERT INTO cms_data (pid, fid, fii, value, inherited)
                         SELECT ? AS pid, fid, fii, value, inherited
                           FROM cms_data AS tD
                           JOIN " . Field::_tablename() . " AS tF ON tF.id = tD.fid
                          WHERE tD.pid = ?
                            AND tF.classname = ?
                            AND tF.pid = 0
                            AND tD.inherited";
            Page::_SQL()->query([$sqlQuery, [$page->id, $page->pid, Material_Type::class]]);
        }
    }
}
