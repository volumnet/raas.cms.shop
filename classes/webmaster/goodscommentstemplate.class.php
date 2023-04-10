<?php
/**
 * Шаблон типа материалов "Отзывы к товарам"
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
 * Класс шаблона типа материалов "Отзывы к товарам"
 */
class GoodsCommentsTemplate extends MaterialTypeTemplate
{
    /**
     * Параметр блока каталога "блок формы комментариев"
     */
    const FORM_BLOCK_PARAM = 'commentFormBlock';

    /**
     * Параметр блока каталога "блок списка комментариев"
     */
    const LIST_BLOCK_PARAM = 'commentsListBlock';

    public $createMainSnippet = false;

    public $createMainBlock = false;

    public $createPage = false;

    public static $global = false;

    /**
     * Блок каталога
     * @var Block_Material
     */
    protected $catalogBlock;

    /**
     * Конструктор класса
     * @param Material_Type $materialType Тип материалов
     * @param CMSWebmaster $webmaster Вебмастер
     * @param Block_Material $catalogBlock Блок каталога
     */
    public function __construct(
        Material_Type $materialType,
        CMSWebmaster $webmaster,
        Block_Material $catalogBlock
    ) {
        $this->materialType = $materialType;
        $this->webmaster = $webmaster;
        $this->catalogBlock = $catalogBlock;
    }


