<?php
/**
 * Сниппет интерфейса загрузчика прайсов
 * @param PriceLoader $Loader Загрузчик прайсов
 * @param Page $Page Страница, в которую загружаем
 * @param int $rows Сколько строк отступать
 * @param int $cols Сколько колонок отступать
 * @param array|null $file <pre><code>[
 *     'tmp_name' => string путь к файлу,
 *     'name' => string Имя файла
 * ]|null</code></pre> загружаемый файл
 * @param bool $test Тестовый режим
 * @param int $clear Очищать старые материалы и/или страницы (константа из PriceLoader::DELETE_PREVIOUS_MATERIALS_...)
 * @param string $type <pre><code>'csv'|'xls'|'xlsx'</code></pre> Формат, в котором выгружаем
 * @param string $encoding Кодировка для формата CSV, в которой выгружаем (совместимо с iconv)
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Page;

$interface = new PriceloaderInterface($Loader);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return $interface->upload(
        $file['tmp_name'],
        $type,
        $Page,
        $test,
        $clear,
        $rows,
        $cols
    );
} else {
    return $interface->download($Page, $rows, $cols, $type, $encoding);
}
