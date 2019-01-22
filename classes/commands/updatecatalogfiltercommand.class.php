<?php
/**
 * Файл класса команды обновления фильтра каталога
 */
namespace RAAS\CMS\Shop;

use RAAS\LockCommand;
use RAAS\CMS\Page;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;

/**
 * Класс команды обновления фильтра каталога
 */
class UpdateCatalogFilterCommand extends LockCommand
{
    /**
     * Выполнение команды
     * @param string $materialTypeURN URN типа материала
     * @param bool $withChildrenGoods с дочерними товарами
     * @param bool $forceUpdate Принудительно выполнить обновление, даже если материалы не были обновлены
     * @param bool $forceLockUpdate Принудительно выполнить обновление, даже если есть параллельный процесс
     */
    public function process(
        $materialTypeURN = 'catalog',
        $withChildrenGoods = true,
        $forceUpdate = false,
        $forceLockUpdate = false
    ) {
        $t = $this;
        if (!$forceLockUpdate && $this->checkLock()) {
            return;
        }
        if (!$forceUpdate) {
            $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                           FROM " . Material::_tablename()
                      . " WHERE 1";
            $lastModifiedMaterialTimestamp = Material::_SQL()->getvalue($sqlQuery);
            $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
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
        $this->lock();
        $materialType = Material_Type::importByURN($materialTypeURN);
        if ($materialType->id) {
            $catalogFilter = new CatalogFilter($materialType, $withChildrenGoods, []);
            $catalogFilter->build();
            $catalogFilter->save();
        }
        $this->unlock();
    }
}
