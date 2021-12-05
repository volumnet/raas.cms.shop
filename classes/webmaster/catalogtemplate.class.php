<?php
/**
 * Шаблон типа материалов "Каталог продукции"
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\Block_Material;
use RAAS\CMS\FishYandexReferatsRetriever;
use RAAS\CMS\Material;
use RAAS\CMS\MaterialTypeTemplate;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Material_Type;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс шаблона типа материалов "Каталог продукции"
 */
class CatalogTemplate extends MaterialTypeTemplate
{
    public $createMainSnippet = true;

    public $createMainBlock = true;

    public $createPage = true;

    public static $global = false;

    /**
     * Блок каталога
     * @var Block_Material
     */
    public $catalogBlock;

    public function createFields()
    {
        $articleField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('ARTICLE'),
            'urn' => 'article',
            'datatype' => 'text',
            'show_in_table' => 1,
        ]);
        $articleField->commit();

        $priceOldField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('BASE_PRICE'),
            'urn' => 'price_old',
            'datatype' => 'number',
        ]);
        $priceOldField->commit();

        $priceField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('PRICE'),
            'urn' => 'price',
            'datatype' => 'number',
            'show_in_table' => 1,
        ]);
        $priceField->commit();

        $fixPriceField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('FIX_PRICE'),
            'urn' => 'fix_price',
            'datatype' => 'checkbox',
        ]);
        $fixPriceField->commit();

        $imagesField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('IMAGE'),
            'multiple' => 1,
            'urn' => 'images',
            'datatype' => 'image',
            'show_in_table' => 1,
        ]);
        $imagesField->commit();

        $videosField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('VIDEOS'),
            'multiple' => 1,
            'urn' => 'videos',
            'datatype' => 'text',
        ]);
        $videosField->commit();

        $filesField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('FILES'),
            'multiple' => 1,
            'urn' => 'files',
            'datatype' => 'file',
        ]);
        $filesField->commit();

        $specField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('SPECIAL_OFFER'),
            'urn' => 'spec',
            'datatype' => 'checkbox',
        ]);
        $specField->commit();

        $availableField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('AVAILABLE'),
            'urn' => 'available',
            'defval' => 1,
            'datatype' => 'checkbox',
        ]);
        $availableField->commit();

        $minField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('MINIMAL_AMOUNT'),
            'urn' => 'min',
            'defval' => 1,
            'datatype' => 'number',
        ]);
        $minField->commit();

        $stepField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('CART_STEP'),
            'urn' => 'step',
            'defval' => 1,
            'datatype' => 'number',
        ]);
        $stepField->commit();

        $relatedField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('RELATED_GOODS'),
            'multiple' => 1,
            'urn' => 'related',
            'datatype' => 'material',
            'source' => $this->materialType->id,
        ]);
        $relatedField->commit();

        $unitField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('UNIT'),
            'multiple' => 0,
            'urn' => 'unit',
            'datatype' => 'text',
            'placeholder' => View_Web::i()->_('PCS'),
        ]);
        $unitField->commit();

        $brandsMT = Material_Type::importByURN('brands');
        $brandField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('BRAND'),
            'multiple' => 0,
            'urn' => 'brand',
            'datatype' => 'material',
            'source' => (int)$brandsMT->id,
        ]);
        $brandField->commit();

        $ratingField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('RATING'),
            'multiple' => 0,
            'urn' => 'rating',
            'datatype' => 'number',
            'min_val' => 0,
            'max_val' => 5,
        ]);
        $ratingField->commit();

        $reviewsCounterField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('REVIEWS_COUNTER'),
            'multiple' => 0,
            'urn' => 'reviews_counter',
            'datatype' => 'number',
        ]);
        $reviewsCounterField->commit();

        $lengthField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('LENGTH_CM'),
            'multiple' => 0,
            'urn' => 'length',
            'datatype' => 'number',
        ]);
        $lengthField->commit();

        $widthField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('WIDTH_CM'),
            'multiple' => 0,
            'urn' => 'width',
            'datatype' => 'number',
        ]);
        $widthField->commit();

        $heightField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('HEIGHT_CM'),
            'multiple' => 0,
            'urn' => 'height',
            'datatype' => 'number',
        ]);
        $heightField->commit();

        $weightField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('WEIGHT_G'),
            'multiple' => 0,
            'urn' => 'weight',
            'datatype' => 'number',
        ]);
        $weightField->commit();

        $countryField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('COUNTRY'),
            'multiple' => 0,
            'urn' => 'country',
            'datatype' => 'text',
        ]);
        $countryField->commit();

        return [
            $articleField->urn => $articleField,
            $priceOldField->urn => $priceOldField,
            $priceField->urn => $priceField,
            $fixPriceField->urn => $fixPriceField,
            $imagesField->urn => $imagesField,
            $videosField->urn => $videosField,
            $filesField->urn => $filesField,
            $specField->urn => $specField,
            $availableField->urn => $availableField,
            $minField->urn => $minField,
            $stepField->urn => $stepField,
            $relatedField->urn => $relatedField,
            $unitField->urn => $unitField,
            $brandField->urn => $brandField,
            $ratingField->urn => $ratingField,
            $reviewsCounterField->urn => $reviewsCounterField,
            $lengthField->urn => $lengthField,
            $widthField->urn => $widthField,
            $heightField->urn => $heightField,
            $weightField->urn => $weightField,
            $countryField->urn => $countryField,
        ];
    }


    public function createBlockSnippet()
    {
        $filename = Module::i()->resourcesDir
                  . '/widgets/materials/catalog/catalog.tmp.php';
        $snippet = $this->webmaster->createSnippet(
            $this->materialType->urn,
            $this->materialType->name,
            (int)$this->widgetsFolder->id,
            $filename,
            $this->getReplaceData(
                $this->materialType->name,
                $this->materialType->urn
            )
        );
        return $snippet;
    }


    public function createMainPageSnippet()
    {
        $filename = Module::i()->resourcesDir
            . '/widgets/materials/catalog/spec.tmp.php';
        $snippet = Snippet::importByURN('spec');
        if (!$snippet->id) {
            $snippet = $this->webmaster->createSnippet(
                $this->materialType->urn . '_main',
                (
                    $this->materialType->name . ' — ' .
                    View_Web::i()->_('MATERIAL_TEMPLATE_MAIN_SUFFIX')
                ),
                (int)$this->widgetsFolder->id,
                $filename,
                $this->getReplaceData(
                    $this->materialType->name,
                    $this->materialType->urn
                )
            );
        }
        return $snippet;
    }


    /**
     * Создает дополнительные сниппеты
     * @return Snippet[] <pre><code>array<
     *     string[] URN сниппета => Snippet созданный или существующий сниппет
     * ></code></pre>
     */
    public function createAdditionalSnippets()
    {
        $widgets = [];
        $widgetsData = [
            'materials/catalog/catalog_item' => View_Web::i()->_('CATALOG_ITEM'),
            'materials/catalog/catalog_category' => View_Web::i()->_('CATEGORY_INC'),
            'materials/catalog/catalog_filter' => View_Web::i()->_('CATALOG_FILTER'),
            'materials/catalog/catalog_controls' => View_Web::i()->_('CATALOG_CONTROLS'),
        ];
        foreach ($widgetsData as $url => $name) {
            $urn = explode('/', $url);
            $urn = $urn[count($urn) - 1];
            $urn = str_replace('catalog_', $this->materialType->urn . '_', $urn);
            $widget = Snippet::importByURN($urn);
            if (!$widget->id) {
                $widget = $this->webmaster->createSnippet(
                    $urn,
                    $name,
                    (int)$this->widgetsFolder->id,
                    Module::i()->resourcesDir . '/widgets/' . $url . '.tmp.php',
                    [
                        'WIDGET_NAME' => $name,
                        'WIDGET_URN' => $urn,
                        'WIDGET_CSS_CLASSNAME' => str_replace('_', '-', $urn)
                    ]
                );
            }
            $widgets[$urn] = $widget;
        }
        return $widgets;
    }


    /**
     * Создает страницы каталога
     * @param Page $rootPage Корневая страница
     * @return Page[] <pre><code>array<
     *     string[] URN страницы => Page
     * ></code></pre>
     */
    public function createPages(Page $rootPage)
    {
        $result = [];
        $result['catalog'] = $this->webmaster->createPage(
            ['name' => View_Web::i()->_('CATALOG'), 'urn' => 'catalog'],
            $this->webmaster->Site
        );
        for ($i = 1; $i <= 3; $i++) {
            $urn = 'category' . $i;
            $result[$urn] = $this->webmaster->createPage([
                'name' => View_Web::i()->_('CATEGORY') . ' ' . $i,
                'urn' => $urn
            ], $result['catalog']);
        }
        for ($i = 1; $i <= 3; $i++) {
            $urn = 'category1' . $i;
            $result[$urn] = $this->webmaster->createPage([
                'name' => View_Web::i()->_('CATEGORY') . ' 1' . $i,
                'urn' => $urn
            ], $result['category1']);
        }
        for ($i = 1; $i <= 3; $i++) {
            $urn = 'category11' . $i;
            $result[$urn] = $this->webmaster->createPage([
                'name' => View_Web::i()->_('CATEGORY') . ' 11' . $i,
                'urn' => $urn,
            ], $result['category11']);
        }
        $categoriesImages = glob(
            Module::i()->resourcesDir . '/fish/categories/*.png'
        );
        shuffle($categoriesImages);
        $i = 0;
        foreach ($result as $category) {
            $att = Attachment::createFromFile(
                $categoriesImages[$i % count($categoriesImages)],
                $category->fields['image']
            );
            $category->fields['image']->addValue(json_encode([
                'vis' => 1,
                'name' => '',
                'description' => '',
                'attachment' => (int)$att->id
            ]));
            $i++;
        }
        return $result;
    }


    public function createBlock(
        Page $page,
        Snippet $widget = null,
        array $additionalData = []
    ) {
        if ($widget->id && $page->id) {
            $block = new Block_Material([
                'material_type' => (int)$this->materialType->id,
                'nat' => 1,
                'pages_var_name' => 'page',
                'rows_per_page' => 12,
                'sort_field_default' => (int)$this->materialType->fields['price']->id,
                'sort_order_default' => 'asc',
                'sort_var_name' => 'sort',
                'order_var_name' => 'order',
                'params' => 'metaTemplates=template&listMetaTemplates=list_template&withChildrenGoods=1&useAvailabilityOrder=available'
            ]);
            $block = $this->webmaster->createBlock(
                $block,
                'content',
                'catalog_interface',
                $widget,
                $page,
                true
            );
            $block->filter = [
                [
                    'id' => (int)$block->id,
                    'var' => 'search_string',
                    'relation' => 'FULLTEXT',
                    'field' => (int)$this->materialType->fields['article']->id,
                ]
            ];
            $block->sort = [
                [
                    'id' => (int)$block->id,
                    'var' => 'price',
                    'field' => (int)$this->materialType->fields['price']->id,
                    'relation' => 'asc',
                ],
                [
                    'id' => (int)$block->id,
                    'var' => 'name',
                    'field' => 'name',
                    'relation' => 'asc',
                ],
            ];
            $block->commit();
            $this->catalogBlock = $block;
            return $block;
        }
    }


    /**
     * Создает блоки для главной страницы
     * @return Block[]
     */
    public function createMainBlocks()
    {
        $specBlock = new Block_Material([
            'name' => View_Web::i()->_('SPECIAL_OFFER'),
            'material_type' => (int)$this->materialType->id,
            'nat' => 0,
            'params' => 'spec=1',
            'pages_var_name' => '',
            'rows_per_page' => 20,
            'sort_field_default' => 'random',
            'sort_order_default' => 'asc!',
            'sort_var_name' => '',
            'order_var_name' => '',
        ]);
        $specBlock->filter = [
            [
                'id' => (int)$specBlock->id,
                'var' => 'spec',
                'relation' => '=',
                'field' => (int)$this->materialType->fields['spec']->id,
            ]
        ];
        $specBlock = $this->webmaster->createBlock(
            $specBlock,
            'content',
            '__raas_shop_spec_interface',
            'spec',
            $this->webmaster->Site,
            false
        );

        $newBlock = new Block_Material([
            'name' => View_Web::i()->_('NEW_GOODS'),
            'material_type' => (int)$this->materialType->id,
            'nat' => 0,
            'pages_var_name' => '',
            'rows_per_page' => 20,
            'sort_field_default' => 'post_date',
            'sort_order_default' => 'desc!',
            'sort_var_name' => '',
            'order_var_name' => '',
        ]);
        $newBlock = $this->webmaster->createBlock(
            $newBlock,
            'content',
            '__raas_shop_spec_interface',
            'spec',
            $this->webmaster->Site,
            false
        );

        $popularBlock = new Block_Material([
            'name' => View_Web::i()->_('POPULAR_GOODS'),
            'material_type' => (int)$this->materialType->id,
            'nat' => 0,
            'params' => 'type=popular',
            'pages_var_name' => '',
            'rows_per_page' => 20,
            'sort_field_default' => 'post_date',
            'sort_order_default' => 'desc!',
            'sort_var_name' => '',
            'order_var_name' => '',
        ]);
        $popularBlock = $this->webmaster->createBlock(
            $popularBlock,
            'content',
            '__raas_shop_spec_interface',
            'spec',
            $this->webmaster->Site,
            false
        );

        return [
            $specBlock,
            $newBlock,
            $popularBlock,
        ];
    }


    public function createMaterials(array $pagesIds = [])
    {
        $result = [];
        $goodsImages = glob(Module::i()->resourcesDir . '/fish/products/*.jpg');
        shuffle($goodsImages);
        $brandsMaterialType = Material_Type::importByURN('brands');
        $brands = Material::getSet([
            'where' => "pid = " . (int)$brandsMaterialType->id,
            'orderBy' => "id",
        ]);
        $units = ['', 'гр', 'комплект', 'м'];
        $textRetriever = new FishYandexReferatsRetriever();
        for ($i = 0; $i < 10; $i++) {
            $text = $textRetriever->retrieve();
            $item = new Material([
                'pid' => (int)$this->materialType->id,
                'vis' => 1,
                'name' => View_Web::i()->_('GOODS_ITEM') . ' ' . ($i + 1),
                'description' => $text['text'],
                'sitemaps_priority' => 0.5,
                'cats' => $pagesIds,
            ]);
            $item->commit();
            $item->fields['article']->addValue(dechex(crc32($i)));
            $item->fields['price']->addValue($price = rand(100, 100000));
            $item->fields['price_old']->addValue(
                ($price % 2) ? (int)($price * (100 + rand(5, 25)) / 100) : 0
            );
            $item->fields['videos']->addValue(
                'https://www.youtube.com/watch?v=YVgc2PQd_bo'
            );
            $item->fields['videos']->addValue(
                'https://www.youtube.com/watch?v=YVgc2PQd_bo'
            );
            $item->fields['spec']->addValue(1);
            $item->fields['available']->addValue((int)(bool)($i % 4));
            $item->fields['min']->addValue($i % 4 ? 1 : 2);
            $item->fields['step']->addValue($i % 4 ? 1 : 2);
            foreach (['test.doc', 'test.pdf'] as $val) {
                $att = Attachment::createFromFile(
                    Module::i()->resourcesDir . '/fish/' . $val,
                    $item->fields['files']
                );

                $item->fields['files']->addValue(json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => (int)$att->id
                ]));
            }

            for ($j = 0; $j < 5; $j++) {
                $att = Attachment::createFromFile(
                    $goodsImages[($i * 5 + $j) % count($goodsImages)],
                    $item->fields['images']
                );
                $item->fields['images']->addValue(json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => (int)$att->id
                ]));
            }
            $item->fields['brand']->addValue($brands[rand(0, count($brands) - 1)]->id);
            $item->fields['unit']->addValue($units[rand(0, count($units) - 1)]);
            $item->fields['rating']->addValue(rand(1, 5));
            $item->fields['reviews_counter']->addValue(rand(0, 100));
            $item->fields['length']->addValue(rand(0, 1000));
            $item->fields['width']->addValue(rand(0, 1000));
            $item->fields['height']->addValue(rand(0, 1000));
            $item->fields['weight']->addValue(rand(0, 1000));
            $item->fields['country']->addValue(View_Web::i()->_('COUNTRY') . ' ' . ($i + 1));
            $result[] = $item;
        }
        for ($i = 0; $i < 10; $i++) {
            $item = $result[$i];
            $item->fields['related']->addValue($result[rand(0, 9)]->id);
            $item->fields['related']->addValue($result[rand(0, 9)]->id);
            $item->fields['related']->addValue($result[rand(0, 9)]->id);
        }
        return $result;
    }


    public function create()
    {
        $widget = Snippet::importByURN($this->materialType->urn);
        if (!$widget->id) {
            $widget = $this->createBlockSnippet();
        }

        if ($this->createMainSnippet) {
            $mainWidget = $this->createMainPageSnippet();
            if ($this->createMainBlock) {
                $blocksMain = $this->createMainBlocks();
            }
        }
        $this->createAdditionalSnippets();

        if ($this->createPage) {
            $temp = Page::getSet(['where' => [
                "pid = " . (int)$this->webmaster->Site->id,
                "urn = '" . $this->materialType->urn . "'"
            ]]);
            if ($temp) {
                $page = $temp[0];
            } else {
                $pages = $this->createPages($this->webmaster->Site);
                $page = $pages['catalog'];
                $pagesIds = [
                    $this->webmaster->Site->id,
                    $pages['category111']->id,
                    $pages['category112']->id,
                    $pages['category113']->id,
                    $pages['category12']->id,
                    $pages['category13']->id,
                    $pages['category2']->id,
                    $pages['category3']->id,
                ];
                $block = $this->createBlock($page, $widget);
                $this->createMaterials($pagesIds);
            }
        } else {
            $block = $this->createBlock(
                $this->webmaster->Site,
                $widget,
                ['nat' => 0]
            );
        }

        if ($this->createPage) {
            return $pages['catalog'];
        }
    }
}
