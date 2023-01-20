<?php
/**
 * Команда обновления рейтинга товаров
 */
namespace RAAS\CMS\Shop;

use RAAS\Command;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;

/**
 * Команда обновления рейтинга товаров
 */
class UpdateRatingCommand extends Command
{
    /**
     * Выполняет команду
     * @param string $catalogMaterialTypeURN URN типа материалов
     *     каталога продукции
     * @param string $commentsMaterialTypeURN URN типа материалов
     *     отзывов к товарам
     * @param string $commentsMaterialFieldURN URN поля "Материал"
     *     отзывов к товарам
     * @param string $commentsRatingFieldURN URN поля "Рейтинг"
     *     отзывов к товарам
     * @param string $catalogRatingFieldURN URN поля "Рейтинг"
     *     каталога продукции
     * @param string $catalogReviewsCounterFieldURN URN поля
     *     "Количество отзывов" каталога продукции
     * @param bool $forceUpdate Обновить даже если это не требуется
     */
    public function process(
        $catalogMaterialTypeURN = 'catalog',
        $commentsMaterialTypeURN = 'goods_comments',
        $commentsMaterialFieldURN = 'material',
        $commentsRatingFieldURN = 'rating',
        $catalogRatingFieldURN = 'rating',
        $catalogReviewsCounterFieldURN = 'reviews_counter',
        $forceUpdate = false
    ) {
        if (!$forceUpdate && !$this->updateNeeded()) {
            $this->controller->doLog('Data is actual');
            return;
        }
        $catalogMaterialType = Material_Type::importByURN($catalogMaterialTypeURN);
        if (!$catalogMaterialType->id) {
            $logMessage = 'Material type "' . $catalogMaterialTypeURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $catalogRatingField = $catalogMaterialType->fields[$catalogRatingFieldURN];
        if (!$catalogRatingField->id) {
            $logMessage = 'Catalog field "' . $catalogRatingFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $catalogReviewsCounterField = $catalogMaterialType->fields[$catalogReviewsCounterFieldURN];
        if (!$catalogReviewsCounterField->id) {
            $logMessage = 'Catalog field "' . $catalogReviewsCounterFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }

        $commentsMaterialType = Material_Type::importByURN($commentsMaterialTypeURN);
        if (!$commentsMaterialType->id) {
            $logMessage = 'Material type "' . $commentsMaterialTypeURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $commentsMaterialField = $commentsMaterialType->fields[$commentsMaterialFieldURN];
        if (!$commentsMaterialField->id) {
            $logMessage = 'Goods comments field "' . $commentsMaterialFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $commentsRatingField = $commentsMaterialType->fields[$commentsRatingFieldURN];
        if (!$commentsRatingField->id) {
            $logMessage = 'Goods comments field "' . $commentsRatingFieldURN . '"' .
                ' is not found';
            $this->controller->doLog($logMessage);
            return;
        }
        $catalogInterface = new CatalogInterface();
        $result = [];

        $sqlQuery = "SELECT tItem.value AS item_id,
                            SUM(tRating.value) AS rating,
                            SUM(IFNULL(tRating.value, 0) > 0) AS votes,
                            COUNT(DISTINCT tComment.id) AS comments
                       FROM cms_data AS tItem
                  LEFT JOIN cms_data AS tRating ON tRating.pid = tItem.pid
                       JOIN cms_materials AS tComment ON tComment.id = tItem.pid
                      WHERE tComment.vis
                        AND tItem.fid = ?
                        AND tRating.fid = ?
                   GROUP BY tItem.value";
        $sqlResult = Material::_SQL()->get([
            $sqlQuery,
            $commentsMaterialType->fields[$commentsMaterialFieldURN]->id,
            $commentsMaterialType->fields[$commentsRatingFieldURN]->id,
        ]);
        foreach ($sqlResult as $sqlRow) {
            $itemId = $sqlRow['item_id'];
            $rating = (int)$sqlRow['rating'];
            $votes = (int)$sqlRow['votes'];
            $comments = (int)$sqlRow['comments'];
            $result[trim($itemId)] = [
                'rating' => (int)$rating,
                'votes' => (int)$votes,
                'comments' => (int)$comments,
            ];
        }
        $result = array_map(function ($x) {
            return [
                'rating' => round((float)$x['rating'] / (int)$x['votes'] * 100) / 100,
                'comments' => (int)$x['comments']
            ];
        }, $result);

        $sqlArr = [];
        $affectedMaterialsIds = array_map('intval', array_keys($result));
        foreach ($result as $itemId => $itemData) {
            $rating = (float)$itemData['rating'];
            $comments = (float)$itemData['comments'];
            $sqlArr[] = [
                'pid' => (int)$itemId,
                'fid' => (int)$catalogRatingField->id,
                'fii' => 0,
                'value' => $rating,
            ];
            $sqlArr[] = [
                'pid' => (int)$itemId,
                'fid' => (int)$catalogReviewsCounterField->id,
                'fii' => 0,
                'value' => $comments,
            ];
        }
        $sqlQuery = "DELETE FROM cms_data WHERE fid IN (?, ?)";
        Material::_SQL()->query([
            $sqlQuery,
            $catalogRatingField->id,
            $catalogReviewsCounterField->id
        ]);
        Material::_SQL()->add('cms_data', $sqlArr);

        // 2022-12-08, AVS: добавим недостающие рейтинги (у кого нет комментариев),
        // чтобы не было выпадений при сортировке через CatalogFilter
        $sqlQuery = "INSERT IGNORE INTO cms_data(pid, fid, fii, value)
                      SELECT tM.id AS pid,
                             tF.id AS fid,
                             0 AS fii,
                             0 AS value
                        FROM " . Material::_tablename() . " AS tM
                        JOIN " . Field::_tablename() . " AS tF
                        WHERE tM.pid IN (" . implode(", ", MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($catalogMaterialType->id)) . ")
                          AND tF.id IN (?, ?) ";
        if ($affectedMaterialsIds) {
            $sqlQuery .= " AND tM.id NOT IN (" . implode(", ", $affectedMaterialsIds) . ")";
        }
        Material::_SQL()->query([
            $sqlQuery,
            $catalogRatingField->id,
            $catalogReviewsCounterField->id
        ]);

        Module::i()->registrySet(
            'rating_updated',
            date('Y-m-d H:i:s')
        );
    }


    /**
     * Определяет, требуется ли обновление
     * @return bool
     */
    public function updateNeeded()
    {
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Material::_tablename()
                  . " WHERE 1";
        $lastModifiedMaterialTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $sqlQuery = "SELECT MAX(UNIX_TIMESTAMP(modify_date))
                       FROM " . Page::_tablename()
                  . " WHERE 1";
        $lastModifiedPageTimestamp = Material::_SQL()->getvalue($sqlQuery);
        $lastModified = max(
            $lastModifiedMaterialTimestamp,
            $lastModifiedPageTimestamp
        );

        $cacheUpdated = strtotime(Module::i()->registryGet('rating_updated'));
        return $lastModified > $cacheUpdated;
    }
}
