<?php
/**
 * Файл класса интерфейса синхронизации с 1С
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use DOMDocument;
use SimpleXMLElement;
use XSLTProcessor;
use SOME\Namespaces;
use SOME\SOME;
use RAAS\Application;
use RAAS\Attachment;
use RAAS\Exception;
use RAAS\CMS\AbstractInterface;
use RAAS\CMS\Field;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Package;
use RAAS\CMS\Page;

/**
 * Класс интерфейса синхронизации с 1С
 */
class Sync1CInterface extends AbstractInterface
{
    use BatchDeleteTrait;
    use InheritPageTrait;

    /**
     * Не удалять предыдущие материалы и страницы
     */
    const DELETE_PREVIOUS_MATERIALS_NONE = 0;

    /**
     * Удалять только предыдущие материалы
     */
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY = 1;

    /**
     * Удалять предыдущие материалы и страницы
     */
    const DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES = 2;

    /**
     * Загрузка прайса на сервер
     * @param ?Page $page Страница, в которую загружаем
     * @param ?Material_Type $materialType Тип материалов
     * @param ?string $goodsFile Путь к файлу номенклатуры
     * @param ?string $offersFile Путь к файлу предложений
     * @param ?string $goodsXSLFile Путь к XSL-файлу преобразования номенклатуры
     * @param ?string $offersXSLFile Путь к XSL-файлу преобразования предложений
     * @param ?string $mappingFile Файл маппинга
     * @param string $articleFieldURN URN поля с артикулом
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param int $clear Очищать предыдущие материалы (константа из self::DELETE_PREVIOUS_MATERIALS_...)
     * @param ?callable $logger Логгер <pre><code>function (string Текст для вывода)</code></pre>
     * @param int $saveMappingAfterIterations Сохранять файл маппинга после определенного количества итераций
     */
    public function process(
        ?Page $page = null,
        ?Material_Type $materialType = null,
        ?string $goodsFile = null,
        ?string $offersFile = null,
        ?string $goodsXSLFile = null,
        ?string $offersXSLFile = null,
        ?string $mappingFile = null,
        $articleFieldURN = 'article',
        $dir = __DIR__,
        $clear = self::DELETE_PREVIOUS_MATERIALS_NONE,
        ?callable $logger = null,
        $saveMappingAfterIterations = 100
    ) {
        $data = $this->loadData($goodsFile, $offersFile, $goodsXSLFile, $offersXSLFile);
        $articlesMapping = $this->getArticlesMapping($materialType, $articleFieldURN);
        $affected = $this->processData(
            $page,
            $materialType,
            $data,
            $articlesMapping,
            $dir,
            $logger,
            $mappingFile,
            $saveMappingAfterIterations
        );
        $this->clear($page, $materialType, $clear, $logger, $affected);
    }


    // Независимые функции

    /**
     * Загрузка данных
     * @param string $goodsFile Путь к файлу номенклатуры
     * @param string $offersFile Путь к файлу предложений
     * @param string $goodsXSLFile Путь к XSL-файлу преобразования номенклатуры
     * @param string $offersXSLFile Путь к XSL-файлу преобразования предложений
     * @return array
     */
    public function loadData($goodsFile = null, $offersFile = null, $goodsXSLFile = null, $offersXSLFile = null)
    {
        $data = [];
        if ($goodsFile && is_file($goodsFile)) {
            $xdc = new XmlDataConverter($goodsFile, $goodsXSLFile);
            $data = $xdc->process();
        }
        if ($offersFile && is_file($offersFile)) {
            $xdc = new XmlDataConverter($offersFile, $offersXSLFile);
            $data = $xdc->process($data);
        }
        return $data;
    }


