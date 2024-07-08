<?php
/**
 * Список вопросов к товару
 * @param Material[] $Set Комментарии для отображения
 */
namespace RAAS\CMS\Shop;

if ($Set) { ?>
    <div class="goods-faq goods-faq__list goods-faq-list" data-v-bind_block-id="<?php echo (int)$Block->id?>">
      <?php foreach ($Set as $comment) { ?>
          <div
            data-vue-role="goods-faq-item"
            class="goods-faq-list__item goods-faq-item"
            id="faq<?php echo (int)$comment->id?>"
            data-v-slot="vm"
          >
            <div class="goods-faq-item__question goods-faq-item__text">
              <div class="goods-faq-item__header">
                <span class="goods-faq-item__title">
                  <a href="#faq<?php echo (int)$comment->id?>" class="goods-comments-item__link">#</a>
                  <?php echo htmlspecialchars($comment->full_name)?>
                </span>
                <?php
                $time = strtotime($comment->date);
                if ($time <= 0) {
                    $time = strtotime($comment->post_date);
                }
                if ($time > 0) { ?>
                    <span class="goods-faq-item__date">
                      <?php echo date(DATEFORMAT, $time)?>
                    </span>
                <?php } ?>
              </div>
              <div class="goods-faq-item__description">
                <?php echo htmlspecialchars($comment->name)?>
              </div>
            </div>
            <?php if ($comment->answer) { ?>
                <div class="goods-faq-item__answer">
                  <?php if ($comment->answer_image->id) { ?>
                      <img
                        class="goods-faq-item__image"
                        loading="lazy"
                        src="/<?php echo htmlspecialchars($comment->answer_image->tnURL)?>"
                        alt="<?php echo htmlspecialchars($comment->answer_image->name ?: $comment->answer_name)?>"
                      />
                  <?php } ?>
                  <div class="goods-faq-item__text goods-faq-item__text_answer">
                    <div class="goods-faq-item__header">
                      <span class="goods-faq-item__title">
                        <?php if ($comment->answer_name) {
                            if (trim($comment->answer_gender) == '1') {
                                echo ANSWERED_MALE;
                            } elseif (trim($comment->answer_gender) == '0') {
                                echo ANSWERED_FEMALE;
                            } else {
                                echo ANSWERED_UNDEFINED;
                            }
                            echo ' ' . htmlspecialchars($comment->answer_name);
                        } else {
                            echo ANSWER;
                        } ?>
                      </span>
                      <?php
                      $time = strtotime($comment->answer_date);
                      if ($time <= 0) {
                          $time = strtotime($comment->modify_date);
                      }
                      if ($time > 0) {
                          ?>
                          <span class="goods-faq-item__date">
                            <?php echo date(DATEFORMAT, $time)?>
                          </span>
                      <?php } ?>
                    </div>
                    <div
                      class="goods-faq-item__description"
                      data-vue-role="overflowable-container"
                      data-v-on_overflows="vm.setOverflows($event.y)"
                    >
                      <?php echo $comment->answer?>
                    </div>
                    <div class="goods-faq-item__more" data-v-if="vm.overflowed">
                      <button
                        type="button"
                        class="btn btn-link"
                        data-v-on_click="vm.toggle()"
                        data-v-html="
                            vm.active ?
                            <?php echo htmlspecialchars(json_encode(HIDE))?> :
                            <?php echo htmlspecialchars(json_encode(READ_ANSWER))?>
                        "
                      >
                        <?php echo READ_ANSWER?>
                      </button>
                    </div>
                  </div>
                </div>
            <?php } ?>
          </div>
      <?php } ?>
    </div>
<?php }
