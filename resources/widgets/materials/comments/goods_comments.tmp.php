<?php
/**
 * Виджет списка отзывов к товару
 * @param Material[] $Set Комментарии для отображения
 */
namespace RAAS\CMS\Shop;

use RAAS\CMS\Package;
use RAAS\CMS\Snippet;

if ($Set) { ?>
    <div class="catalog-article__comments goods-comments">
      <div class="goods-comments__list">
        <div class="goods-comments-list">
          <?php foreach ($Set as $comment) { ?>
              <div class="goods-comments-list__item">
                <div data-vue-role="goods-comments-item" data-vue-inline-template data-v-bind_id="<?php echo (int)$comment->id?>">
                  <div class="goods-comments-item" id="comment<?php echo (int)$comment->id?>">
                    <div class="goods-comments-item__header">
                      <div class="goods-comments-item__title">
                        <?php echo htmlspecialchars($comment->name)?>
                      </div>
                      <div class="goods-comments-item__date">
                        <a href="#comment<?php echo (int)$comment->id?>" class="goods-comments-item__link">#</a>
                        <?php echo date(DATETIMEFORMAT, strtotime($comment->post_date))?>
                      </div>
                      <?php if ($comment->rating) { ?>
                          <div class="goods-comments-item__rating">
                            <div class="goods-comments-item__rating-stars">
                              <?php Snippet::importByURN('rating')->process([
                                  'rating' => $comment->rating
                              ]);?>
                            </div>
                            <div class="goods-comments-item__rating-text">
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
                            </div>
                          </div>
                      <?php } ?>
                    </div>
                    <?php if ($comment->advantages) { ?>
                        <div class="goods-comments-item__description">
                          <span class="goods-comments-item__description-title"><?php echo ADVANTAGES?>:</span>
                          <?php echo htmlspecialchars($comment->advantages)?>
                        </div>
                    <?php } ?>
                    <?php if ($comment->disadvantages) { ?>
                        <div class="goods-comments-item__description">
                          <span class="goods-comments-item__description-title"><?php echo DISADVANTAGES?>:</span>
                          <?php echo htmlspecialchars($comment->disadvantages)?>
                        </div>
                    <?php } ?>
                    <div class="goods-comments-item__description">
                      <?php if ($comment->advantages || $comment->disadvantages) { ?>
                          <span class="goods-comments-item__description-title"><?php echo COMMENT?>:</span>
                      <?php } ?>
                      <?php echo htmlspecialchars($comment->brief) ?: $comment->description?>
                    </div>
                    <div class="goods-comments-item__is-useful">
                      <div class="goods-comments-item__is-useful-title">
                        <?php echo IS_REVIEW_USEFUL?>
                      </div>
                      <div class="goods-comments-item__is-useful-votes-list">
                        <div class="goods-comments-item-is-useful-votes-list">
                          <div class="goods-comments-item-is-useful-votes-list__item">
                            <div class="goods-comments-item-is-useful-votes-item goods-comments-item-is-useful-votes-item_pros" data-v-bind_class="{ 'goods-comments-item-is-useful-votes-item_voted': voted, 'goods-comments-item-is-useful-votes-item_active': (voted > 0) }">
                              <a class="goods-comments-item-is-useful-votes-item__vote" data-v-on_click="vote(1)"></a>
                              <span class="goods-comments-item-is-useful-votes-item__counter" v-if="votesRetrieved" v-html="pros"></span>
                            </div>
                          </div>
                          <div class="goods-comments-item-is-useful-votes-list__item">
                            <div class="goods-comments-item-is-useful-votes-item goods-comments-item-is-useful-votes-item_cons" data-v-bind_class="{ 'goods-comments-item-is-useful-votes-item_voted': voted, 'goods-comments-item-is-useful-votes-item_active': (voted < 0) }">
                              <a class="goods-comments-item-is-useful-votes-item__vote" data-v-on_click="vote(-1)"></a>
                              <span class="goods-comments-item-is-useful-votes-item__counter" v-if="votesRetrieved" v-html="cons"></span>
                            </div>
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
    <?php
    Package::i()->requestJS('/js/goods-comments.js');
}
