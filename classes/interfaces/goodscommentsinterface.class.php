<?php
/**
 * Файл стандартного интерфейса комментариев к товарам
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Block_Material;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialInterface;
use RAAS\CMS\Page;

/**
 * Класс стандартного интерфейса комментариев к товарам
 */
class GoodsCommentsInterface extends MaterialInterface
{
    public function process(): array
    {
        $result = parent::process();
        if ($this->get['AJAX'] == $this->block->id) {
            $result['votes'] = [];
            $localError = [];
            if ($this->post['id'] && $this->post['vote']) {
                $matchingComments = array_values(array_filter(
                    $result['Set'],
                    function ($x) {
                        return $x->id == $this->post['id'];
                    }
                ));
                if (!$matchingComments) {
                    $localError['id'] = View_Web::i()->_('COMMENT_NOT_FOUND');
                } else {
                    $result['votes'] = $this->doVote(
                        $matchingComments[0],
                        (($this->post['vote'] > 0) ? 1 : -1)
                    );
                }
            } else {
                $result['votes'] = $this->getVotes($result['Set']);
            }
            if ($localError) {
                $result['localError'] = $localError;
            }
        }
        return $result;
    }


    public function getMaterialsSQL(
        Block_Material $block,
        Page $page,
        array &$sqlFrom,
        array &$sqlWhere,
        array &$sqlWhereBind
    ) {
        parent::getMaterialsSQL(
            $block,
            $page,
            $sqlFrom,
            $sqlWhere,
            $sqlWhereBind
        );
        $activeMaterialId = (int)($page->Material->id ?: $page->Item->id ?: 0);
        $materialFieldURN = $block->additionalParams['materialFieldURN'];
        if ($activeMaterialId && $materialFieldURN) {
            $materialType = $block->Material_Type;
            $materialField = $materialType->fields[$materialFieldURN];
            if ($materialField->id) {
                $sqlFrom['tMaterial'] = "LEFT JOIN cms_data
                                                AS tMaterial
                                                ON tMaterial.pid = tM.id
                                               AND tMaterial.fid = " . (int)$materialField->id;
                $sqlWhere[] = "tMaterial.value = ?";
                $sqlWhereBind[] = $activeMaterialId;
            }
        }
    }


    /**
     * Голосует за комментарий
     * @param Material $comment Комментарий для голосования
     * @param int $vote 1 - за, -1 - против
     * @return array <pre><code>[
     *     string ID# комментария, за который голосовали => [
     *         'id' => int ID# комментария, за который голосовали,
     *         'pros' => int Количество комментариев за,
     *         'cons' => int Количество комментариев против,
     *         'vote' => int Как проголосовал текущий пользователь
     *             (1 - за, -1 - против),
     *     ]
     * ]</code></pre>
     */
    public function doVote(Material $comment, $vote)
    {
        $result = [];
        if (!$vote) {
            return [];
        }
        $ip = '0.0.0.0';
        if (isset($this->server['HTTP_X_FORWARDED_FOR']) && $this->server['HTTP_X_FORWARDED_FOR']) {
            $forwardedFor = explode(',', (string)$this->server['HTTP_X_FORWARDED_FOR']);
            $forwardedFor = array_map('trim', $forwardedFor);
            $ip = $forwardedFor[0];
        } elseif (isset($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        }
        $sqlQuery = "SELECT IFNULL(vote, 0)
                       FROM cms_materials_votes
                      WHERE material_id = ?
                        AND ip = ?";
        $sqlBind = [(int)$comment->id, $ip];
        $oldVote = (int)Material::_SQL()->getValue([$sqlQuery, $sqlBind]);
        // var_dump($oldVote, $_POST['vote']); exit;

        if ($oldVote == $vote) { // Снимаем голосование
            $sqlQuery = "DELETE FROM cms_materials_votes
                          WHERE material_id = ?
                            AND ip = ?";
            Material::_SQL()->query([$sqlQuery, $sqlBind]);
        } else { // Ставим новое значение
            $sqlArr = [
                'material_id' => (int)$comment->id,
                'ip' => $_SERVER['REMOTE_ADDR'],
                'post_date' => date('Y-m-d H:i:s'),
                'vote' => $vote,
            ];
            Material::_SQL()->add('cms_materials_votes', $sqlArr);
        }

        $result = $this->getVotes([$comment]);
        return $result;
    }


    /**
     * Получает голоса
     * @param Material[] $set Набор материалов, для которых получаем голоса
     * @return array <pre><code>array<
     *     string[] ID# комментария, за который голосовали => [
     *         'id' => int ID# комментария, за который голосовали,
     *         'pros' => int Количество комментариев за,
     *         'cons' => int Количество комментариев против,
     *         'vote' => int Как проголосовал текущий пользователь
     *             (1 - за, -1 - против),
     *     ]
     * ></code></pre>
     */
    public function getVotes(array $set = [])
    {
        $ids = array_map(function ($x) {
            return (int)$x->id;
        }, $set);
        if (!$ids) {
            return [];
        }
        $materialType = $this->block->Material_Type;

        $sqlQuery = "SELECT tM.id,
                            SUM(IF(vote > 0, 1, 0)) AS pros,
                            SUM(IF(vote < 0, 1, 0)) AS cons,
                            IFNULL(MAX(IF(ip = :ip, vote, NULL)), 0) AS voted
                       FROM " . Material::_tablename() . " AS tM
                  LEFT JOIN cms_materials_votes AS tMV ON tMV.material_id = tM.id
                      WHERE tM.vis
                        AND tM.id IN (" . implode(", ", $ids) . ")
                   GROUP BY id";
        $sqlBind = [
            'ip' => $ip,
        ];
        $sqlResult = Material::_SQL()->get([$sqlQuery, $sqlBind]);
        // echo $sqlQuery; var_dump($sqlBind); exit;
        $result = [];
        foreach ($sqlResult as $sqlRow) {
            $result[trim($sqlRow['id'])] = $sqlRow;
        }
        return $result;
    }
}
