<?php
/**
 * Виджет списка отзывов к товару
 * @param Material[] $Set Комментарии для отображения
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Snippet;

if ($_GET['AJAX'] == $Block->id) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['votes' => $votes]);
    exit;
} elseif ($Set) { ?>
    <div class="goods-comments" data-vue-role="goods-comments" data-v-bind_block-id="<?php echo (int)$Block->id?>" data-v-slot="commentsVm">
      <div class="goods-comments__list">
        <div class="goods-comments-list">
          <?php foreach ($Set as $comment) { ?>
              <div class="goods-comments-list__item">
                <div data-vue-role="goods-comments-item" data-v-bind_item-id="<?php echo (int)$comment->id?>" data-v-slot="itemVm" class="goods-comments-item" id="comment<?php echo (int)$comment->id?>">
                  <div class="goods-comments-item__header">
                    <span class="goods-comments-item__title">
                      <a href="#comment<?php echo (int)$comment->id?>" class="goods-comments-item__link">#</a>
                      <?php echo htmlspecialchars($comment->name)?>
                    </span>
                    <?php
                    $time = strtotime($comment->date);
                    if ($time <= 0) {
                        $time = strtotime($item->post_date);
                    }
                    if ($time > 0) { ?>
                        <span class="goods-comments-item__date">
                          <?php echo date(DATEFORMAT, $time)?>
                        </span>
                    <?php }
                    if ($comment->rating) { ?>
                        <span class="goods-comments-item__rating">
                          <span class="goods-comments-item__rating-stars">
                            <?php Snippet::importByURN('rating')->process([
                                'rating' => $comment->rating
                            ]);?>
                          </span>
                          <span class="goods-comments-item__rating-text">
                            <?php
                            $ratingText = [
                                '',
                                RATING_TERRIBLE,
                                RATING_BAD,
                                RATING_NORMAL,
                                RATING_GOOD,
                                RATING_EXCELLENT,
                            ];
                            echo $ratingText[(int)$comment->rating];
                            ?>
                          </span>
                        </span>
                    <?php } ?>
                  </div>
                  <?php if ($comment->advantages) { ?>
                      <div class="goods-comments-item__description">
                        <span class="goods-comments-item__description-title"><?php echo ADVANTAGES?>:</span>
                        <?php echo htmlspecialchars($comment->advantages)?>
                      </div>
                  <?php }
                  if ($comment->disadvantages) { ?>
                      <div class="goods-comments-item__description">
                        <span class="goods-comments-item__description-title"><?php echo DISADVANTAGES?>:</span>
                        <?php echo htmlspecialchars($comment->disadvantages)?>
                      </div>
                  <?php } ?>
                  <div class="goods-comments-item__description">
                    <?php if ($comment->advantages || $comment->disadvantages) { ?>
                        <span class="goods-comments-item__description-title"><?php echo COMMENT?>:</span>
                    <?php }
                    echo htmlspecialchars($comment->brief) ?: $comment->description;
                    ?>
                  </div>
                  <div class="goods-comments-item__is-useful">
                    <div class="goods-comments-item__is-useful-title">
                      <?php echo IS_REVIEW_USEFUL?>
                    </div>
                    <div class="goods-comments-item__is-useful-votes-list">
                      <div class="goods-comments-item-is-useful-votes-list">
                        <div class="goods-comments-item-is-useful-votes-list__item">
                          <div class="goods-comments-item-is-useful-votes-item goods-comments-item-is-useful-votes-item_pros" data-v-bind_class="{ 'goods-comments-item-is-useful-votes-item_voted': itemVm.voted, 'goods-comments-item-is-useful-votes-item_active': (itemVm.voted > 0) }">
                            <a class="goods-comments-item-is-useful-votes-item__vote" data-v-on_click="commentsVm.vote(<?php echo (int)$comment->id?>, 1)"></a>
                            <span class="goods-comments-item-is-useful-votes-item__counter" v-if="itemVm.votesRetrieved" v-html="itemVm.pros"></span>
                          </div>
                        </div>
                        <div class="goods-comments-item-is-useful-votes-list__item">
                          <div class="goods-comments-item-is-useful-votes-item goods-comments-item-is-useful-votes-item_cons" data-v-bind_class="{ 'goods-comments-item-is-useful-votes-item_voted': itemVm.voted, 'goods-comments-item-is-useful-votes-item_active': (itemVm.voted < 0) }">
                            <a class="goods-comments-item-is-useful-votes-item__vote" data-v-on_click="commentsVm.vote(<?php echo (int)$comment->id?>, -1)"></a>
                            <span class="goods-comments-item-is-useful-votes-item__counter" v-if="itemVm.votesRetrieved" v-html="itemVm.cons"></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
          <?php } ?>
        </div>
      </div>
    </div>
<?php }
