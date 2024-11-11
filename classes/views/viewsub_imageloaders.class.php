<?php
namespace RAAS\CMS\Shop;

class ViewSub_Imageloaders extends \RAAS\Abstract_Sub_View
{
    protected static $instance;
    
    /**
     * Отображение параметров загрузки загрузчика
     * @param array $in Входные данные
     */
    public function upload(array $in = [])
    {
        $this->assignVars($in);
        $this->title = $in['Form']->caption;
        $this->path = [[
            'name' => $this->_('IMAGELOADERS'),
            'href' => $this->url,
        ]];
        $this->contextmenu = $this->getLoaderContextMenu($in['loader']);
        $this->template = $in['Form']->template;
    }


    /**
     * Отображение списка загрузчиков прайсов
     * @param array $in Входные данные
     */
    public function showlist(array $in = [])
    {
        $in['Table'] = new ImageLoadersSelectTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->template = $in['Table']->template;
    }


    /**
     * Получает контекстное меню загрузчика
     * @param PriceLoader $loader Загрузчик
     * @return array
     */
    public function getLoaderContextMenu(ImageLoader $loader): array
    {
        $arr = [];
        if (($this->id != $loader->id) || ($this->action == 'download')) {
            $arr[] = [
                'href' => $this->url . '&id=' . (int)$loader->id,
                'name' => $this->_('UPLOAD'),
                'icon' => 'upload',
            ];
        }
        if ($this->action != 'download') {
            $arr[] = [
                'href' => $this->url . '&action=download&id=' . (int)$loader->id,
                'name' => $this->_('DOWNLOAD'),
                'icon' => 'download',
            ];
        }
        return $arr;
    }
}
