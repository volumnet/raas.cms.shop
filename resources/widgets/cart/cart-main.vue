<template>
  <a href="/cart/" class="cart-main" rel="nofollow" :class="{ 'cart-main_active': dataLoaded }">
    <span class="cart-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="cart-main__text">
      <span class="cart-main__title">{{title}}</span>
      <span class="cart-main__sum-outer">
        <span class="cart-main__sum">
          {{ formatPrice(sum) }}
        </span>
        <span class="cart-main__sum-currency">₽</span>
      </span>
    </span>
  </a>
</template>

<script>
export default {
    props: {
        title: {
            type: String,
            default: 'Корзина',
        },
        numerals: {
            type: Array,
            default: function () {
                return ['товаров', 'товар', 'товара'];
            },
        }
    },
    data: function () {
        return {
            dataLoaded: false,
            amount: 0,
            sum: 0,
        }
    },
    mounted: function () {
        var self = this;
        $(document).on('raas.shop.cart-updated', function (e, data) {
            if ((data.id == self.remoteCartId) && data.remote) {
                self.sum = data.data.sum;
                self.amount = data.data.count;
                self.dataLoaded = true;
            }
        });
    },
    methods: {
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        numTxt: window.numTxt,
    },
    computed: {
        amountText: function () {
            return window.numTxt(this.amount, this.numerals);
        },
    },
};
</script>