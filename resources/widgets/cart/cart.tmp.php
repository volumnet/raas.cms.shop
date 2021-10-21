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
use RAAS\CMS\FieldArrayFormatter;
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
$cartData['sum'] = $cartData['rollup'] = (float)$Cart->sum;
if ($additional['discount']['price']) {
    $cartData['rollup'] += (float)$additional['discount']['price'];
}
if ($additional['delivery']['price']) {
    $cartData['rollup'] += (float)$additional['delivery']['price'];
}
$cartData['formData'] = (object)$_POST;
if (!$cartData['formData']->delivery) {
    $cartData['formData']->delivery = '0';
}
foreach (['region', 'city', 'pickup_point'] as $key) {
    if (!$cartData['formData']->$key) {
        $cartData['formData']->$key = '';
    }
}
$cartData['items'] = [];
$cartData['localError'] = (array)$localError;
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

if ($_GET['AJAX'] || ($_POST['AJAX'] == $Block->id)) {
    $result = $cartData;
    if ($success[(int)$Block->id]) {
        $result['success'] = 1;
        $result['orderId'] = $Item->id;
    }
    if ($localError) {
        $result['localError'] = $localError;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
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
        ],
    );
    // var_dump($formArr); exit;
    ?>
    <cart class="cart" :cart="cart" :block-id="<?php echo (int)$Block->id?>" id="cart" :form="<?php echo htmlspecialchars(json_encode($formArr))?>" :initial-form-data="<?php echo htmlspecialchars(json_encode((object)$DATA))?>">
      <div v-if="false" class="cart__loading">
        <?php echo CART_IS_LOADING?>
      </div>
    </cart>
    <?php
    Package::i()->requestCSS('/css/cart.css');
    Package::i()->requestJS('/js/cart.js');
}
