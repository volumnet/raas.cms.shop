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

        $canUseWithPostamatesField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('CAN_USE_WITH_POSTAMATES'),
            'urn' => 'can_use_with_postamates',
            'datatype' => 'checkbox',
            'defval' => 1,
        ]);
        $canUseWithPostamatesField->commit();

        return [
            $epayField->urn => $epayField,
            $commissionField->urn => $commissionField,
            $deliveryField->urn => $deliveryField,
            $canUseWithPostamatesField->urn => $canUseWithPostamatesField,
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
        $result = [];
        $deliveryMaterialType = Material_Type::importByURN('delivery');
        $deliveries = Material::getSet(['where' => [
            "pid = " . (int)$deliveryMaterialType->id,
        ], 'orderBy' => "id"]);

        $pickupSet = array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 0) && !$x->service_urn;
        });
        $pickup = array_shift($pickupSet);
        $courierDeliverySet = array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && !$x->service_urn;
        });
        $courierDelivery = array_shift($courierDeliverySet);
        $cdekPickupSet = array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 0) && ($x->service_urn == 'cdek');
        });
        $cdekPickup = array_shift($cdekPickupSet);
        $cdekDeliverySet = array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && ($x->service_urn == 'cdek');
        });
        $cdekDelivery = array_shift($cdekDeliverySet);
        $russianPostSet = array_filter($deliveries, function ($x) {
            return ($x->receiving_method == 1) && ($x->service_urn == 'russianpost');
        });
        $russianPost = array_shift($russianPostSet);

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_CASH_UPON_RECEIPT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $result[] = $item;

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_BANK_CARD_UPON_RECEIPT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $result[] = $item;

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WITH_TRANSFER_TO_BANK_CARD'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['can_use_with_postamates']->addValue(1);
        $result[] = $item;

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('WITH_PAYMENT_ON_ACCOUNT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['can_use_with_postamates']->addValue(1);
        $result[] = $item;

        $item = new Material([
            'pid' => (int)$this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('WITH_ONLINE_PAYMENT'),
            'description' => '',
            'sitemaps_priority' => 0.5,
        ]);
        $item->commit();
        $item->fields['epay']->addValue(1);
        $item->fields['can_use_with_postamates']->addValue(1);
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
