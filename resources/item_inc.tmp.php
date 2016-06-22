<?php
$formatPrice = function($price) {
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
};

$showItem = function($row) use ($formatPrice)
{
    ?>
    <div class="article">
      <div class="article__title"><a href="<?php echo $row->url?>"><?php echo htmlspecialchars($row->name)?></a></div>
      <?php if ($row->article) { ?>
          <div class="article__article"><?php echo ARTICLE_SHORT?> <a href="<?php echo $row->url?>"><?php echo htmlspecialchars($row->article)?></a></div>
      <?php } ?>
      <a href="<?php echo $row->url?>" class="article__image<?php echo !$row->visImages ? ' article__image_nophoto' : ''?>">
        <?php if ($row->visImages) { ?>
            <img src="/<?php echo htmlspecialchars(addslashes($row->visImages[0]->tnURL))?>" />
        <?php } ?>
      </a>
      <form action="/cart/" class="article__controls" data-role="add-to-cart-form" data-id="<?php echo (int)$row->id?>" data-price="<?php echo (int)$row->price?>">
        <?php if ($row->available) { ?>
            <input type="hidden" name="action" value="add" />
            <input type="hidden" name="id" value="<?php echo (int)$row->id?>" />
            <input type="hidden" name="back" value="1" />
            <?php /* <input type="hidden" name="amount" value="1" /> */?>
        <?php } ?>
        <div class="article__text">
          <div class="article__price<?php echo ($row->price_old && ($row->price_old != $row->price)) ? ' article__price_new' : ''?>" data-price="<?php echo (float)$row->price?>">
            <?php if ($row->price_old && ($row->price_old != $row->price)) { ?>
                <span class="article__price__old"><?php echo $formatPrice((float)$row->price_old)?></span>
            <?php } ?>
            <span data-role="price-container">
              <?php echo $formatPrice((float)$row->price)?>
            </span>
            <i class="fa fa-rub"></i>
          </div>
          <div class="article__available"><?php echo $row->available ? '<span class="text-success">В наличии</span>' : '<span class="text-danger">Под заказ</span>'?></div>
        </div>
        <div class="article__read-more">
          <div class="article__add-to-cart">
            <?php if ($row->available) { ?>
                <input type="number" class="form-control" autocomplete="off" name="amount" min="<?php echo (int)$row->min ?: 1?>" step="<?php echo (int)$row->step ?: 1?>" value="<?php echo (int)$row->min ?: 1?>" />
                <button type="submit" class="btn btn-danger" title="<?php echo TO_CART?>"><span class="fa fa-shopping-cart"></span></button>
                <?php /* <a href="/cart/?action=add&id=<?php echo (int)$row->id?>" class="btn btn-danger" data-role="add-to-cart-trigger" data-id="<?php echo (int)$row->id?>" data-price="<?php echo (int)$row->price?>" title="<?php echo TO_CART?>" data-active-title="<?php echo DELETE_FROM_CART?>"><span class="fa fa-shopping-cart"></span></button> */ ?>
            <?php } ?>
            <a href="/favorites/?action=add&id=<?php echo (int)$row->id?>" class="btn btn-info" data-role="add-to-favorites-trigger" data-id="<?php echo (int)$row->id?>" title="<?php echo TO_FAVORITES?>" data-active-title="<?php echo DELETE_FROM_FAVORITES?>"><span class="fa fa-star"></span></a>
          </div>
        </div>
      </form>
    </div>
    <?php
};
