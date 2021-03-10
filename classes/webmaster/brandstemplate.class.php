<?php
/**
 * Шаблон типа материалов "Марки"
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\FishYandexReferatsRetriever;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeTemplate;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс шаблона типа материалов "Новости"
 */
class BrandsTemplate extends MaterialTypeTemplate
{
    public $createMainSnippet = true;

    public $createMainBlock = true;

    public $createPage = true;

    public static $global = true;

    public function createFields()
    {
        $imageField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('IMAGE'),
            'multiple' => 0,
            'urn' => 'image',
            'datatype' => 'image',
            'show_in_table' => 1,
        ]);
        $imageField->commit();

        return [
            $imageField->urn => $imagesField,
        ];
    }

    public function createMaterials(array $pagesIds = [])
    {
        $result = [];
        $brandsImages = glob(Module::i()->resourcesDir . '/fish/brands/*.png');
        $textRetriever = new FishYandexReferatsRetriever();
        shuffle($brandsImages);
        for ($i = 0; $i < 10; $i++) {
            $text = $textRetriever->retrieve();
            $item = new Material([
                'pid' => (int)$this->materialType->id,
                'vis' => 1,
                'name' => View_Web::i()->_('BRAND') . ' ' . ($i + 1),
                'description' => $text['text'],
                'sitemaps_priority' => 0.5
            ]);
            $item->commit();
            $att = Attachment::createFromFile(
                $brandsImages[$i % count($brandsImages)],
                $item->fields['image']
            );
            $item->fields['image']->addValue(json_encode([
                'vis' => 1,
                'name' => '',
                'description' => '',
                'attachment' => (int)$att->id
            ]));
            $result[] = $item;
        }
        return $result;
    }


    public function createBlock(
        Page $page,
        Snippet $widget = null,
        array $additionalData = []
    ) {
        $additionalData = ['rows_per_page' => 20];
        if (!$page->pid) {
            $additionalData['location'] = 'content5';
        }
        return parent::createBlock($page, $widget, $additionalData);
    }
}
