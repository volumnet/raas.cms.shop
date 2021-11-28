<?php
/**
 * Стандартный интерфейс каталога
 * @param Block_Material $Block Текущий блок <pre><code>Block_Material(
 *     'additionalParams' => [
 *         'metaTemplates' => string URN поля шаблона метаданных
 *             активного материала в виде
 *             ('name'|'meta_title'|'meta_keywords'|'meta_description'|'h1') . '_' . <значение данного поля>
 *         'listMetaTemplates' => string URN поля шаблона метаданных
 *             списка материалов в виде
 *             ('name'|'meta_title'|'meta_keywords'|'meta_description'|'h1') . '_' . <значение данного поля>
 *         'commentFormBlock' => int ID# блока формы отзывов к товару,
 *         'commentsListBlock' => int ID# блока списка отзывов к товару,
 *         'faqFormBlock' => int ID# блока формы вопрос-ответ к товару,
 *         'faqListBlock' => int ID# блока списка вопросов-ответов к товару,
 *         'materialFieldURN' => string URN поля "Материал" (ссылки на товар)
 *             у материалов отзывов или вопросов-ответов,
 *         'withChildrenGoods' => bool Использовать товары дочерних категорий
 *             в родительской
 *         'useAvailabilityOrder' => bool Использовать сортировку по наличию
 *     ],
 * )</code></pre>
 * @param Page $Page Текущая страница
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block_Material;
use RAAS\CMS\Page;

$interface = new CatalogInterface(
    $Block,
    $Page,
    $_GET,
    $_POST,
    $_COOKIE,
    $_SESSION,
    $_SERVER,
    $_FILES
);
return $interface->process();
