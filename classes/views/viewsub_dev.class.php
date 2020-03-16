<?php
namespace RAAS\CMS\Shop;

use RAAS\Column;
use RAAS\Table;
use RAAS\Row;
use RAAS\CMS\ViewSub_Dev as CMSViewSubDev;
use RAAS\Abstract_Sub_View as RAASAbstractSubView;

class ViewSub_Dev extends RAASAbstractSubView
{
    protected static $instance;

    public function devMenu()
    {
        $submenu = [];
        $submenu[] = [
            'href' => $this->url . '&action=cart_types',
            'name' => $this->_('CART_TYPES'),
            'active' => in_array(
                $this->action,
                ['cart_types', 'edit_cart_type']
            )
        ];
        $submenu[] = [
            'href' => $this->url . '&action=order_statuses',
            'name' => $this->_('ORDER_STATUSES'),
            'active' => in_array(
                $this->action,
                ['order_statuses', 'edit_order_status']
            ),
        ];
        $submenu[] = [
            'href' => $this->url . '&action=priceloaders',
            'name' => $this->_('PRICELOADERS'),
            'active' => in_array(
                $this->action,
                ['priceloaders', 'edit_priceloader', 'copy_priceloader']
            )
        ];
        $submenu[] = [
            'href' => $this->url . '&action=imageloaders',
            'name' => $this->_('IMAGELOADERS'),
            'active' => in_array(
                $this->action,
                ['imageloaders', 'edit_imageloader', 'copy_imageloader']
            )
        ];
        return $submenu;
    }


    public function cart_types(array $in = [])
    {
        $in['Table'] = new CartTypesTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->path[] = [
            'name' => $this->_('DEVELOPMENT'),
            'href' => CMSViewSubDev::i()->url
        ];
        $this->contextmenu = [[
            'name' => $this->_('ADD_CART_TYPE'),
            'href' => $this->url . '&action=edit_cart_type',
            'icon' => 'plus'
        ]];
        $this->template = $in['Table']->template;
    }


    public function order_statuses(array $in = [])
    {
        $in['Table'] = new OrdersStatusesTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->path[] = [
            'name' => $this->_('DEVELOPMENT'),
            'href' => $this->url
        ];
        $this->contextmenu = [[
            'name' => $this->_('ADD_ORDER_STATUS'),
            'href' => $this->url . '&action=edit_order_status',
            'icon' => 'plus'
        ]];
        $this->template = $in['Table']->template;
    }


    public function priceloaders(array $in = [])
    {
        $in['Table'] = new PriceLoadersTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->path[] = [
            'name' => $this->_('DEVELOPMENT'),
            'href' => CMSViewSubDev::i()->url
        ];
        $this->contextmenu = [[
            'name' => $this->_('ADD_PRICELOADER'),
            'href' => $this->url . '&action=edit_priceloader',
            'icon' => 'plus'
        ]];
        $this->template = $in['Table']->template;
    }


    public function imageloaders(array $in = [])
    {
        $in['Table'] = new ImageLoadersTable($in);
        $this->assignVars($in);
        $this->title = $in['Table']->caption;
        $this->path[] = [
            'name' => $this->_('DEVELOPMENT'),
            'href' => CMSViewSubDev::i()->url
        ];
        $this->contextmenu = [[
            'name' => $this->_('ADD_IMAGELOADER'),
            'href' => $this->url . '&action=edit_imageloader',
            'icon' => 'plus'
        ]];
        $this->template = $in['Table']->template;
        // return $this->stdDictionaryShowlist($in, 'IMAGELOADERS', 'edit_imageloader', 'getImageLoaderContextMenu', 'NO_IMAGELOADERS_FOUND', 'ADD_IMAGELOADER');
    }


    public function edit_cart_type(array $in = [])
    {
        $this->js[] = $this->publicURL . '/dev_edit_cart_type.js';
        $this->subtitle = $this->getCartTypeSubtitle($in['Item']);
        return $this->stdDictionaryEdit(
            $in,
            'CART_TYPES',
            'cart_types',
            'getCartTypeContextMenu'
        );
    }


    public function edit_order_status(array $in = [])
    {
        $this->subtitle = $this->getOrderStatusSubtitle($in['Item']);
        return $this->stdDictionaryEdit(
            $in,
            'ORDER_STATUSES',
            'order_statuses',
            'getOrderStatusContextMenu'
        );
    }


    public function edit_priceloader(array $in = [])
    {
        $this->js[] = $this->publicURL . '/dev_edit_priceloader.js';
        $this->subtitle = $this->getPriceLoaderSubtitle($in['Item']);
        return $this->stdDictionaryEdit(
            $in,
            'PRICELOADERS',
            'priceloaders',
            'getPriceLoaderContextMenu'
        );
    }


    public function edit_imageloader(array $in = [])
    {
        $this->js[] = $this->publicURL . '/dev_edit_imageloader.js';
        $this->subtitle = $this->getImageLoaderSubtitle($in['Item']);
        return $this->stdDictionaryEdit(
            $in,
            'IMAGELOADERS',
            'imageloaders',
            'getImageLoaderContextMenu'
        );
    }


    public function getCartTypeContextMenu(Cart_Type $cartType)
    {
        return $this->stdView->stdContextMenu(
            $cartType,
            0,
            0,
            'edit_cart_type',
            'cart_types',
            'delete_cart_type'
        );
    }


    public function getAllCartTypesContextMenu()
    {
        $arr = [];
        $arr[] = [
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete_cart_type&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        ];
        return $arr;
    }


