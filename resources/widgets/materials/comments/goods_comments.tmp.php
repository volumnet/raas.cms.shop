<?php if ($comments) { ?>
    <div class="catalog-article__comments goods-comments">
      <div class="goods-comments__list">
        <div class="goods-comments-list">
          <?php foreach ($comments as $row) { ?>
              <div class="goods-comments-list__item">
                <div class="goods-comments-item">
                  <div class="goods-comments-item__title">
                    <?php echo htmlspecialchars($row->name)?>
                  </div>
                  <div class="goods-comments-item__description">
                    <?php echo htmlspecialchars($row->brief) ?: $row->description?>
                  </div>
                </div>
              </div>
          <?php } ?>
        </div>
      </div>
    </div>
<?php } ?>
