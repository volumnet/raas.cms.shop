<?php
/**
 * Файл теста конвертера данных XML
 */
namespace RAAS\CMS\Shop;

use SimpleXMLElement;

/**
 * Класс теста конвертера данных XML
 */
class XmlDataConverterTest extends BaseTest
{
    /**
     * Тестирует разбора XML-узла сущности
     */
    public function testParseXMLNode()
    {
        $text = ' <Material delete="true">
                    <id>b323ca07-96e9-11e8-9a9f-6cf04909dac2</id>
                    <pid update="false">4</pid>
                    <name>Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок</name>
                    <description>
                      Блок-кубик Attache серии Fanasy для записей выполнен из белой офсетной бумаги. Голубой пластиковый стакан обеспечивает удобство использования и порядок на рабочем столе. Блок-кубик упакован в термоусадочную пленку. Размер изделия (ШхДхВ): 90х90х50 мм Плотность бумаги: офсет 70-80 г/м2. Белизна: 86-92 %.
                    </description>
                    <pages_ids update="true">
                      <pageId>e7a0df86-96e8-11e8-9a9f-6cf04909dac2</pageId>
                    </pages_ids>
                    <fields>
                      <field urn="article" update="false">354656</field>
                      <field urn="related">
                        <value>1</value>
                        <value>2</value>
                        <value>3</value>
                      </field>
                      <field id="ce78d3b0-d5cc-11e8-9aa9-6cf04909dac2">ce78d3b5-d5cc-11e8-9aa9-6cf04909dac2</field>
                      <field id="ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2">90х90х50 мм</field>
                      <field id="ce78d3e2-d5cc-11e8-9aa9-6cf04909dac2">Российская Федерация</field>
                      <field id="ce78d3eb-d5cc-11e8-9aa9-6cf04909dac2">пластик</field>
                      <field id="ce78d429-d5cc-11e8-9aa9-6cf04909dac2">белый</field>
                      <field id="ce78d43d-d5cc-11e8-9aa9-6cf04909dac2">80</field>
                      <field id="f13e6d9c-d5cc-11e8-9aa9-6cf04909dac2">86-92 %</field>
                      <field id="f13e6d9d-d5cc-11e8-9aa9-6cf04909dac2">Да</field>
                      <field id="f13e6d9e-d5cc-11e8-9aa9-6cf04909dac2">f13e6d9f-d5cc-11e8-9aa9-6cf04909dac2</field>
                    </fields>
                  </Material>';
        $sxe = new SimpleXMLElement($text);
        $xdc = new XmlDataConverter();

        $result = $xdc->parseXMLNode($sxe);

        $this->assertTrue($result['@delete']);
        $this->assertEquals('b323ca07-96e9-11e8-9a9f-6cf04909dac2', $result['id']);
        $this->assertEquals(4, $result['pid']);
        $this->assertFalse($result['@config']['update']['pid']);
        $this->assertTrue($result['@config']['update']['pages_ids']);
        $this->assertFalse($result['@config']['update']['article']);
        $this->assertEquals('354656', $result['fields']['article']);
        $this->assertEquals('90х90х50 мм', $result['fields']['id:ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2']);
        $this->assertEquals('e7a0df86-96e8-11e8-9a9f-6cf04909dac2', $result['pages_ids']['e7a0df86-96e8-11e8-9a9f-6cf04909dac2']);
        $this->assertEquals(['1', '2', '3'], $result['fields']['related']);
    }


    /**
     * Тестирует разбора XML-узла сущности с предыдущими данными
     */
    public function testParseXMLNodeWithOldData()
    {
        $text = ' <Material>
                    <id>b323ca07-96e9-11e8-9a9f-6cf04909dac2</id>
                    <fields>
                      <field urn="price">104</field>
                      <field urn="available">0</field>
                    </fields>
                  </Material>';
        $data = [
            'id' => 'b323ca07-96e9-11e8-9a9f-6cf04909dac2',
            'pid' => '4',
            '@config' => ['update' => ['pid' => false, 'pages_ids' => true]],
            'name' => 'Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок',
            'description' => 'Блок-кубик Attache серии Fanasy для записей выполнен из белой офсетной бумаги. Голубой пластиковый стакан обеспечивает удобство использования и порядок на рабочем столе. Блок-кубик упакован в термоусадочную пленку. Размер изделия (ШхДхВ): 90х90х50 мм Плотность бумаги: офсет 70-80 г/м2. Белизна: 86-92 %.',
            'pages_ids' => [
                'e7a0df86-96e8-11e8-9a9f-6cf04909dac2' => 'e7a0df86-96e8-11e8-9a9f-6cf04909dac2',
            ],
            'fields' => [
                'article' => '354656',
                'id:ce78d3b0-d5cc-11e8-9aa9-6cf04909dac2' => 'ce78d3b5-d5cc-11e8-9aa9-6cf04909dac2',
                'id:ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2' => '90х90х50 мм',
                'id:f13e6d9e-d5cc-11e8-9aa9-6cf04909dac2' => 'f13e6d9f-d5cc-11e8-9aa9-6cf04909dac2',
            ],
        ];
        $sxe = new SimpleXMLElement($text);
        $xdc = new XmlDataConverter();

        $result = $xdc->parseXMLNode($sxe, $data);

        $this->assertEquals('b323ca07-96e9-11e8-9a9f-6cf04909dac2', $result['id']);
        $this->assertEquals(4, $result['pid']);
        $this->assertFalse($result['@config']['update']['pid']);
        $this->assertTrue($result['@config']['update']['pages_ids']);
        $this->assertEquals('104', $result['fields']['price']);
    }