    public function getOrderStatusContextMenu(Order_Status $orderStatus)
    {
        return $this->stdView->stdContextMenu(
            $orderStatus,
            0,
            0,
            'edit_order_status',
            'order_statuses',
            'delete_order_status'
        );
    }


    public function getAllOrderStatusesContextMenu()
    {
        $arr = [];
        $arr[] = [
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete_order_status&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        ];
        return $arr;
    }


    public function getPriceLoaderContextMenu(PriceLoader $priceLoader)
    {
        $arr = [];
        if ($priceLoader->id) {
            $edit = ($this->action == 'edit_priceloader');
            $showlist = ($this->action == 'priceloaders');
            if (!$edit) {
                $arr[] = [
                    'href' => $this->url . '&action=edit_priceloader&id='
                           .  (int)$priceLoader->id,
                    'name' => $this->_('EDIT'),
                    'icon' => 'edit',
                ];
            }
            $arr[] = [
                'href' => $this->url . '&action=copy_priceloader&id='
                       .  (int)$priceLoader->id,
                'name' => $this->_('COPY'),
                'icon' => 'tags',
            ];
            $arr[] = [
                'href' => $this->url . '&action=delete_priceloader&id='
                       .  (int)$priceLoader->id . ($showlist ? '&back=1' : ''),
                'name' => $this->_('DELETE'),
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' .
                             $this->_('DELETE_TEXT') .
                             '\')',

            ];
        }
        return $arr;
    }


    public function getAllPriceLoadersContextMenu()
    {
        $arr = [];
        $arr[] = [
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete_priceloader&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        ];
        return $arr;
    }


    public function getImageLoaderContextMenu(ImageLoader $imageLoader)
    {
        $arr = [];
        if ($imageLoader->id) {
            $edit = ($this->action == 'edit_imageloader');
            $showlist = ($this->action == 'imageloaders');
            if (!$edit) {
                $arr[] = [
                    'href' => $this->url . '&action=edit_imageloader&id='
                           .  (int)$imageLoader->id,
                    'name' => $this->_('EDIT'),
                    'icon' => 'edit',
                ];
            }
            $arr[] = [
                'href' => $this->url . '&action=copy_imageloader&id='
                       .  (int)$imageLoader->id,
                'name' => $this->_('COPY'),
                'icon' => 'tags',
            ];
            $arr[] = [
                'href' => $this->url . '&action=delete_imageloader&id='
                       .  (int)$imageLoader->id . ($showlist ? '&back=1' : ''),
                'name' => $this->_('DELETE'),
                'icon' => 'remove',
                'onclick' => 'return confirm(\'' .
                             $this->_('DELETE_TEXT') .
                             '\')',

            ];
        }
        return $arr;
    }


    public function getAllImageLoadersContextMenu()
    {
        $arr = [];
        $arr[] = [
            'name' => $this->_('DELETE'),
            'href' => $this->url . '&action=delete_imageloader&back=1',
            'icon' => 'remove',
            'onclick' => 'return confirm(\'' . $this->_('DELETE_MULTIPLE_TEXT') . '\')'
        ];
        return $arr;
    }


    private function stdDictionaryEdit(
        array $in,
        $title,
        $showListAction,
        $contextMenuName
    ) {
        $this->path[] = [
            'name' => $this->_('DEVELOPMENT'),
            'href' => CMSViewSubDev::i()->url
        ];
        $this->path[] = [
            'name' => $this->_($title),
            'href' => $this->url . '&action=' . $showListAction
        ];
        $this->stdView->stdEdit($in, $contextMenuName);
    }


    /**
     * Получает подзаголовок типа корзины
     * @param Cart_Type $cartType Тип корзины для получения
     * @return string HTML-код подзаголовка
     */
    public function getCartTypeSubtitle(Cart_Type $cartType)
    {
        $subtitleArr = [];
        if ($cartType->id) {
            $subtitleArr[] = $this->_('ID') . ': ' . (int)$cartType->id;
            return implode('; ', $subtitleArr);
        }
        return '';
    }


    /**
     * Получает подзаголовок статуса заказов
     * @param Order_Status $orderStatus Статус заказов для получения
     * @return string HTML-код подзаголовка
     */
    public function getOrderStatusSubtitle(Order_Status $orderStatus)
    {
        $subtitleArr = [];
        if ($orderStatus->id) {
            $subtitleArr[] = $this->_('ID') . ': ' . (int)$orderStatus->id;
            return implode('; ', $subtitleArr);
        }
        return '';
    }


    /**
     * Получает подзаголовок загрузчика прайсов
     * @param PriceLoader $priceLoader Загрузчик прайсов для получения
     * @return string HTML-код подзаголовка
     */
    public function getPriceLoaderSubtitle(PriceLoader $priceLoader)
    {
        $subtitleArr = [];
        if ($priceLoader->id) {
            $subtitleArr[] = $this->_('ID') . ': ' . (int)$priceLoader->id;
            return implode('; ', $subtitleArr);
        }
        return '';
    }


    /**
     * Получает подзаголовок загрузчика изображений
     * @param ImageLoader $imageLoader Загрузчик изображений для получения
     * @return string HTML-код подзаголовка
     */
    public function getImageLoaderSubtitle(ImageLoader $imageLoader)
    {
        $subtitleArr = [];
        if ($imageLoader->id) {
            $subtitleArr[] = $this->_('ID') . ': ' . (int)$imageLoader->id;
            return implode('; ', $subtitleArr);
        }
        return '';
    }
}