    /**
     * Получает маппинг по артикулам
     * @param Material_Type $materialType Тип материала, у которого есть поле с артикулом
     * @param string $articleFieldURN URN поля с артикулом
     * @return array<string[] Артикул => int ID# товара> маппинг по артикулам
     */
    public function getArticlesMapping(Material_Type $materialType, $articleFieldURN = 'article')
    {
        $result = [];
        $field = $materialType->fields[$articleFieldURN];
        if ($field->id) {
            $sqlQuery = "SELECT value, pid FROM cms_data WHERE fid = ?";
            $sqlBind = [$field->id];
            $sqlResult = Application::i()->SQL->get([$sqlQuery, $sqlBind]);
            foreach ($sqlResult as $sqlRow) {
                $result[trim($sqlRow['value'])] = (int)$sqlRow['pid'];
            }
        }
        return $result;
    }


    /**
     * Проверяет, есть ли специальные настройки по полю
     * @param array $data Данные по одной сущности
     * @param string $key Идентификатор поля (URN или специальный идентификатор)
     * @param 'update'|'create'|'delete'|'map' $condition Настройка, которую проверяем
     * @param mixed $defaultValue Если настройка не задана, значение по умолчанию
     * @return mixed Значение настройки или значение по умолчанию
     */
    public function isSpecialField(array $data, $key = 'id', $condition = 'map', $defaultValue = false)
    {
        if (!isset($data['@config'][$condition][$key])) {
            return $defaultValue;
        }
        return (bool)$data['@config'][$condition][$key];
    }


    /**
     * Поиск сущности по нативному полю
     * @param string $classname Класс сущности
     * @param string $fieldName Наименование нативного поля
     * @param mixed $value Значение поля
     * @param array<
     *            (string[] поле => mixed значение)|
     *            (string SQL-инструкция)
     *        > $context Набор дополнительных условий для выборки
     * @return SOME
     */
    public function findEntityByField($classname, $fieldName, $value, array $context = [])
    {
        $context[$fieldName] = (string)$value;
        $sqlWhere = [];
        $sqlBind = [];
        foreach ($context as $k => $v) {
            if (is_numeric($k)) {
                $sqlWhereRow = "(" . $v . ")";
            } else {
                if (is_array($v)) {
                    $v = array_map(function ($x) {
                        return is_numeric($x) ? $x : "'" . Application::i()->SQL->real_escape_string($x) . "'";
                    }, $v);
                    $sqlWhereRow = $k . " IN (" . implode(", ", $v) . ")";
                } else {
                    $sqlWhereRow = $k . " = "
                                 . (is_numeric($v) ? $v : "'" . Application::i()->SQL->real_escape_string($v) . "'");
                }
            }
            $sqlWhere[] = $sqlWhereRow;
        }
        $sqlResult = $classname::getSet(['where' => $sqlWhere, 'orderBy' => 'id']);
        if ($sqlResult) {
            return array_shift($sqlResult);
        }
    }


    /**
     * Получает сообщение о действии с сущностью
     * @param SOME $entity Сущность
     * @param int $i Индекс текущей сущности в массиве
     * @param int $c Количество сущностей в массиве
     * @return string description
     */
    public function logEntityMessage(SOME $entity, $i = 0, $c = 0)
    {
        $message = '';
        if ($entity->deleted) {
            $message .= 'Deleted ';
        } elseif ($entity->orphan) {
            $message .= 'Orphan skipped ';
        } elseif ($entity->new) {
            $message .= 'Created ';
        } else {
            $message .= 'Updated ';
        }
        $message .= Namespaces::getClass($entity)
                 . ($entity->id ? ' #' . $entity->id : '')
                 .  ' (' . $entity->name . ')';
        if ($i && $c) {
            $message .= ' - ' . $i . '/' . $c;
        }
        return $message;
    }

    /**
     * Загружает маппинг
     * @param string|null $mappingFile Файл маппинга
     * @return array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >>
     */
    public function loadMapping($mappingFile = null)
    {
        $mapping = [];
        if ($mappingFile && is_file($mappingFile)) {
            $mapping = (array)json_decode(file_get_contents($mappingFile), true);
        }
        return $mapping;
    }

