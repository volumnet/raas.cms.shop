<?php
/**
 * Шаблон типа материалов "Скидки"
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
class DiscountTemplate extends MaterialTypeTemplate
{
    public $createMainSnippet = false;

    public $createMainBlock = false;

    public $createPage = false;

    public static $global = true;

    public function createFields()
    {
        $discountField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => true,
            'name' => View_Web::i()->_('DISCOUNT_PERCENT'),
            'urn' => 'discount',
            'datatype' => 'number',
            'show_in_table' => true,
        ]);
        $discountField->commit();

        $codeURN = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => true,
            'name' => View_Web::i()->_('PROMO_CODE'),
            'urn' => 'code',
            'multiple' => true,
            'datatype' => 'text',
        ]);
        $codeURN->commit();

        return [
            $discountField->urn => $discountField,
            $codeURN->urn => $codeURN,
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
        $result = [];
        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('TEST_PROMO_CODE'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['discount']->addValue(10);
        $item->fields['code']->addValue('test10');
        $result[] = $item;

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
