<?php
$formatPrice = function($price) {
    $remainder = (float)$price - (float)(int)$price;
    return str_replace(' ', '&nbsp;', number_format((float)$price, ($remainder > 0) ? 2 : 0, ',', ' ' ));
};

$showItem = function($row) use ($formatPrice)
{
    ?>
    <article class="article">
      <div class="article__title"><a href="<?php echo $row->url?>"><?php echo htmlspecialchars($row->name)?></a></div>
      <?php if ($row->article) { ?>
          <div class="article__article"><?php echo ARTICLE_SHORT?> <a href="<?php echo $row->url?>"><?php echo htmlspecialchars($row->article)?></a></div>
      <?php } ?>
      <div class="article__image<?php echo !$row->visImages ? ' article__image_nophoto' : ''?>">
        <?php if ($row->visImages) { ?>
            <a href="<?php echo $row->url?>">
              <img src="/<?php echo htmlspecialchars(addslashes($row->visImages[0]->tnURL))?>" /></a>
        <?php } ?>
      </div>
      <form action="/cart/" class="article__controls" data-role="add-to-cart-form">
        <?php if ($row->available) { ?>
            <input type="hidden" name="action" value="add" />
            <input type="hidden" name="id" value="<?php echo (int)$row->id?>" />
            <input type="hidden" name="back" value="1" />
            <input type="hidden" name="amount" value="1" />
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
          <?php if ($row->available) { ?>
              <button type="submit" class="btn btn-danger" data-role="add-to-cart-trigger" data-price="<?php echo (int)$row->price?>">В корзину</button>
          <?php } else { ?>
              <a class="btn btn-danger" data-target="#requestItemModal" data-request-item-id="<?php echo (int)$row->id?>" data-request-item-name="<?php echo htmlspecialchars($row->name)?>" data-request-item-url="<?php echo htmlspecialchars($row->url)?>" data-toggle="modal">Заказать</a>
          <?php } ?>
          <a href="<?php echo $row->url?>" class="btn btn-success"><?php echo SHOW_MORE?></a>
        </div>
      </form>
    </article>
    <?php
};