    /**
     * Сохраняет маппинг
     * @param string|null $mappingFile Файл маппинга
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     */
    public function saveMapping($mappingFile = null, array $mapping = [])
    {
        if ($mappingFile) {
            $json = json_encode($mapping);
            file_put_contents($mappingFile, $json);
        }
    }

    // Конец независимых функций


    /**
     * Ищем сущность по полю данных по маппингу
     * @param string $classname Класс сущности
     * @param array $data Набор данных
     * @param string $fieldName Ключ, по значению которого ищем
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @return object|null Объект класса $classname, либо null, если не найден
     */
    public function findEntityById($classname, array $data, $fieldName, array $mapping)
    {
        if ($this->isSpecialField($data, $fieldName, 'map', true)) {
            if (isset($data[$fieldName]) && isset($mapping[$classname][$data[$fieldName]])) {
                $id = $mapping[$classname][$data[$fieldName]];
                $entity = new $classname($id);
                if ($entity->id) {
                    return $entity;
                }
            }
        } else {
            $id = $data[$fieldName];
            $entity = new $classname($id);
            if ($entity->id) {
                return $entity;
            }
        }
    }


    /**
     * Поиск или создание сущности
     * @param string $classname Класс сущности
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $idN Наименование поля ID#
     * @param string $searchField Наименование поля, по которому ищем
     * @param string $parentClassname Наименование родительского класса
     * @param string $pidN Наименование поля ID# родителя
     * @param ?SOME $defaultParent Родительская сущность по умолчанию
     * @param bool $withParentChildren Учитывать дочерние элементы для родительского, в качестве родительских
     * @return SOME Найденная или созданная сущность
     */
    public function findOrCreateEntity(
        $classname,
        array $data,
        array &$mapping,
        $idN = 'id',
        $searchField = 'name',
        $parentClassname = null,
        $pidN = 'pid',
        ?SOME $defaultParent = null,
        $withParentChildren = false
    ) {
        $entity = $this->findEntityById($classname, $data, $idN, $mapping);
        if (!$entity) {
            $context = [];
            if ($parentClassname && $pidN) {
                $parent = $this->findEntityById($parentClassname, $data, $pidN, $mapping);
                if (!$parent) {
                    $parent = $defaultParent;
                }
                if ($parent->id) {
                    if ($withParentChildren && ($selfAndChildrenIds = $parent->selfAndChildrenIds)) {
                        $context = ['pid' => $selfAndChildrenIds];
                    } else {
                        $context = ['pid' => (int)$parent->id];
                    }
                }
            }
            if (isset($data[$searchField])) {
                $entity = $this->findEntityByField($classname, $searchField, $data[$searchField], $context);
            }
            if ($entity) {
                if ($this->isSpecialField($data, $idN, 'map', true)) {
                    $mapping[$classname][$data[$idN]] = (int)$entity->$idN;
                }
            }
        }
        if (!($entity && $entity->id)) {
            $entity = new $classname();
        }
        return $entity;
    }


    /**
     * Обновляет нативные поля сущности (без сохранения) с учетом конфигурации по обновлению
     * @param SOME $entity Сущность для обновления
     * @param array $data Данные по сущности для обновления
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     */
    public function updateEntity(SOME $entity, array $data, array $mapping)
    {
        $classname = get_class($entity);
        foreach ($data as $key => $val) {
            if ($key[0] == '@') {
                continue;
            }
            $ref = null;
            if ($refKey = $classname::getReferenceByFK($key)) {
                $ref = $classname::_references($refKey);
            }
            $defaultFieldUpdates = true;
            if ($key == $classname::_idN()) {
                $defaultFieldUpdates = false;
            } elseif ($ref) {
                $defaultFieldUpdates = !$entity->id;
            }
            if ($this->isSpecialField($data, $key, $entity->id ? 'update' : 'create', $defaultFieldUpdates)) {
                if (isset($ref['classname'])) {
                    if ($this->isSpecialField($data, $key, 'map', true)) {
                        if (isset($mapping[$ref['classname']][$val])) {
                            $val = $mapping[$ref['classname']][$val];
                        }
                    }
                }
                // 2024-07-03, AVS: обновляем только регулярные поля и id
                // Иначе из-за обновлений порядка получения свойств в SOME перезаписываются $entity->fields
                if (in_array($classname::typeof($key), [$classname::FIELD_ID, $classname::FIELD_REGULAR])) {
                    $entity->$key = $val;
                }
            }
        }
    }


