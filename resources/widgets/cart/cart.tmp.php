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
use RAAS\CMS\Form;
use RAAS\CMS\Material;
use RAAS\CMS\Package;
use RAAS\CMS\Page;
use RAAS\CMS\Snippet;

$cartData = [];
$cartData['count'] = (int)$Cart->count;
$cartData['sum'] = (float)$Cart->sum;
$cartData['items'] = [];
foreach ($Cart->items as $cartItem) {
    $item = new Material($cartItem->id);
    $cartData['items'][] = [
        'id' => $cartItem->id,
        'meta' => $cartItem->meta,
        'amount' => $cartItem->amount,
        'price' => $cartItem->realprice,
        'name' => $cartItem->name,
        'url' => $item->url,
        'image' => $item->visImages ? '/' . $item->visImages[0]->smallURL : null,
        'min' => $item->min ?: 1,
        'step' => $item->step ?: 1,
    ];
}

if ($_GET['AJAX']) {
    echo json_encode($cartData);
    exit;
} elseif ($epayWidget && ($epayWidget instanceof Snippet)) {
    eval('?' . '>' . $epayWidget->description);
} elseif ($success[(int)$Block->id]) { ?>
    <div class="notifications">
      <div class="alert alert-success">
        <?php echo sprintf(ORDER_SUCCESSFULLY_SENT, $Item->id)?>
      </div>
    </div>
<?php } else { ?>
    <script type="text/html" v-pre id="cart-item-template">
      <div class="cart-list__item">
        <div class="cart-item">
          <div class="cart-item__image">
            <a :href="item.url" v-if="item.image">
              <img :src="item.image" alt="">
            </a>
          </div>
          <div class="cart-item__title">
            <a :href="item.url">
              {{ item.name }}
            </a>
          </div>
          <div class="cart-item__price">
            {{ formatPrice(item.price) }} ₽
          </div>
          <div class="cart-item__amount-block">
            <a class="cart-item__decrement" v-on:click="item.amount = (parseInt(item.amount) || 1) - (parseInt(item.step) || 1); checkAmount();">–</a>
            <input type="number" class="cart-item__amount" autocomplete="off" :name="'amount[' + item.id + '_' + item.meta + ']'" :min="item.min" :step="item.step" v-model="item.amount" v-on:change="checkAmount();" />
            <a class="cart-item__increment" v-on:click="item.amount = (parseInt(item.amount) || 1) + (parseInt(item.step) || 1); checkAmount();">+</a>
          </div>
          <div class="cart-item__sum">
            {{ formatPrice(item.price * item.amount) }} ₽
          </div>
          <div class="cart-item__actions">
            <a class="cart-item__delete" v-on:click="$emit('delete', item)" title="<?php echo DELETE?>"></a>
          </div>
        </div>
      </div>
    </script>
    <script type="text/html" v-pre id="cart-list-template">
      <div class="cart-list">
        <div class="cart-list__header">
          <div class="cart-list__item cart-list__item_header">
            <div class="cart-item cart-item_header">
              <div class="cart-item__image">
                <?php echo IMAGE?>
              </div>
              <div class="cart-item__title">
                <?php echo NAME?>
              </div>
              <div class="cart-item__price">
                <?php echo PRICE?>
              </div>
              <div class="cart-item__amount-block">
                <?php echo AMOUNT?>
              </div>
              <div class="cart-item__sum">
                <?php echo SUM?>
              </div>
              <div class="cart-item__actions"></div>
            </div>
          </div>
        </div>
        <div class="cart-list__list">
          <cart-item v-for="item in items" :item="item" v-on:change="itemUpdate(item)" v-on:delete="$emit('delete', item)"></cart-item>
        </div>
        <div class="cart-list__summary">
          <div class="cart-list__item cart-list__item_summary">
            <div class="cart-item cart-item_summary">
              <div class="cart-item__title">
                <?php echo TOTAL_SUM?>:
              </div>
              <div class="cart-item__amount-block">
                {{ amount }}
              </div>
              <div class="cart-item__sum">
                 {{ formatPrice(sum) }} ₽
              </div>
              <div class="cart-item__actions">
                <a class="cart-item__delete" v-on:click="$emit('clear')"></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </script>
    <cart class="cart" inline-template :cart="cart">
      <div>
        <?php if ($localError) { ?>
            <div class="alert alert-danger">
              <ul>
                <?php foreach ((array)$localError as $key => $val) { ?>
                    <li><?php echo htmlspecialchars($val)?></li>
                <?php } ?>
              </ul>
            </div>
        <?php } ?>
        <form action="#feedback" method="post" enctype="multipart/form-data" v-if="items.length">
          <div class="cart__list">
            <cart-list :items="items" :cart="cart" @delete="requestItemDelete($event)" @clear="requestClear()"></cart-list>
          </div>
          <?php if ($Form->id) {
              foreach ($Form->fields as $fieldURN => $field) {
                  if (!$DATA[$fieldURN] &&
                      ($userVal = Controller_Frontend::i()->user->{$fieldURN})
                  ) {
                      $DATA[$fieldURN] = $userVal;
                  }
              } ?>
              <div class="cart__form">
                <div class="cart-form form-horizontal">
                  <?php include Package::i()->resourcesDir . '/form2.inc.php'?>
                  <div data-role="notifications" <?php echo ($success[(int)$Block->id] || $localError) ? '' : 'style="display: none"'?>>
                    <div class="alert alert-success" <?php echo ($success[(int)$Block->id]) ? '' : 'style="display: none"'?>>
                      <?php echo FEEDBACK_SUCCESSFULLY_SENT?>
                    </div>
                    <div class="alert alert-danger" <?php echo ($localError) ? '' : 'style="display: none"'?>>
                      <ul>
                        <?php foreach ((array)$localError as $key => $val) { ?>
                            <li><?php echo htmlspecialchars($val)?></li>
                        <?php } ?>
                      </ul>
                    </div>
                  </div>

                  <div data-role="feedback-form" <?php echo $success[(int)$Block->id] ? 'style="display: none"' : ''?>>
                    <p class="cart-form__required-fields">
                      <?php echo ASTERISK_MARKED_FIELDS_ARE_REQUIRED?>
                    </p>
                    <?php if ($Form->signature) { ?>
                        <input type="hidden" name="form_signature" value="<?php echo md5('form' . (int)$Form->id . (int)$Block->id)?>" />
                    <?php }
                    if ($Form->antispam == 'hidden' && $Form->antispam_field_name) { ?>
                        <textarea autocomplete="off" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" style="position: absolute; left: -9999px"><?php echo htmlspecialchars($DATA[$Form->antispam_field_name])?></textarea>
                    <?php }
                    foreach ($Form->fields as $fieldURN => $field) {
                        if ($fieldURN == 'agree') { ?>
                            <div class="form-group">
                              <div class="col-sm-9 col-sm-offset-3 col-md-offset-2">
                                <label>
                                  <?php $getField($field, $DATA);?>
                                  <a href="/privacy/" target="_blank">
                                    <?php echo htmlspecialchars($field->name)?>
                                  </a>
                                </label>
                              </div>
                            </div>
                        <?php } elseif ($field->datatype == 'checkbox') { ?>
                            <div class="form-group">
                              <div class="col-sm-9 col-sm-offset-3 col-md-offset-2">
                                <label>
                                  <?php $getField($field, $DATA);?>
                                  <?php echo htmlspecialchars($field->name . ($field->required ? '*' : ''))?>
                                </label>
                              </div>
                            </div>
                        <?php } else { ?>
                            <div class="form-group">
                              <label<?php echo !$field->multiple ? ' for="' . htmlspecialchars($fieldURN . $field->id . '_' . $Block->id) . '"' : ''?> class="control-label col-sm-3 col-md-2">
                                <?php echo htmlspecialchars($field->name . ($field->required ? '*' : ''))?>
                              </label>
                              <div class="col-sm-9 col-md-4">
                                <?php $getField($field, $DATA);?>
                              </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                    <?php if ($Form->antispam == 'captcha' && $Form->antispam_field_name) { ?>
                        <div class="form-group">
                          <label for="<?php echo htmlspecialchars($Form->antispam_field_name)?>" class="control-label col-sm-3 col-md-2"><?php echo CAPTCHA?></label>
                          <div class="col-sm-9 col-md-4">
                            <img src="/assets/kcaptcha/?<?php echo session_name() . '=' . session_id()?>" /><br />
                            <input type="text" autocomplete="off" name="<?php echo htmlspecialchars($Form->antispam_field_name)?>" />
                          </div>
                        </div>
                    <?php } ?>
                    <?php if ($Block->EPay_Interface->id && !$Form->fields['epay']) { ?>
                        <div class="form-group">
                          <label for="name" class="control-label col-sm-3 col-md-2"><?php echo PAYMENT_METHOD?></label>
                          <div class="col-sm-9 col-md-4">
                            <label>
                              <input type="radio" name="epay" value="0" <?php echo !$DATA['epay'] ? 'checked="checked"' : ''?> />
                              <?php echo PAY_ON_DELIVERY?>
                            </label>
                            <label>
                              <input type="radio" name="epay" value="1" <?php echo $DATA['epay'] ? 'checked="checked"' : ''?> />
                              <?php echo PAY_BY_EPAY?>
                            </label>
                          </div>
                        </div>
                    <?php } ?>
                    <div class="cart-form__controls col-sm-offset-3 col-md-offset-2">
                      <button class="cart-form__submit btn btn-primary" type="submit">
                        <?php echo SEND?>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
          <?php } ?>
        </form>
        <div class="cart__empty" v-if="!items.length">
          <?php echo htmlspecialchars(YOUR_CART_IS_EMPTY)?>
        </div>
      </div>
    </cart>
    <script>
    window.raasShopCartData = {
        items: <?php echo json_encode($cartData['items'])?>,
    };
    </script>
    <?php Package::i()->requestJS('/js/cart.js')?>
<?php } ?>
