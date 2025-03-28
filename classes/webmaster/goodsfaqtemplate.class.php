<?php
/**
 * Шаблон типа материалов "Вопрос-ответ для товаров"
 */
namespace RAAS\CMS\Shop;

use RAAS\Application;
use RAAS\Attachment;
use RAAS\CMS\Block_Material;
use RAAS\CMS\FishRandomUserRetriever;
use RAAS\CMS\FishYandexReferatsRetriever;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Field;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

/**
 * Класс шаблона типа материалов "Вопрос-ответ для товаров"
 */
class GoodsFAQTemplate extends GoodsCommentsTemplate
{
    const FORM_BLOCK_PARAM = 'faqFormBlock';

    const LIST_BLOCK_PARAM = 'faqListBlock';

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

        $nameField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('FULL_NAME'),
            'urn' => 'full_name',
            'datatype' => 'text',
        ]);
        $nameField->commit();

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

        $answerDateField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ANSWER_DATE'),
            'urn' => 'answer_date',
            'datatype' => 'date',
        ]);
        $answerDateField->commit();

        $answerNameField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ANSWER_NAME'),
            'urn' => 'answer_name',
            'datatype' => 'text',
        ]);
        $answerNameField->commit();

        $answerGenderField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ANSWER_GENDER'),
            'urn' => 'answer_gender',
            'datatype' => 'select',
            'source_type' => 'ini',
            'source' => '0 = "' . View_Web::i()->_('FEMALE') . '"' . "\n"
                     .  '1 = "' . View_Web::i()->_('MALE') . '"'
        ]);
        $answerGenderField->commit();

        $answerImageField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ANSWER_IMAGE'),
            'urn' => 'answer_image',
            'datatype' => 'image', 'show_in_table' => 0,
        ]);
        $answerImageField->commit();

        $answerField = new Material_Field([
            'pid' => $this->materialType->id,
            'vis' => 1,
            'name' => View_Web::i()->_('ANSWER'),
            'urn' => 'answer',
            'datatype' => 'htmlarea',
        ]);
        $answerField->commit();

        return [
            $materialField->urn => $materialField,
            $dateField->urn => $dateField,
            $nameField->urn => $nameField,
            $phoneField->urn => $phoneField,
            $emailField->urn => $emailField,
            $answerDateField->urn => $answerDateField,
            $answerNameField->urn => $answerNameField,
            $answerGenderField->urn => $answerGenderField,
            $answerImageField->urn => $answerImageField,
            $answerField->urn => $answerField,
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
                    'urn' => 'full_name',
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
                    'vis' => 0,
                    'name' => View_Web::i()->_('YOUR_PHOTO'),
                    'urn' => 'image',
                    'datatype' => 'image',
                    'show_in_table' => 0,
                ],
                [
                    'vis' => 1,
                    'name' => View_Web::i()->_('QUESTION_TEXT'),
                    'urn' => '_name_',
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
        $filename = Module::i()->resourcesDir
                  . '/widgets/materials/faq/goods_faq.tmp.php';
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
                  . '/widgets/materials/faq/goods_faq_form.tmp.php';
        $snippet = $this->webmaster->createSnippet(
            $this->materialType->urn . '_form',
            View_Web::i()->_('GOODS_FAQ_FORM'),
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
        ?Snippet $widget = null,
        array $additionalData = []
    ) {
        $additionalData = array_merge(
            [
                'interface_classname' => GoodsCommentsInterface::class,
                'name' => View_Web::i()->_('FAQ'),
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
                $answer = $usersRetriever->retrieve();
                $text = $textRetriever->retrieve();
                $item = new Material([
                    'pid' => (int)$this->materialType->id,
                    'vis' => 1,
                    'name' => $text['name'],
                    'description' => '',
                    'sitemaps_priority' => 0.5,
                    'cats' => (array)$product->pages_ids,
                ]);
                $item->commit();
                $t = time() - 86400 * rand(1, 7);
                $t1 = $t + rand(0, 86400);
                $item->fields['material']->addValue((int)$product->id);
                $item->fields['date']->addValue(date('Y-m-d', $t));
                $item->fields['full_name']->addValue(
                    $user['name']['first'] . ' ' .  $user['name']['last']
                );
                $item->fields['phone']->addValue($user['phone']);
                $item->fields['email']->addValue($user['email']);
                $item->fields['answer_date']->addValue(date('Y-m-d', $t1));
                $item->fields['answer_name']->addValue(
                    $answer['name']['first'] . ' ' . $answer['name']['last']
                );
                $item->fields['answer_gender']->addValue(
                    (int)($answer['gender'] == 'male')
                );
                $item->fields['answer']->addValue($text['text']);
                $att = Attachment::createFromFile(
                    $answer['pic']['filepath'],
                    $this->materialType->fields['answer_image']
                );
                $item->fields['answer_image']->addValue(json_encode([
                    'vis' => 1,
                    'name' => '',
                    'description' => '',
                    'attachment' => (int)$att->id
                ]));
                $result[] = $item;
            }
        }
        return $result;
    }
}
