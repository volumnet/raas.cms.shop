<?php
$_RAASForm_Control = function(\RAAS\Field $Field) use (&$_RAASForm_Attrs, &$_RAASForm_Options, &$_RAASForm_Checkbox, &$_RAASForm_Control) {
    $Item = $Field->Form->Item;
    switch ($Field->name) {
        case 'status_id':
            echo '<strong>' . htmlspecialchars($Item->status->id ? $Item->status->name : \RAAS\CMS\Shop\Module::i()->view->_('ORDER_STATUS_NEW')) . '</strong>';
            break;
        case 'paid':
            if ($Item->paid) {
                echo '<strong class="text-success">' . \RAAS\CMS\Shop\Module::i()->view->_('PAYMENT_PAID_CONFIRMED');
            } else {
                echo '<strong class="text-error">' . \RAAS\CMS\Shop\Module::i()->view->_('PAYMENT_NOT_PAID');
            }
            echo '</strong>';
            break;
        case 'pid':
            if (\RAAS\Application::i()->user->root) { 
                echo '<a href="' . \RAAS\CMS\Shop\Sub_Dev::i()->url . '&action=edit_cart_type&id=' . (int)$Item->pid . '">' . htmlspecialchars($Item->parent->name) . '</a>';
            } else {
                echo '<a href="' . \RAAS\CMS\Shop\Sub_Orders::i()->url . '&id=' . (int)$Item->pid . '">' . htmlspecialchars($Item->parent->name) . '</a>';
            }
            break;
    }
};