    /**
     * Обновляет привязку к страницам
     * @param Material $entity Материал для обновления
     * @param array $data Данные по материалу для обновления
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Page $defaultParent Родительская страница по умолчанию (если не найдены) - тогда материал устанавливается скрытым
     * @return false В случае, если материал глобальный
     */
    public function updatePages(Material $entity, array $data, array $mapping, Page $defaultParent)
    {
        if ($entity->material_type->global_type) {
            return false;
        }
        $pagesIds = array_values(array_filter((array)($data['pages_ids'] ?? [])));
        $deleteOldPages = $this->isSpecialField($data, 'pages_ids', 'update', false);
        $addPagesToExisting = $this->isSpecialField($data, 'pages_ids', 'create', true);

        $realPagesIds = [];
        foreach ($pagesIds as $pageId) {
            if ($this->isSpecialField($data, 'pages_ids', 'map', true)) {
                if (isset($mapping[Page::class][$pageId])) {
                    $pageId = $mapping[Page::class][$pageId];
                }
            }
            $realPagesIds[] = $pageId;
        }
        $realPagesIds = array_filter(array_map('intval', $realPagesIds));

        if ($deleteOldPages) {
            $newPagesIds = [];
        } else {
            $newPagesIds = (array)$entity->pages_ids;
        }
        if ($addPagesToExisting || !$entity->id) {
            $newPagesIds = array_values(array_unique(array_merge($newPagesIds, $realPagesIds)));
        }
        $entity->cats = $newPagesIds;
        if (!$entity->cats && !$entity->id) {
            $entity->cats = [(int)$defaultParent->id];
            $entity->vis = 0;
        }
    }


    /**
     * Обновляет значение кастомного поля
     * @param Field $field Поле, значение которого нужно обновить
     * @param string $fieldKey Ключ поля в данных по сущности
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $dir Путь к папке с файлами для медиа-полей
     */
    public function updateCustomField(Field $field, $fieldKey, array $data, array $mapping, $dir)
    {
        $field->deleteValues();
        $values = (array)$data['fields'][$fieldKey];
        if (in_array($field->datatype, ['file', 'image'])) {
            foreach ($values as $val) {
                if (is_file($filename = $dir . '/' . $val)) {
                    $att = Attachment::createFromFile(
                        $filename,
                        $field,
                        Package::i()->registryGet('max_size') ?? 0,
                        Package::i()->registryGet('tn_size') ?? 0
                    );

                    $json = json_encode(
                        ['attachment' => $att->id, 'vis' => 1, 'name' => '', 'description' => '']
                    );
                    $field->addValue($json);
                }
            }
        } else {
            $mapFieldByDefault = (
                in_array($field->datatype, ['material', 'select', 'radio']) ||
                (($field->datatype == 'checkbox') && $field->multiple)
            );
            $mapValues = $this->isSpecialField($data, $fieldKey, 'map', $mapFieldByDefault);
            foreach ($values as $val) {
                if ($mapValues) {
                    if ($field->datatype == 'material') {
                        $val = $mapping[Material::class][$val];
                    } elseif (isset($mapping['source'][$field->id][$val])) {
                        $val = $mapping['source'][$field->id][$val];
                    }
                }
                $field->addValue($val);
            }
        }
    }


