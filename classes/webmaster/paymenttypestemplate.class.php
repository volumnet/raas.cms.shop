<?php
/**
 * Шаблон типа материалов "Типы оплаты"
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\Block_Form;
use RAAS\CMS\Block_Material;
use RAAS\CMS\FishRandomUserRetriever;
use RAAS\CMS\FishYandexReferatsRetriever;
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeTemplate;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;
use RAAS\CMS\Webmaster as CMSWebmaster;

/**
 * Класс шаблона типа материалов "Типы оплаты"
 */
class PaymentTypesTemplate extends MaterialTypeTemplate
{
    public $createMainSnippet = false;

    public $createMainBlock = false;

    public $createPage = false;

    public static $global = true;

    public function createFields()
    {
        $epayField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('EPAY'),
            'urn' => 'epay',
            'datatype' => 'checkbox',
        ]);
        $epayField->commit();

        $commissionField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('COMMISSION'),
            'urn' => 'commission',
            'datatype' => 'text',
        ]);
        $commissionField->commit();

        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $deliveryField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('LIMIT_BY_RECEIVING_METHODS'),
            'urn' => 'delivery',
            'multiple' => true,
            'datatype' => 'material',
            'source' => (int)$deliveryMaterialType->id,
        ]);
        $deliveryField->commit();

        return [
            $epayField->urn => $epayField,
            $commissionField->urn => $commissionField,
            $deliveryField->urn => $deliveryField,
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
        return parent::createBlock($page, $widget, $additionalData, true);
    }


    public function createMaterials(array $pagesIds = [])
    {
        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $deliveries = Material::getSet(['where' => [
            "pid = " . (int)$deliveryMaterialType->id,
        ], 'orderBy' => "id"]);

        $pickup = array_shift(array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 0) && !$x->service_urn;
        }));
        $courierDelivery = array_shift(array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && !$x->service_urn;
        }));
        $cdekPickup = array_shift(array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 0) && ($x->service_urn == 'cdek');
        }));
        $cdekDelivery = array_shift(array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && ($x->service_urn == 'cdek');
        }));
        $russianPost = array_shift(array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && ($x->service_urn == 'russianpost');
        }));

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_CASH_UPON_RECEIPT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_BANK_CARD_UPON_RECEIPT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_TRANSFER_TO_BANK_CARD'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('WITH_PAYMENT_ON_ACCOUNT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('WITH_ONLINE_PAYMENT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['epay']->addValue(1);

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
