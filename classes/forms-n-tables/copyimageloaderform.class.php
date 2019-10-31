<?php
/**
 * Форма копирования загрузчика изображений
 */
namespace RAAS\CMS\Shop;

/**
 * Класс формы копирования загрузчика изображений
 */
class CopyImageLoaderForm extends EditImageLoaderForm
{
    public function __construct(array $params = [])
    {
        $params['selfUrl'] = Sub_Dev::i()->url . '&action=edit_imageloader&id=%d';
        $params['newUrl'] = Sub_Dev::i()->url . '&action=edit_imageloader';
        $params['caption'] = $this->view->_('COPY_IMAGELOADER');
        parent::__construct($params);
        $item = isset($params['Item']) ? $params['Item'] : null;
        foreach ($this->children as $row) {
            if ($item->{$row->name}) {
                $row->default = $item->{$row->name};
            }
        }
    }
}