    /**
     * Обновить кастомные поля для сущности с учетом их настроек по созданию/обновлению
     * @param SOME $entity Сущность, для которой обновляем поля
     * @param bool $new Сущность только что создана
     * @param array $data Данные по сущности
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param string $dir Путь к папке с файлами для медиа-полей
     */
    public function updateCustomFields(SOME $entity, $new, array $data, array $mapping, $dir)
    {
        if (!$entity->fields || !isset($data['fields'])) {
            return false;
        }
        $classname = get_class($entity);
        foreach ((array)$data['fields'] as $key => $val) {
            if ($key[0] == '@') {
                continue;
            }
            if ($this->isSpecialField($data, $key, $new ? 'create' : 'update', true)) {
                $fieldKey = '';
                if (preg_match('/^id\\:(.*?)$/umi', $key, $regs)) {
                    if (isset($mapping[Material_Field::class][$regs[1]])) {
                        $field = new Material_Field((int)$mapping[Material_Field::class][$regs[1]]);
                        if ($field->id) {
                            $fieldKey = $field->urn;
                        }
                    }
                } else {
                    $fieldKey = $key;
                }
                if ($field = $entity->fields[$fieldKey]) {
                    $this->updateCustomField($field, $key, $data, $mapping, $dir);
                }
            }
        }
    }


    /**
     * Найти или создать тип материала
     * @param array $data Данные по типу материала
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых типов
     * @return Material_Type Найденный или созданный тип материала
     */
    public function findOrCreateMaterialType(array $data, array &$mapping, Material_Type $defaultParent)
    {
        $entity = $this->findOrCreateEntity(
            Material_Type::class,
            $data,
            $mapping,
            'id',
            'name',
            Material_Type::class,
            'pid',
            $defaultParent,
            false
        );
        return $entity;
    }


    /**
     * Найти или создать поле
     * @param array $data Данные по полю
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых полей
     * @return Field Найденное или созданное поле
     */
    public function findOrCreateField(array $data, array &$mapping, Material_Type $defaultParent)
    {
        $entity = $this->findOrCreateEntity(
            Material_Field::class,
            $data,
            $mapping,
            'id',
            'name',
            Material_Type::class,
            'pid',
            $defaultParent,
            true
        );
        return $entity;
    }


    /**
     * Найти или создать страницу
     * @param array $data Данные по странице
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Page $defaultParent Родительская страница по умолчанию для вновь создаваемых типов
     * @return Page Найденная или созданная страница
     */
    public function findOrCreatePage(array $data, array &$mapping, Page $defaultParent)
    {
        $entity = $this->findOrCreateEntity(
            Page::class,
            $data,
            $mapping,
            'id',
            'name',
            Page::class,
            'pid',
            $defaultParent,
            false
        );
        return $entity;
    }


    /**
     * Найти или создать материал
     * @param array $data Данные по материалу
     * @param array<string[] Артикул => int ID# товара> $articlesMapping маппинг по артикулам
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых материалов
     * @return Material Найденный или созданный материал
     */
    public function findOrCreateMaterial(array $data, array $articlesMapping, array &$mapping, Material_Type $defaultParent)
    {
        $entity = $this->findEntityById(Material::class, $data, 'id', $mapping);
        if (!$entity) {
            if (isset($data['fields']['article'], $articlesMapping[$data['fields']['article']])) {
                $id = $articlesMapping[$data['fields']['article']];
                $entity = new Material($id);
            }
            if (!$entity) {
                $parent = $this->findEntityById(Material_Type::class, $data, 'pid', $mapping);
                if (!$parent) {
                    $parent = $defaultParent;
                }
                if ($parent->id) {
                    $context = ['pid' => (int)$parent->id];
                }
            }
            if ($entity) {
                if ($this->isSpecialField($data, 'id', 'map', true)) {
                    $mapping[Material::class][$data['id']] = (int)$entity->id;
                }
            }
        }
        if (!($entity && $entity->id)) {
            $context['vis'] = 1;
            $entity = new Material($context);
        }
        return $entity;
    }


