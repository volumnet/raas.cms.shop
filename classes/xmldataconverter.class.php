<?php
/**
 * Файл конвертера XML-данных
 */
namespace RAAS\CMS\Shop;

use SimpleXMLElement;
use DOMDocument;
use XSLTProcessor;

/**
 * Класс конвертера XML-данных
 */
class XmlDataConverter
{
    /**
     * XML-файл для обработки
     * @var string
     */
    protected $xmlFile;

    /**
     * XSL-файл для обработки
     * @var string
     */
    protected $xslFile;

    /**
     * Конструктор класса
     * @param string $xmlFile XML-файл для обработки
     * @param string $xslFile XSL-файл для обработки
     */
    public function __construct($xmlFile = null, $xslFile = null)
    {
        $this->xmlFile = $xmlFile;
        $this->xslFile = $xslFile;
    }


    /**
     * Возвращает отформатированные данные
     * @param array $data Данные с предыдущих этапов
     * @return array
     */
    public function process($data = [])
    {
        if ($this->xmlFile) {
            $sxe = $this->loadXML($this->xmlFile, $this->xslFile);
            $data = $this->parseXML($sxe, $data);
        }
        return $data;
    }


    /**
     * Загружает XML-файл, при необходимости применяя XSL-преобразование из другого файла
     * @param string $xmlFile Путь к XML-файлу
     * @param string $xslFile Путь к XSL-файлу
     * @return SimpleXMLElement
     */
    public function loadXML($xmlFile, $xslFile = null)
    {
        $xmlText = file_get_contents($xmlFile);
        if ($xslFile) {
            $xslText = file_get_contents($xslFile);
            $xmlText = $this->applyXSL($xmlText, $xslText);
        }
        $sxe = new SimpleXMLElement($xmlText);
        return $sxe;
    }



    /**
     * Преобразует XML при помощи XSLT
     * @param string $xml XML-текст для преобразования
     * @param string $xsl XSL-текст для преобразования
     * @return string Преобразованный XML
     */
    public function applyXSL($xml, $xsl)
    {
        $xmlDom = new DOMDocument();
        $xslDom = new DOMDocument();
        $xmlDom->loadXML($xml);
        $xslDom->loadXML($xsl);
        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xslDom);
        $newXML = $proc->transformToXML($xmlDom);
        return $newXML;
    }


    /**
     * Разбирает данные XML
     * @param SimpleXMLElement $importXML Данные для разбора в стандартном формате
     * @param array $data Данные с предыдущих этапов
     * @return array Измененные данные
     */
    public function parseXML(SimpleXMLElement $importXML, array $data = [])
    {
        if ($importXML) {
            // $this->registerXMLPrefix($importXML, 'xmlns');
            foreach ([
                'pages' => 'Page',
                'materials' => 'Material',
                'materialTypes' => 'Material_Type',
                'fields' => 'Field'
            ] as $groupTag => $itemTag) {
                if (!isset($data[$groupTag])) {
                    $data[$groupTag] = [];
                }
                $nodes = $importXML->xpath($groupTag . '/' . $itemTag);
                $data[$groupTag] = $this->parseXMLNodes($nodes, $data[$groupTag]);
            }
        }
        return $data;
    }


    /**
     * Разбирает XML-узел сущности
     * @param SimpleXMLElement $sxe Узел для разбора
     * @param array $data Данные для обновления
     * @return array Обновленные данные
     */
    public function parseXMLNode(SimpleXMLElement $sxe, array $data = [])
    {
        if ($sxe['delete']) {
            $data['@delete'] = true;
        }
        foreach ($sxe->children() as $key => $childSxe) {
            switch ($key) {
                case 'pages_ids':
                    foreach ($childSxe->children() as $pageIdSxe) {
                        $pageId = trim($pageIdSxe);
                        $data[$key][$pageId] = $pageId;
                    }
                    break;
                case 'values':
                    foreach ($childSxe->children() as $valueSxe) {
                        $data['@values'][trim($valueSxe['id'])] = trim($valueSxe);
                    }
                    break;
                case 'fields':
                    foreach ($childSxe->children() as $fieldSxe) {
                        $fieldKey = null;
                        if ($fieldURN = trim($fieldSxe['urn'] ?? '')) {
                            $fieldKey = $fieldURN;
                        } elseif ($fieldId = trim($fieldSxe['id'] ?? '')) {
                            $fieldKey = 'id:' . $fieldId;
                        }

                        if ($fieldKey) {
                            if ($fieldValuesSxe = $fieldSxe->value) {
                                // var_dump('aaa'); exit;
                                foreach ($fieldValuesSxe as $fieldValueSxe) {
                                    $data['fields'][$fieldKey][] = trim($fieldValueSxe);
                                }
                            } else {
                                $data['fields'][$fieldKey] = trim($fieldSxe);
                            }

                            foreach (['create', 'update'] as $configKey) {
                                if ($configVal = trim($fieldSxe[$configKey] ?? '')) {
                                    $data['@config'][$configKey][$fieldKey] = !in_array(
                                        $configVal,
                                        ['0', 'false', '-1', 'no']
                                    );
                                }
                            }
                        }
                    }
                    break;
                default:
                    $data[$key] = trim($childSxe);
                    break;
            }
            foreach (['create', 'update', 'map'] as $configKey) {
                if ($configVal = trim((string)$childSxe[$configKey])) {
                    $data['@config'][$configKey][$key] = !in_array(
                        $configVal,
                        ['0', 'false', '-1', 'no']
                    );
                }
            }
        }
        return $data;
    }


    /**
     * Разбирает дерево узлов, сохраняя предыдущие данные
     * @param array<SimpleXMLElement> $sxeArr Массив узлов для обработки
     * @param array<string[] ID# узла (берется из элемента id) => array<
     *            string[] => mixed
     *        >> $data Данные из предыдущих данных
     * @return array<string[] ID# узла (берется из элемента id) => array<
     *            string[] => mixed
     *        >> Обновленные данные
     */
    public function parseXMLNodes(array $sxeArr = [], $data = [])
    {
        $newData = $data;
        foreach ($sxeArr as $sxe) {
            $id = trim($sxe->id);
            $entry = isset($data[$id]) ? $data[$id] : [];
            $arr = $this->parseXMLNode($sxe, $entry);
            $newData[$arr['id']] = $arr;
        }
        return $newData;
    }
}
