<?php
/**
 * Блок корзины
 */
declare(strict_types=1);

namespace RAAS\CMS\Shop;

use RAAS\User as RAASUser;
use RAAS\CMS\Block;
use RAAS\CMS\Snippet;

class Block_Cart extends Block
{
    protected static $tablename2 = 'cms_shop_blocks_cart';

    protected static $references = [
        'author' => [
            'FK' => 'author_id',
            'classname' => RAASUser::class,
            'cascade' => false,
        ],
        'editor' => [
            'FK' => 'editor_id',
            'classname' => RAASUser::class,
            'cascade' => false,
        ],
        'Cart_Type' => [
            'FK' => 'cart_type',
            'classname' => Cart_Type::class,
            'cascade' => true,
        ],
        'EPay_Interface' => [
            'FK' => 'epay_interface_id',
            'classname' => Snippet::class,
            'cascade' => false,
        ],
    ];

    public function commit()
    {
        if (!$this->name) {
            $this->name = Module::i()->view->_('CART');
        }
        parent::commit();
    }


    public function getAddData(): array
    {
        return [
            'id' => (int)$this->id,
            'cart_type' => (int)$this->cart_type,
            'epay_interface_id' => (int)$this->epay_interface_id,
            'epay_login' => trim((string)$this->epay_login),
            'epay_pass1' => trim((string)$this->epay_pass1),
            'epay_pass2' => trim((string)$this->epay_pass2),
            'epay_test' => (int)$this->epay_test,
            'epay_currency' => trim((string)$this->epay_currency),
        ];
    }
}
