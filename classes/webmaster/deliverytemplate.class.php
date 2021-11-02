<?php
/**
 * Шаблон типа материалов "Способы получения"
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeTemplate;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс шаблона типа материалов "Способы получения"
 */
class DeliveryTemplate extends MaterialTypeTemplate
{
    public $createMainSnippet = false;

    public $createMainBlock = false;

    public $createPage = false;

    public static $global = true;

    public function createFields()
    {
        $briefNameField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('BRIEF_NAME'),
            'urn' => 'brief',
            'datatype' => 'text',
        ]);
        $briefNameField->commit();

        $priceField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('PRICE'),
            'urn' => 'price',
            'datatype' => 'number',
        ]);
        $priceField->commit();

        $minSumField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('MINIMAL_FREE_SUM'),
            'urn' => 'min_sum',
            'datatype' => 'number',
        ]);
        $minSumField->commit();

        $receivingMethodsField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('RECEIVING_METHOD'),
            'urn' => 'delivery',
            'datatype' => 'radio',
            'required' => true,
            'source_type' => 'ini',
            'source' => '0 = "' . View_Web::i()->_('PICKUP') . '"' . "\n" .
                        '1 = "' . View_Web::i()->_('DELIVERY') . '"',
        ]);
        $receivingMethodsField->commit();

        $serviceURN = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('SERVICE_URN'),
            'urn' => 'service_urn',
            'datatype' => 'text',
        ]);
        $serviceURN->commit();

        return [
            $briefNameField->urn => $briefNameField,
            $priceField->urn => $priceField,
            $minSumField->urn => $minSumField,
            $receivingMethodsField->urn => $receivingMethodsField,
            $serviceURN->urn => $serviceURN,
        ];
    }


    public function createBlock(
        Page $page,
        Snippet $widget = null,
        array $additionalData = []
    ) {
        $additionalData = array_merge(
            [
                'interface_id' => 0,
                'nat' => 0,
                'vis' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 0,
                'location' => 'head_counters',
            ],
            $additionalData
        );
        $result = parent::createBlock($page, $widget, $additionalData, true);
        return $result;
    }


    public function createMaterials(array $pagesIds = [])
    {
        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('SALES_OFFICE_PICKUP'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['brief']->addValue(View_Web::i()->_('SALES_OFFICE'));
        $item->fields['price']->addValue(0);
        $item->fields['min_sum']->addValue(0);
        $item->fields['delivery']->addValue(0);

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('COURIER_DELIVERY'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['price']->addValue(300);
        $item->fields['min_sum']->addValue(1000);
        $item->fields['delivery']->addValue(1);

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'urn' => 'cdek',
            'name' => View_Web::i()->_('CDEK_PICKUP'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['brief']->addValue(View_Web::i()->_('CDEK'));
        $item->fields['delivery']->addValue(0);
        $item->fields['service_urn']->addValue('cdek');

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'urn' => 'cdek',
            'name' => View_Web::i()->_('CDEK_DELIVERY'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['brief']->addValue(View_Web::i()->_('CDEK'));
        $item->fields['delivery']->addValue(1);
        $item->fields['service_urn']->addValue('cdek');

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'urn' => 'russianpost',
            'name' => View_Web::i()->_('RUSSIAN_POST_DELIVERY'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['brief']->addValue(View_Web::i()->_('RUSSIAN_POST'));
        $item->fields['delivery']->addValue(1);
        $item->fields['service_urn']->addValue('russianpost');

        return $result;
    }


    public function create()
    {
        $temp = Page::getSet(['where' => [
            "pid = " . (int)$this->webmaster->Site->id,
            "urn = 'cart'"
        ]]);
        if ($temp) {
            $block = $this->createBlock($temp[0]);
        }
        $this->createMaterials();
        return null;
    }
}