    /**
     * Обрабатывает полученные данные по типу материалов (с сохранением и обновлением маппинга)
     * @param array $data Данные по типу материала
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых типов
     * @return Material_Type Найденный или созданный тип материала (со свойством deleted, если удален или new, если новый)
     */
    public function processMaterialType(array $data, array &$mapping, Material_Type $defaultParent)
    {
        $entity = $this->findOrCreateMaterialType($data, $mapping, $defaultParent);
        if ($data['@delete'] ?? null) {
            $entityToDelete = $entity;
            Material_Type::delete($entityToDelete);
            $entity->deleted = true;
            return $entity;
        }
        $this->updateEntity($entity, $data, $mapping);
        $new = !$entity->id;
        if ($new) {
            $entity->new = true;
        }
        $entity->commit();
        if ($new) {
            $mapping[Material_Type::class][$data['id']] = $entity->id;
        }
        return $entity;
    }


    /**
     * Обрабатывает полученные данные по полю (с сохранением и обновлением маппинга)
     * @param array $data Данные по полю
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых полей
     * @return Field Найденное или созданное поле (со свойством deleted, если удален или new, если новый)
     */
    public function processField(array $data, array &$mapping, Material_Type $defaultParent)
    {
        $entity = $this->findOrCreateField($data, $mapping, $defaultParent);
        if ($data['@delete'] ?? null) {
            $entityToDelete = $entity;
            Material_Field::delete($entityToDelete);
            $entity->deleted = true;
            return $entity;
        }
        $this->updateEntity($entity, $data, $mapping);
        $new = !$entity->id;
        if ($new) {
            $entity->new = true;
        }
        $entity->commit();
        if ($new) {
            $mapping[Material_Field::class][$data['id']] = $entity->id;
        }
        if ($data['@values'] ?? null) {
            $mapping['source'][$entity->id] = $data['@values'];
        }
        return $entity;
    }


    /**
     * Обрабатывает полученные данные по странице (с сохранением, кастомными полями и обновлением маппинга)
     * @param array $data Данные по странице
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Page $defaultParent Родительская страница по умолчанию для вновь создаваемых типов
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @return Page Найденная или созданная страница
     *              (со свойством deleted, если удален, new, если новый и orphan, если не входит в каталог)
     */
    public function processPage(array $data, array &$mapping, Page $defaultParent, $dir)
    {
        $entity = $this->findOrCreatePage($data, $mapping, $defaultParent);
        if ($data['@delete'] ?? null) {
            $entityToDelete = $entity;
            Page::delete($entityToDelete);
            $entity->deleted = true;
            return $entity;
        }
        $new = !$entity->id;
        $this->updateEntity($entity, $data, $mapping);
        if ($new) {
            $entity->new = true;
            if (!$entity->pid) {
                $entity->orphan = true;
                return $entity;
            }
            $this->inheritPageNativeFields($entity); // Вызываем после updateEntity, т.к. до этого родитель не установлен
        }
        $entity->commit();
        if ($new) {
            $this->inheritPageCustomFields($entity);
            $mapping[Page::class][$data['id']] = $entity->id;
        }
        $this->updateCustomFields($entity, $new, $data, $mapping, $dir);
        return $entity;
    }