    public function createFields()
    {
        $materialField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('MATERIAL'),
            'urn' => 'material',
            'datatype' => 'material',
            'source' => (int)$this->catalogBlock->material_type,
            'show_in_table' => 1,
        ]);
        $materialField->commit();

        $dateField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('DATE'),
            'urn' => 'date',
            'datatype' => 'date',
        ]);
        $dateField->commit();

        $phoneField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('PHONE'),
            'urn' => 'phone',
            'datatype' => 'tel',
        ]);
        $phoneField->commit();

        $emailField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 0,
            'name' => View_Web::i()->_('EMAIL'),
            'urn' => 'email',
            'datatype' => 'email',
        ]);
        $emailField->commit();

        $ratingField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('RATING'),
            'multiple' => 0,
            'urn' => 'rating',
            'datatype' => 'number',
            'min_val' => 0,
            'max_val' => 5,
            'show_in_table' => 1,
        ]);
        $ratingField->commit();

        $advantagesField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ADVANTAGES'),
            'multiple' => 0,
            'urn' => 'advantages',
            'datatype' => 'textarea',
        ]);
        $advantagesField->commit();

        $disadvantagesField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('DISADVANTAGES'),
            'multiple' => 0,
            'urn' => 'disadvantages',
            'datatype' => 'textarea',
        ]);
        $disadvantagesField->commit();

        return [
            $materialField->urn => $materialField,
            $dateField->urn => $dateField,
            $phoneField->urn => $phoneField,
            $emailField->urn => $emailField,
            $ratingField->urn => $ratingField,
            $advantagesField->urn => $advantagesField,
            $disadvantagesField->urn => $disadvantagesField,
        ];
    }


    /**
     * Создает форму вопросов
     * @return Form
     */
    public function createForm()
    {
        $notificationSnippet = Snippet::importByURN('__raas_form_notify');
        $form = $this->webmaster->createForm([
            'name' => $this->materialType->name,
            'urn' => $this->materialType->urn,
            'material_type' => (int)$this->materialType->id,
            'interface_id' => (int)$notificationSnippet->id,
            'fields' => [
                [
                    'vis' => 0,
                    'name' => View_Web::i()->_('MATERIAL'),
                    'urn' => 'material',
                    'datatype' => 'material',
                    'source' => (int)$this->catalogBlock->material_type,
                    'show_in_table' => 1,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('YOUR_NAME'),
                    'urn' => '_name_',
                    'required' => 1,
                    'datatype' => 'text',
                    'show_in_table' => 1,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('PHONE'),
                    'urn' => 'phone',
                    'datatype' => 'text',
                    'show_in_table' => 1,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('EMAIL'),
                    'urn' => 'email',
                    'datatype' => 'email',
                    'show_in_table' => 0,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('RATING'),
                    'urn' => 'rating',
                    'datatype' => 'number',
                    'min_val' => 1,
                    'max_val' => 5,
                    'show_in_table' => 0,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('ADVANTAGES'),
                    'multiple' => 0,
                    'urn' => 'advantages',
                    'datatype' => 'textarea',
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('DISADVANTAGES'),
                    'multiple' => 0,
                    'urn' => 'disadvantages',
                    'datatype' => 'textarea',
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('COMMENT'),
                    'urn' => '_description_',
                    'required' => 1,
                    'datatype' => 'textarea',
                    'show_in_table' => 0,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('AGREE_PRIVACY_POLICY'),
                    'urn' => 'agree',
                    'required' => 1,
                    'datatype' => 'checkbox',
                ],
            ]
        ]);
        return $form;
    }


    public function createBlockSnippet($nat = false)
    {
        $widget = Snippet::importByURN('rating');
        if (!$widget->id) {
            $widget = $this->webmaster->createSnippet(
                'rating',
                View_Web::i()->_('RATING'),
                (int)$this->widgetsFolder->id,
                Module::i()->resourcesDir . '/widgets/materials/comments/rating.tmp.php',
                [
                    'WIDGET_NAME' => View_Web::i()->_('RATING'),
                    'WIDGET_URN' => 'rating',
                    'WIDGET_CSS_CLASSNAME' => 'rating',
                ]
            );
        }

        $filename = Module::i()->resourcesDir
                  . '/widgets/materials/comments/goods_comments.tmp.php';
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


    /**
     * Создает сниппет формы
     */
    public function createFormSnippet()
    {
        $filename = Module::i()->resourcesDir
                  . '/widgets/materials/comments/goods_comments_form.tmp.php';
        $snippet = $this->webmaster->createSnippet(
            $this->materialType->urn . '_form',
            View_Web::i()->_('GOODS_COMMENTS_FORM'),
            (int)$this->widgetsFolder->id,
            $filename,
            $this->getReplaceData(
                $this->materialType->name,
                $this->materialType->urn
            )
        );
        return $snippet;
    }


    public function createBlock(
        Page $page,
        Snippet $widget = null,
        array $additionalData = []
    ) {
        $additionalData = array_merge(
            [
                'interface_id' => (int)Snippet::importByURN('__raas_shop_goods_comments_interface')->id,
                'name' => View_Web::i()->_('REVIEWS'),
                'nat' => 0,
                'vis' => 0,
                'pages_var_name' => '',
                'rows_per_page' => 0,
                'sort_field_default' => $this->materialType->fields['date']->id,
                'sort_order_default' => 'desc!',
                'params' => 'materialFieldURN=material',
            ],
            $additionalData
        );
        return parent::createBlock($page, $widget, $additionalData, true);
    }


    /**
     * Создает блок формы
     * @param Page $page Страница материалов
     * @param Form $form Форма
     * @param Snippet|null $widget Виджет блока
     * @param array $additionalData Дополнительные параметры
     * @return Block_Form|null
     */
    public function createFormBlock(
        Page $page,
        Form $form,
        Snippet $widget = null,
        array $additionalData = []
    ) {
        if ($widget->id && $page->id) {
            $blockData = array_merge([
                'vis' => 0,
                'form' => (int)$form->id,
                'interface_id' => (int)Snippet::importByURN('__raas_form_interface')->id,
                'widget_id' => (int)$widget->id,
                'location' => 'content',
                'inherit' => 1,
                'cats' => (array)$this->catalogBlock->pages_ids,
            ], $additionalData);
            $block = new Block_Form($blockData);
            $block->commit();
            return $block;
        }
    }


    public function createMaterials(array $pagesIds = [])
    {
        $result = [];
        $textRetriever = new FishYandexReferatsRetriever();
        $usersRetriever = new FishRandomUserRetriever();
        $goods = Material::getSet([
            'where' => "pid = " . (int)$this->catalogBlock->material_type,
            'orderBy' => "id ASC",
        ]);
        foreach ($goods as $product) {
            for ($i = 0; $i < 3; $i++) {
                $user = $usersRetriever->retrieve();
                $text = $textRetriever->retrieve();
                $text2 = $textRetriever->retrieve();
                $text3 = $textRetriever->retrieve();
                $item = new Material([
                    'pid' => (int)$this->materialType->id,
                    'vis' => 1,
                    'name' => $user['name']['first'] . ' '
                           .  $user['name']['last'],
                    'description' => $text['name'],
                    'sitemaps_priority' => 0.5,
                    'cats' => (array)$product->pages_ids,
                ]);
                $item->commit();
                $t = time() - 86400 * rand(1, 7);
                $item->fields['material']->addValue((int)$product->id);
                $item->fields['date']->addValue(date('Y-m-d', $t));
                $item->fields['phone']->addValue($user['phone']);
                $item->fields['email']->addValue($user['email']);
                $item->fields['rating']->addValue(rand(0, 5));
                $item->fields['advantages']->addValue($text2['name']);
                $item->fields['disadvantages']->addValue($text3['name']);
                $result[] = $item;
            }
        }
        return $result;
    }


    /**
     * Создает или находит тип материалов, соответствующий URN, и создает к нему
     * шаблон
     * @param string $name Наименование
     * @param string $urn URN
     * @param CMSWebmaster $webmaster Объект вебмастера
     * @param Block_Material $catalogBlock Блок каталога
     * @return self
     */
    public static function spawn(
        $name,
        $urn,
        CMSWebmaster $webmaster,
        Block_Material $catalogBlock = null
    ) {
        $newMaterialType = false;
        $materialType = Material_Type::importByURN($urn);
        if (!($materialType && $materialType->id)) {
            $materialType = new Material_Type([
                'name' => $name,
                'urn' => $urn,
                'global_type' => (int)static::$global,
            ]);
            $materialType->commit();
            $newMaterialType = true;
        }
        $materialTemplate = new static($materialType, $webmaster, $catalogBlock);
        if ($newMaterialType) {
            $fields = $materialTemplate->createFields();
        }
        return $materialTemplate;
    }


    public function create()
    {
        $form = $this->createForm();
        $widget = $this->createBlockSnippet();
        $formWidget = $this->createFormSnippet();

        $pagesIds = (array)$this->catalogBlock->pages_ids;
        $pageId = min($pagesIds);
        $page = new Page($pageId);

        $block = $this->createBlock($page, $widget);
        $formBlock = $this->createFormBlock($page, $form, $formWidget);
        $this->createMaterials();

        $this->catalogBlock->params .= '&' . static::FORM_BLOCK_PARAM . '='
            . (int)$formBlock->id
            . '&' . static::LIST_BLOCK_PARAM . '=' . (int)$block->id;
        $this->catalogBlock->commit();
        return null;
    }
}
