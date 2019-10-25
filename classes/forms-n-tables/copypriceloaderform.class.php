<?php
/**
 * Форма копирования загрузчика прайсов
 */
namespace RAAS\CMS\Shop;

use RAAS\HTMLElement;
use RAAS\Field as RAASField;
use RAAS\CMS\Material_Type;

/**
 * Класс формы копирования загрузчика прайсов
 */
class CopyPriceLoaderForm extends EditPriceLoaderForm
{
    public function __construct(array $params = [])
    {
        $params['selfUrl'] = Sub_Dev::i()->url . '&action=edit_priceloader&id=%d';
        $params['newUrl'] = Sub_Dev::i()->url . '&action=edit_priceloader';
        $params['caption'] = $this->view->_('COPY_PRICELOADER');
        parent::__construct($params);
        $this->meta['Original'] = $original = $params['Original'];
        $content = [];
        $content['fields'] = [
            [
                'value' => 'urn',
                'caption' => $this->view->_('URN')
            ],
            [
                'value' => 'vis',
                'caption' => $this->view->_('VISIBILITY')
            ],
            [
                'value' => 'name',
                'caption' => $this->view->_('NAME')
            ],
            [
                'value' => 'description',
                'caption' => $this->view->_('DESCRIPTION')
            ],
            [
                'value' => 'meta_title',
                'caption' => $this->view->_('META_TITLE')
            ],
            [
                'value' => 'meta_description',
                'caption' => $this->view->_('META_DESCRIPTION')
            ],
            [
                'value' => 'meta_keywords',
                'caption' => $this->view->_('META_KEYWORDS')
            ],
            [
                'value' => 'priority',
                'caption' => $this->view->_('PRIORITY')
            ],
        ];
        if ($item->id) {
            $Material_Type = $item->Material_Type;
        } elseif (isset($_POST['mtype'])) {
            $Material_Type = new Material_Type($_POST['mtype']);
        } elseif ($original->id) {
            $Material_Type = $original->Material_Type;
        } else {
            $Material_Type = $content['material_types']['Set'][0];
        }
        foreach ((array)$Material_Type->fields as $row) {
            $content['fields'][] = [
                'value' => (int)$row->id,
                'caption' => $row->name
            ];
        }
        $this->meta['CONTENT']['fields'] = $content['fields'];
    }


    public function importDefault()
    {
        $DATA = [];
        if (($_SERVER['REQUEST_METHOD'] == 'POST') || ($this->Item && $this->Item->id)) {
            return parent::importDefault();
        } else {
            $originalItem = $this->Item;
            $this->Item = $this->meta['Original'];
            $DATA = parent::importDefault();
            $DATA['name'] = $originalItem->name;
            $DATA['urn'] = $originalItem->urn;
            $this->Item = $originalItem;
            $DATA['column_id'] = array_fill(0, count($DATA['column_fid']), 0);
            return $DATA;
        }
        return $DATA;
    }
}
