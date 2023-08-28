<?php
/**
 * Виджет блока корзины
 * @param Page $Page Текущая страница
 * @param Block_Cart $Block Текущий блок
 * @param Snippet|null $epayWidget Виджет оплаты
 * @param Cart $Cart Текущая корзина
 * @param Form $Form Форма заказа
 */
namespace RAAS\CMS\Shop;

use SOME\Text;
use RAAS\Application;
use RAAS\AssetManager;
use RAAS\CMS\FieldArrayFormatter;
use RAAS\CMS\Block;
use RAAS\CMS\Form;
use RAAS\CMS\FormArrayFormatter;
use RAAS\CMS\FormFieldRenderer;
use RAAS\CMS\FormRenderer;
use RAAS\CMS\Material;
use RAAS\CMS\Material_Type;
use RAAS\CMS\MaterialTypeRecursiveCache;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

$cartData = [];
$cartData['formData'] = (object)$DATA;
if (!$cartData['formData']->delivery) {
    $cartData['formData']->delivery = '0';
}
foreach ($Form->fields as $fieldURN => $field) {
    if (!isset($cartData['formData']->$fieldURN)) {
        $defval = $field->defval;
        if ($field->multiple) {
            $cartData['formData']->$fieldURN = $defval ? [$defval] : [];
        } else {
            $cartData['formData']->$fieldURN = $defval ?: '';
        }
    }
}

if (($Page->mime == 'application/json') || (int)($_GET['AJAX'] ?? 0)) {
    $cartData['count'] = (int)$Cart->count;
    $cartData['additional'] = (array)$additional ?: null;
    if ($cartData['additional']['items']) {
        foreach ((array)$cartData['additional']['items'] as $i => $cartItem) {
            $cartItemFormatter = new CartItemArrayFormatter($cartItem);
            $cartData['additional']['items'][$i] = $cartItemFormatter->format([
                'type' => trim($cartItem->additional['type'])
            ]);
        }
    }
    if ($minOrderSum) {
        $cartData['additional']['minOrderSum'] = $minOrderSum;
    }
    $cartData['sum'] = $cartData['rollup'] = (float)$Cart->sum;
    if ($additional['items']) {
        foreach ((array)$additional['items'] as $additionalItem) {
            $cartData['rollup'] += (float)$additionalItem->realprice * (int)$additionalItem->amount;
        }
    }
    $cartData['items'] = [];
    $cartData['localError'] = (array)$localError;
    // 2022-07-12, AVS: добавил переменную для редиректа при онлайн-оплате
    if (isset($redirectUrl) && $redirectUrl) {
        $cartData['redirectUrl'] = $redirectUrl;
    }
    $cartData['proceed'] = ($_SERVER['REQUEST_METHOD'] == 'POST');
    foreach ($Cart->items as $i => $cartItem) {
        $cartItemFormatter = new CartItemArrayFormatter($cartItem);
        $cartItemData = $cartItemFormatter->format([
            'article',
            'url',
            'eCommerce' => ECommerce::getProduct($cartItem->material, $i),
        ]);
        $cartData['items'][] = $cartItemData;
    }
    $result = $cartData;
    if ($success[(int)$Block->id]) {
        $result['success'] = true;
        $result['orderId'] = $Item->id;
    }
    if ($localError) {
        $result['localError'] = $localError;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
} elseif ($epayWidget && ($epayWidget instanceof Snippet)) {
    eval('?' . '>' . $epayWidget->description);
} elseif ($success[(int)$Block->id]) {
    $eCommerceProducts = [];
    $catalogMaterialType = Material_Type::importByURN('catalog');
    foreach ((array)$Item->items as $item) {
        if (in_array(
            $item->pid,
            MaterialTypeRecursiveCache::i()->getSelfAndChildrenIds($catalogMaterialType->id)
        )) {
            $eCommerceProducts[] = ECommerce::getProduct($item);
        }
    }
    ?>
    <div class="notifications">
      <div class="alert alert-success">
        <?php echo sprintf(ORDER_SUCCESSFULLY_SENT, $Item->id)?>
      </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        window.eCommerce.pushToDataLayer(
            'purchase',
            <?php echo json_encode($eCommerceProducts)?>,
            <?php echo (int)$Item->id?>
        );
    });
    </script>
<?php } else {
    $formArrayFormatter = new FormArrayFormatter($Form);
    $formArr = $formArrayFormatter->format(
        ['signature' => function ($form) use ($Block) {
            return $form->getSignature($Block);
        }],
        [
            'htmlId' => function ($field) use ($Block) {
                return $field->getHTMLId($Block);
            },
        ]
    );
    $formArr['fields']['city']['datatype'] = 'city';
    $formArr['fields']['city']['stdSource'] = array_values($cities);
    ?>
    <cart class="cart" :cart="cart" :block-id="<?php echo (int)$Block->id?>" id="cart" :form="<?php echo htmlspecialchars(json_encode($formArr))?>" :initial-form-data="<?php echo htmlspecialchars(json_encode((object)$cartData['formData']))?>">
      <div class="cart__loading">
        <?php echo CART_IS_LOADING?>
      </div>
    </cart>
    <?php
    AssetManager::requestCSS('/css/cart.css');
    AssetManager::requestJS('/js/cart.js');
}