    /**
     * Тестирует разбор дерева узлов
     */
    public function testParseXMLNodes()
    {
        $text1 = '<Material>
                    <id>b323ca07-96e9-11e8-9a9f-6cf04909dac2</id>
                    <fields>
                      <field urn="price">104</field>
                      <field urn="available">0</field>
                    </fields>
                  </Material>';
        $text2 = '<Material>
                    <id>b323ca0b-96e9-11e8-9a9f-6cf04909dac2</id>
                    <fields>
                      <field urn="price">154</field>
                      <field urn="available">0</field>
                    </fields>
                  </Material>';
        $text3 = '<Material>
                    <id>b323ca16-96e9-11e8-9a9f-6cf04909dac2</id>
                    <fields>
                      <field urn="price">352</field>
                      <field urn="available">0</field>
                    </fields>
                  </Material>';
        $sxe1 = new SimpleXMLElement($text1);
        $sxe2 = new SimpleXMLElement($text2);
        $sxe3 = new SimpleXMLElement($text3);
        $nodes = [$sxe1, $sxe2, $sxe3];
        $xdc = new XmlDataConverter();

        $result = $xdc->parseXMLNodes($nodes);

        $this->assertEquals('b323ca07-96e9-11e8-9a9f-6cf04909dac2', $result['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['id']);
        $this->assertEquals('b323ca0b-96e9-11e8-9a9f-6cf04909dac2', $result['b323ca0b-96e9-11e8-9a9f-6cf04909dac2']['id']);
        $this->assertEquals('b323ca16-96e9-11e8-9a9f-6cf04909dac2', $result['b323ca16-96e9-11e8-9a9f-6cf04909dac2']['id']);
    }


    /**
     * Тестирует разбор данных XML
     */
    public function testParseXML()
    {
        $sxe = new SimpleXMLElement($this->getResourcesDir() . '/1cresult.xml', 0, true);
        $xdc = new XmlDataConverter();

        $result = $xdc->parseXML($sxe);

        $this->assertEquals('Каталог продукции', $result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertFalse($result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['@config']['update']['pid']);
        $this->assertEquals('Блок-кубик Attache Selection куб 76х76, желтый неон 400 л', $result['materials']['b323ca0b-96e9-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertEquals('383720', $result['materials']['b323ca0b-96e9-11e8-9a9f-6cf04909dac2']['fields']['article']);
    }



    /**
     * Тестирует разбор данных XML
     */
    public function testParseXMLWithOldData()
    {
        $data = [
            'pages' => [
                'e7a0df87-96e8-11e8-9a9f-6cf04909dac2' => [
                    'id' => 'e7a0df87-96e8-11e8-9a9f-6cf04909dac2',
                    'pid' => '',
                    '@config' => ['update' => ['pid' => false]],
                    'name' => 'Каталог продукции',
                ]
            ],
            'materials' => [
                'b323ca07-96e9-11e8-9a9f-6cf04909dac2' => [
                    'id' => 'b323ca07-96e9-11e8-9a9f-6cf04909dac2',
                    'pid' => '4',
                    '@config' => ['update' => ['pid' => false, 'pages_ids' => true]],
                    'name' => 'Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок',
                    'description' => 'Блок-кубик Attache серии Fanasy для записей выполнен из белой офсетной бумаги. Голубой пластиковый стакан обеспечивает удобство использования и порядок на рабочем столе. Блок-кубик упакован в термоусадочную пленку. Размер изделия (ШхДхВ): 90х90х50 мм Плотность бумаги: офсет 70-80 г/м2. Белизна: 86-92 %.',
                    'pages_ids' => [
                        'e7a0df86-96e8-11e8-9a9f-6cf04909dac2' => 'e7a0df86-96e8-11e8-9a9f-6cf04909dac2',
                    ],
                    'fields' => [
                        'article' => '354656',
                        'id:ce78d3b0-d5cc-11e8-9aa9-6cf04909dac2' => 'ce78d3b5-d5cc-11e8-9aa9-6cf04909dac2',
                        'id:ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2' => '90х90х50 мм',
                        'id:f13e6d9e-d5cc-11e8-9aa9-6cf04909dac2' => 'f13e6d9f-d5cc-11e8-9aa9-6cf04909dac2',
                    ],
                ]
            ],
        ];
        $sxe = new SimpleXMLElement($this->getResourcesDir() . '/1cresultoffers.xml', 0, true);
        $xdc = new XmlDataConverter();

        $result = $xdc->parseXML($sxe, $data);

        $this->assertEquals('Каталог продукции', $result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertFalse($result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['@config']['update']['pid']);
        $this->assertEquals('Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertEquals('354656', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['article']);
        $this->assertEquals('104', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['price']);
        $this->assertEquals('0', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['available']);
    }



    /**
     * Тестирует преобразования XML при помощи XSLT
     */
    public function testApplyXSL()
    {
        $xml = file_get_contents($this->getResourcesDir() . '/import0_1.update.xml');
        $xsl = file_get_contents($this->getResourcesDir() . '/import.xsl');
        $xdc = new XmlDataConverter();

        $result = $xdc->applyXSL($xml, $xsl);
        $sxe = new SimpleXMLElement($result);

        $this->assertEquals('Ценовой сегмент', trim($sxe->fields->Field[0]->name));
        $this->assertEquals('e7a0df87-96e8-11e8-9a9f-6cf04909dac2', trim($sxe->pages->Page[2]->pid));
        $this->assertEquals('false', trim($sxe->materials->Material[0]->pid['update']));
    }


    /**
     * Тестирует загрузки XML-файла
     */
    public function testLoadXML()
    {
        $xmlFile = $this->getResourcesDir() . '/import0_1.update.xml';
        $xslFile = $this->getResourcesDir() . '/import.xsl';
        $xdc = new XmlDataConverter();

        $result = $xdc->loadXML($xmlFile, $xslFile);

        $this->assertEquals('Ценовой сегмент', trim($result->fields->Field[0]->name));
        $this->assertEquals('e7a0df87-96e8-11e8-9a9f-6cf04909dac2', trim($result->pages->Page[2]->pid));
        $this->assertEquals('false', trim($result->materials->Material[0]->pid['update']));
    }


    /**
     * Тестирует возврата отформатированных данных
     */
    public function testProcess()
    {
        $xmlFile = $this->getResourcesDir() . '/import0_1.update.xml';
        $xslFile = $this->getResourcesDir() . '/import.xsl';
        $xdc = new XmlDataConverter($xmlFile, $xslFile);

        $result = $xdc->process();

        $this->assertEquals('Каталог продукции', $result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertFalse($result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['@config']['update']['pid']);
        $this->assertEquals('Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertEquals('354656', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['article']);
        // $this->assertEquals('104', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['price']);
        // $this->assertEquals('0', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['available']);
    }


    /**
     * Тестирует возврата отформатированных данных со старыми данными
     */
    public function testProcessWithOldData()
    {
        $xmlFile = $this->getResourcesDir() . '/offers0_1.update.xml';
        $xslFile = $this->getResourcesDir() . '/offers.xsl';
        $data = [
            'pages' => [
                'e7a0df87-96e8-11e8-9a9f-6cf04909dac2' => [
                    'id' => 'e7a0df87-96e8-11e8-9a9f-6cf04909dac2',
                    'pid' => '',
                    '@config' => ['update' => ['pid' => false]],
                    'name' => 'Каталог продукции',
                ]
            ],
            'materials' => [
                'b323ca07-96e9-11e8-9a9f-6cf04909dac2' => [
                    'id' => 'b323ca07-96e9-11e8-9a9f-6cf04909dac2',
                    'pid' => '4',
                    '@config' => ['update' => ['pid' => false, 'pages_ids' => true]],
                    'name' => 'Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок',
                    'description' => 'Блок-кубик Attache серии Fanasy для записей выполнен из белой офсетной бумаги. Голубой пластиковый стакан обеспечивает удобство использования и порядок на рабочем столе. Блок-кубик упакован в термоусадочную пленку. Размер изделия (ШхДхВ): 90х90х50 мм Плотность бумаги: офсет 70-80 г/м2. Белизна: 86-92 %.',
                    'pages_ids' => [
                        'e7a0df86-96e8-11e8-9a9f-6cf04909dac2' => 'e7a0df86-96e8-11e8-9a9f-6cf04909dac2',
                    ],
                    'fields' => [
                        'article' => '354656',
                        'id:ce78d3b0-d5cc-11e8-9aa9-6cf04909dac2' => 'ce78d3b5-d5cc-11e8-9aa9-6cf04909dac2',
                        'id:ce78d3d3-d5cc-11e8-9aa9-6cf04909dac2' => '90х90х50 мм',
                        'id:f13e6d9e-d5cc-11e8-9aa9-6cf04909dac2' => 'f13e6d9f-d5cc-11e8-9aa9-6cf04909dac2',
                    ],
                ]
            ],
        ];
        $xdc = new XmlDataConverter($xmlFile, $xslFile);

        $result = $xdc->process($data);

        $this->assertEquals('Каталог продукции', $result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertFalse($result['pages']['e7a0df87-96e8-11e8-9a9f-6cf04909dac2']['@config']['update']['pid']);
        $this->assertEquals('Блок-кубик ATTACHE Fantasy 9х9х5 стакан голубой белый блок', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['name']);
        $this->assertEquals('354656', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['article']);
        $this->assertEquals('104', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['price']);
        $this->assertEquals('0', $result['materials']['b323ca07-96e9-11e8-9a9f-6cf04909dac2']['fields']['available']);
    }
}
