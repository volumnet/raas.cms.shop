<?php
/**
 * Файл класса команды обновления файла Яндекс-Маркета
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Command;
use SOME\EventProcessor;
use RAAS\CMS\Page;
use RAAS\CMS\Material;

/**
 * Команда обновления файла Яндекс-Маркета
 */
class UpdateYMLCommand extends Command
{
    /**
     * Выполнение команды
     * @param string|null $ymlPageURL URL страницы Яндекс.Маркета
     * @param string $outputFile Файл для вывода (по умолчанию yandex.market.xml в корне сайта)
     * @param bool $https Включен ли HTTPS
     * @param bool $forceUpdate Принудительно выполнить обновление, даже если материалы не были обновлены
     * @param bool $forceLockUpdate Принудительно выполнить обновление, даже если есть параллельный процесс
     *      {@deprecated больше не используется}
     * @param int $limit Лимит обновляемых товаров (для отладки)
     */
    public function process(
        $ymlPageURL = '/yml/',
        $outputFile = null,
        $https = true,
        $forceUpdate = true,
        $forceLockUpdate = false,
        $limit = 0
    ) {
        $t = $this;

        if ($outputFile === null) {
            $outputFile = Application::i()->baseDir . '/yandex.market.xml';
        }

        if (!$forceUpdate) {
            $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(last_modified))
                           FROM " . Material::_tablename()
                      . " WHERE 1";
            $lastModifiedMaterialTimestamp = Material::_SQL()->getvalue($sqlQuery);
            $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(last_modified))
                           FROM " . Page::_tablename()
                      . " WHERE 1";
            $lastModifiedPageTimestamp = Material::_SQL()->getvalue($sqlQuery);
            if (is_file($outputFile)) {
                if (filemtime($outputFile) >= max(
                    $lastModifiedMaterialTimestamp,
                    $lastModifiedPageTimestamp
                )) {
                    $this->controller->doLog('Data is actual');
                    return;
                }
            }
        }
        $typesize = 0;
        $i = 0;
        EventProcessor::on(
            YMLInterface::class . ':' . 'getOfferBlock',
            null,
            function ($material) use ($t, &$i, &$typesize) {
                $i++;
                $t->controller->doLog(
                    'Item #' . $material->id . ' (' . $material->name . ')' .
                    ' processed (' . $i . '/' . $typesize . ')'
                );
            }
        );
        EventProcessor::on(
            YMLInterface::class . ':' . 'outputOffersBlock:starttype',
            null,
            function ($mtype, $data) use ($t, &$typesize, &$i) {
                $typesize = $data['size'];
                $i = 0;
                $t->controller->doLog(
                    'Material type #' . $mtype->id .
                    ' (' . $mtype->name . ') started - ' .
                    $data['size'] . ' items total'
                );
            }
        );
        $blocks = Block_YML::getSet([
            'where' => 'block_type = "' . Block_YML::_SQL()->real_escape_string(Block_YML::class) . '"',
            'orderBy' => 'id'
        ]);
        foreach ($blocks as $block) {
            $page = $block->parent;
            if ($page->url == $ymlPageURL) {
                $block = Block_YML::spawn($block->id);
                break;
            }
        }
        if ($https) {
            $_SERVER['HTTPS'] = 'on';
        }
        $_SERVER['HTTP_HOST'] = parse_url($page->domain, PHP_URL_HOST);
        if ($page->id && $block) {
            $interface = new YMLInterface(
                $block,
                $page,
                [],
                [],
                [],
                [],
                $_SERVER,
                $limit
            );
            $result = $interface->process(false, null, true);
            file_put_contents($outputFile, $result['result']);
        } else {
            $this->controller->doLog('Yandex Market file not found');
        }
    }
}