    /**
     * Обрабатывает полученные данные по материалу (с сохранением, кастомными полями и обновлением маппинга)
     * @param array $data Данные по материалу
     * @param array<string[] Артикул => int ID# товара> $articlesMapping маппинг по артикулам
     * @param array<string[] Имя класса => array<
     *            string[] Значение уникального поля => int ID# сущности
     *        >> $mapping Полный маппинг по всем классам
     * @param Material_Type $defaultParent Родительский тип материала по умолчанию для вновь создаваемых материалов
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param Page $page Страница по умолчанию, в которую загружаем
     * @return Material Найденный или созданный материал (со свойством deleted, если удален или new, если новый)
     */
    public function processMaterial(
        array $data,
        array $articlesMapping,
        array &$mapping,
        Material_Type $defaultParent,
        $dir,
        Page $page
    ) {
        $entity = $this->findOrCreateMaterial($data, $articlesMapping, $mapping, $defaultParent);
        if ($data['@delete'] ?? null) {
            $entityToDelete = $entity;
            Material::delete($entityToDelete);
            $entity->deleted = true;
            return $entity;
        }
        $this->updateEntity($entity, $data, $mapping);
        $this->updatePages($entity, $data, $mapping, $page);
        $new = !$entity->id;
        if ($new) {
            $entity->new = true;
        }
        $entity->commit();
        if ($new) {
            $mapping[Material::class][$data['id']] = $entity->id;
        }
        $this->updateCustomFields($entity, $new, $data, $mapping, $dir);
        return $entity;
    }


    /**
     * Обрабатывает полученные данные
     * @param array $data Данные по всем сущностям
     * @param array $articlesMapping <pre><code>array<
     *     string[] Артикул => int ID# товара
     * ></code></pre> маппинг по артикулам
     * @param string $dir Путь к папке с файлами для медиа-полей
     * @param ?callable $logger Логгер <pre><code>function (string Текст для вывода)</code></pre>
     * @param ?string $mappingFile Файл маппинга
     * @param int $saveMappingAfterIterations Сохранять файл маппинга после определенного количества итераций
     * @return array<string[] Имя класса => array<int>> Задействованные сущности
     */
    public function processData(
        Page $page,
        Material_Type $materialType,
        array $data,
        array $articlesMapping,
        string $dir,
        ?callable $logger = null,
        ?string $mappingFile = null,
        int $saveMappingAfterIterations = 100
    ): array {
        $mapping = $this->loadMapping($mappingFile);
        $affected = [];
        foreach ([
            'materialTypes' => Material_Type::class,
            'fields' => Material_Field::class,
            'pages' => Page::class,
            'materials' => Material::class
        ] as $dataSectionURI => $classname) {
            if (isset($data[$dataSectionURI])) {
                $dataSection = (array)$data[$dataSectionURI];
                $i = 0;
                $c = count($dataSection);
                foreach ($dataSection as $uuid => $entityData) {
                    switch ($classname) {
                        case Material_Type::class:
                            $entity = $this->processMaterialType((array)$entityData, $mapping, $materialType);
                            break;
                        case Material_Field::class:
                            $entity = $this->processField((array)$entityData, $mapping, $materialType);
                            break;
                        case Page::class:
                            $entity = $this->processPage((array)$entityData, $mapping, $page, $dir);
                            break;
                        case Material::class:
                            $entity = $this->processMaterial((array)$entityData, $articlesMapping, $mapping, $materialType, $dir, $page);
                            break;
                    }
                    $i++;
                    $message = $this->logEntityMessage($entity, $i, $c);
                    $logger ? $logger($message) : null;
                    $affected[$classname][$entity->id] = $entity->id;
                    if ($mappingFile && $saveMappingAfterIterations && $i && !($i % $saveMappingAfterIterations)) {
                        $this->saveMapping($mappingFile, $mapping);
                    }
                }
                if ($mappingFile) {
                    $this->saveMapping($mappingFile, $mapping);
                }
            }
        }
        return $affected;
    }


