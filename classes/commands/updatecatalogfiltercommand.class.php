<?php
/**
 * Файл класса команды обновления фильтра каталога
 */
namespace RAAS\CMS\Shop;

use RAAS\Command;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;

/**
 * Команда обновления фильтра каталога
 */
class UpdateCatalogFilterCommand extends Command
{
    /**
     * Выполнение команды
     * @param string $materialTypeURN URN типа материала
     * @param bool $withChildrenGoods с дочерними товарами
     * @param bool $forceUpdate Принудительно выполнить обновление, даже если материалы не были обновлены
     * @param bool $forceLockUpdate Принудительно выполнить обновление, даже если есть параллельный процесс
     *      {@deprecated больше не используется}
     * @param string $availabilityURN URN поля наличия
     */
    public function process(
        $materialTypeURN = 'catalog',
        $withChildrenGoods = true,
        $forceUpdate = false,
        $forceLockUpdate = false,
        $availabilityURN = 'available'
    ) {
        $t = $this;
        $materialType = Material_Type::importByURN($materialTypeURN);
        if ($materialType->id) {
            $catalogFilter = new CatalogFilter($materialType, $withChildrenGoods, []);
            $outputFile = $catalogFilter->getDefaultFilename($materialType->id, $withChildrenGoods);
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
                    if (filemtime($outputFile) >= max($lastModifiedMaterialTimestamp, $lastModifiedPageTimestamp)) {
                        $this->controller->doLog('Data is actual');
                        return;
                    }
                }
            }
            $catalogFilter->useAvailabilityOrder = $availabilityURN;
            $catalogFilter->build();
            $catalogFilter->save();
            Package::i()->clearCache(true);
            $this->controller->doLog('Completed');
        }
    }
}
