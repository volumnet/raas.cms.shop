<template>
  <div class="modal fade item-added" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header" style="border-bottom: none">
          <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
          <h4 class="modal-title">{{title}}</h4>
        </div>
        <div class="modal-body">
          <div class="item-added__list" v-if="items.length">
            <div class="item-added-list">
              <div class="item-added-list__item" v-for="item in items">
                <div class="item-added-item">
                  <div class="item-added-item__image">
                    <img :src="item.image" alt="" v-if="item.image">
                  </div>
                  <div class="item-added-item__text">
                    <div class="item-added-item__title">
                      {{item.name}}
                    </div>
                  </div>
                  <div class="item-added-item__price">
                    {{formatPrice(item.price)}} ₽
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="item-added__cart" v-if="cart">
            Общая сумма заказа:
            <span class="item-added__cart-sum">{{cart.sum}} ₽</span>
          </div>
        </div>
        <div class="modal-footer">
          <a :href="href" class="btn btn-primary">{{submitTitle}}</a>
          <button type="button" class="btn btn-default" data-dismiss="modal">{{dismissTitle}}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
    data: function () {
        return {
            items: [], 
            cart: null, 
            href: '', 
            title: '', 
            submitTitle: '', 
            dismissTitle: ''
        };
    },
    methods: {
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        show: function (data = {}) {
            for (let key in data) {
                this[key] = data[key];
            }
            $(this.$el).modal('show');
        }
    }

}
</script>