    /**
     * Очищает материалы
     * @param Material_Type $materialType Тип материалов
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<int> $affectedMaterialsIds Массив ID# "затронутых" материалов
     * @param callable $logger Логгер <pre><code>function (string Текст для вывода)</code></pre>
     */
    public function clearMaterials(
        Material_Type $materialType,
        Page $deleteRoot,
        array $affectedMaterialsIds = [],
        ?callable $logger = null
    ) {
        $logger ? $logger('Start clearing old materials') : null;
        $assets = $this->findMaterialsFieldsAndAttachmentsToClear($materialType, $deleteRoot, $affectedMaterialsIds);
        $materialsToClearIds = $assets[Material::class];
        $attachmentsToClearIds = $assets[Attachment::class];

        if ($logger) {
            $logmessages = [];
            for ($i = 0, $c = count($materialsToClearIds); $i < $c; $i++) {
                $entity = new Material($materialsToClearIds[$i]);
                $entity->deleted = true;
                $message = $this->logEntityMessage($entity, $i + 1, $c);
                $logmessages[] = $message;
            }
            for ($i = 0, $c = count($attachmentsToClearIds); $i < $c; $i++) {
                $entity = new Attachment($attachmentsToClearIds[$i]);
                $entity->deleted = true;
                $message = $this->logEntityMessage($entity, $i + 1, $c);
                $logmessages[] = $message;
            }
        }
        $this->deleteMaterialsByIds($materialsToClearIds);
        $this->deleteAttachmentsByIds($attachmentsToClearIds);
        if ($logger) {
            foreach ($logmessages as $message) {
                $logger($message);
            }
        }
    }


    /**
     * Очищает страницы
     * @param Page $deleteRoot Корень для удаления материалов
     * @param array<int> $affectedPagesIds Массив ID# "затронутых" страниц
     * @param ?callable $logger Логгер <pre><code>function (string Текст для вывода)</code></pre>
     */
    public function clearPages(Page $deleteRoot, array $affectedPagesIds = [], ?callable $logger = null)
    {
        $logger ? $logger('Start clearing old pages') : null;
        $assets = $this->findPagesFieldsAndAttachmentsToClear($deleteRoot, $affectedPagesIds);
        $pagesToClearIds = $assets[Page::class];
        $attachmentsToClearIds = $assets[Attachment::class];

        if ($logger) {
            $logmessages = [];
            for ($i = 0, $c = count($pagesToClearIds); $i < $c; $i++) {
                $entity = new Page($pagesToClearIds[$i]);
                $entity->deleted = true;
                $message = $this->logEntityMessage($entity, $i + 1, $c);
                $logmessages[] = $message;
            }
            for ($i = 0, $c = count($attachmentsToClearIds); $i < $c; $i++) {
                $entity = new Attachment($attachmentsToClearIds[$i]);
                $entity->deleted = true;
                $message = $this->logEntityMessage($entity, $i + 1, $c);
                $logmessages[] = $message;
            }
        }
        $this->deletePagesByIds($pagesToClearIds);
        $this->deleteAttachmentsByIds($attachmentsToClearIds);
        if ($logger) {
            foreach ($logmessages as $message) {
                $logger($message);
            }
        }
    }


    /**
     * Очищает материалы и/или страницы
     * @param ?Page $deleteRoot Корень для удаления материалов
     * @param ?Material_Type $materialType Тип материалов
     * @param int $clear Очищать предыдущие материалы (константа из self::DELETE_PREVIOUS_MATERIALS_...)
     * @param ?callable $logger Логгер <pre><code>function (string Текст для вывода)</code></pre>
     * @param array<string[] Имя класса => array<int>> $affected Массив ID# "затронутых" сущностей по классам
     */
    public function clear(
        ?Page $deleteRoot = null,
        ?Material_Type $materialType = null,
        $clear = self::DELETE_PREVIOUS_MATERIALS_NONE,
        ?callable $logger = null,
        array $affected = array()
    ) {
        if (isset($affected[Material::class]) &&
            in_array(
                $clear,
                [self::DELETE_PREVIOUS_MATERIALS_MATERIALS_ONLY, self::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES]
            )
        ) {
            // Очищаем материалы
            $this->clearMaterials($materialType, $deleteRoot, $affected[Material::class], $logger);
        }
        if (isset($affected[Page::class]) && ($clear == self::DELETE_PREVIOUS_MATERIALS_MATERIALS_AND_PAGES)) {
            // Очищаем страницы
            $this->clearPages($deleteRoot, $affected[Page::class], $logger);
        }
    }
}
