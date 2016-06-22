<?php
$showCategory = function($row)
{
    $queryString = http_build_query(array_intersect_key($_GET, array_flip(array('brand', 'model', 'engine'))));
    $queryString = $queryString ? '?' . $queryString : '';
    ?>
    <a class="category" href="<?php echo $row->url . $queryString ?>">
      <div class="category__image<?php echo !$row->image->id ? ' category__image_nophoto' : ''?>">
        <?php if ($row->image->id) { ?>
            <img src="/<?php echo htmlspecialchars(addslashes($row->image->smallURL))?>" />
        <?php } ?>
      </div>
      <div class="category__text">
        <div class="category__title">
          <?php echo htmlspecialchars($row->name . ((int)$row->counter ? ' (' . (int)$row->counter . ')' : ''))?>
        </div>
      </div>
    </a>
    <?php
};
