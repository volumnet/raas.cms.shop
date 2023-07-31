<?php
/**
 * Файл стандартного интерфейса сравнения
 */
namespace RAAS\CMS\Shop;

use SOME\HTTP;
use RAAS\Controller_Frontend as RAASControllerFrontend;
use RAAS\Redirector;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;

/**
 * Класс стандартного интерфейса сравнения
 */
class CompareInterface extends CartInterface
{
    /**
     * Отрабатывает интерфейс
     * @param bool $debug Режим отладки
     * @return mixed
     */
    public function process($debug = false)
    {
        $result = [];
        $cartType = new Cart_Type((int)$this->block->cart_type);
        $user = RAASControllerFrontend::i()->user;
        $cart = new Cart($cartType, $user);
        $action = isset($this->get['action']) ? $this->get['action'] : '';

        switch ($action) {
            case 'set':
            case 'add':
            case 'reduce':
            case 'delete':
            case 'clear':
            case 'refresh':
            case 'delete':
                $result = parent::process($debug);
                $cart = new Cart($cartType, $user);
                break;
            case 'deleteGroup':
                $id = isset($this->get['id']) ? $this->get['id'] : '';
                foreach ($cart->items as $cartItem) {
                    $group = $this->getGroup($cartItem->material);
                    if ($group['id'] == $id) {
                        $cart->set($cartItem->material, 0, $cartItem->meta);
                    }
                }
                break;
            default:
                break;
        }
        if ((isset($this->get['back']) && $this->get['back']) ||
            (
                in_array($action, ['set', 'add', 'reduce', 'delete', 'clear']) &&
                !$this->get['AJAX'] // 2020-12-25, AVS: добавлено, чтобы не редиректило в AJAX'е
            )
        ) {
            if ($this->get['back']) {
                $url = 'history:back';
            } else {
                $url = HTTP::queryString('action=&id=&meta=&amount=') ?: '?';
            }
            if ($debug) {
                return $url;
            } else {
                new Redirector($url);
            }
        }

        $result['Cart'] = $cart;
        $result['Cart_Type'] = $cartType;
        $result['convertMeta'] = [$this, 'convertMeta'];
        $result['interface'] = $this;
        $result = array_merge($result, $this->getCompareData($cart));
        return $result;
    }


    /**
     * Получает группу товаров для сравнения
     * @param Material $material Товар
     * @return array <pre>[
     *     'id' => string Идентификатор группы
     *     'name' => string Наименование группы
     * ]</pre>
     */
    public function getGroup(Material $material)
    {
        $mTypeId = $material->pid;
        $mTypeSelfAndParentsIds = MaterialTypeRecursiveCache::i()->getSelfAndParentsIds($mTypeId);
        $rootMTypeId = $mTypeSelfAndParentsIds[1] ?: $mTypeSelfAndParentsIds[0];
        $rootMTypeCache = MaterialTypeRecursiveCache::i()->cache[$rootMTypeId];
        $result = [
            'id' => (int)$rootMTypeCache['id'],
            'name' => trim($rootMTypeCache['name'])
        ];
        return $result;
    }


    /**
     * Получает данные по сравнению
     * @param Cart $cart Корзина
     * @return array <pre>[
     *     'Set' => array<string[] ID# товара => Material>
     *         Набор материалов для сравнения,
     *     'groups' => array<string[] ID# группы => [
     *         'id' => string ID# группы,
     *         'name' => string Наименование группы,
     *         'itemsIds' => int[] ID# товаров в группе
     *     ]> Набор групп,
     *     'rawData' => array<string[] ID# материала => array<
     *         string[] ID# поля => array<
     *             string[] Индекс поля => string Сырое значение поля материала
     *         >
     *     >> Сырые данные по материалам,
     *     'fields' => array<string[] ID# поля => Material_Field Поле>
     *         Задействованные поля
     * ]</pre>
     */
    public function getCompareData(Cart $cart)
    {
        $set = $groups = $rawData = $affectedFieldsIds = $fields = [];
        foreach ($cart->items as $cartItem) {
            $material = $cartItem->material;
            $set[trim($cartItem->id)] = $material;
            $group = $this->getGroup($material);
            if (!isset($groups[$group['id']])) {
                $groups[trim($group['id'])] = $group;
                $groups[trim($group['id'])]['itemsIds'] = [];
            }
            $groups[trim($group['id'])]['itemsIds'][] = (int)$cartItem->id;
        }
        $mTypesIds = $cart->cartType->material_types_ids;
        $mTypesIds = MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($mTypesIds);

        if ($set) {
            $sqlQuery = "SELECT tD.pid, tD.fid, tD.fii, tD.value
                           FROM cms_data AS tD
                           JOIN " . Material_Field::_tablename() . " AS tF ON tF.id = tD.fid
                          WHERE tF.datatype NOT IN ('file', 'image', 'htmlarea')
                            AND tF.classname = ?
                            AND tF.pid IN (" . implode(", ", $mTypesIds) . ")
                            AND tD.pid IN (" . implode(", ", array_keys($set)) . ")
                       ORDER BY tF.priority, tD.fii";
            $sqlResult = Material::_SQL()->get([$sqlQuery, Material_Type::class]);
            foreach ($sqlResult as $sqlRow) {
                $rawData[trim($sqlRow['pid'])][trim($sqlRow['fid'])][trim($sqlRow['fii'])] = $sqlRow['value'];
                $affectedFieldsIds[trim($sqlRow['fid'])] = (int)$sqlRow['fid'];
            }
        }

        if ($affectedFieldsIds) {
            $sqlQuery = "SELECT *
                           FROM " . Material_Field::_tablename() . "
                          WHERE id IN (" . implode(", ", $affectedFieldsIds) . ")
                       ORDER BY priority";
            $sqlResult = Material_Field::getSQLSet($sqlQuery);
            foreach ($sqlResult as $field) {
                $fields[trim($field->id)] = $field;
            }
        }

        $result = [
            'Set' => $set,
            'groups' => $groups,
            'rawData' => $rawData,
            'fields' => $fields,
        ];
        return $result;
    }
}
