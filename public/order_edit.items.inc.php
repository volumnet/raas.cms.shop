<?php
namespace RAAS\CMS\Shop;

use RAAS\FieldSet;
use RAAS\Application;
use RAAS\CMS\Material;

$_RAASForm_FieldSet = function (FieldSet $fieldSet) {
    $item = $fieldSet->Form->Item;
    $allContextMenu = $fieldSet->Form->meta['allContextMenu'];
    $DATA = $fieldSet->Form->DATA;
    $repoData = [];
    foreach ((array)($DATA['material'] ?? []) as $i => $temp) {
        $repoRow = [
            'material' => Controller_Ajax::i()->formatMaterial(new Material($DATA['material'][$i] ?? null), $item->parent),
            'name' => $DATA['material_name'][$i] ?? '',
            'meta' => $DATA['meta'][$i] ?? '',
            'realprice' => $DATA['realprice'][$i] ?? '',
            'amount' => $DATA['amount'][$i] ?? '',
        ];
        $repoData[] = $repoRow;
    }
    ?>
    <cms-shop-edit-order-items
      :model-value="<?php echo htmlspecialchars(json_encode($repoData))?>"
      :cart-type-id="<?php echo (int)$fieldSet->meta['Cart_Type']->id?>"
      :menu="<?php echo htmlspecialchars(json_encode(getMenu((array)$allContextMenu)))?>"
    ></cms-shop-edit-order-items>
    <?php
};
