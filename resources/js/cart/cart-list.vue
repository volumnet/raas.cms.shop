<script>
import CartItem from './cart-item.vue';

export default {
    props: ['items', 'cart'],
    components: {
        'cart-item': CartItem,
    },
    template: '#cart-list-template',
    methods: {
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        itemUpdate: function (item) {
            this.cart.set(item.id, item.amount, item.meta, item.price);
        },
    },
    computed: {
        amount: function () {
            var amount = this.items.reduce(function (acc, item) {
                return acc + (parseInt(item.amount) || 1);
            }, 0);
            return amount;
        },
        sum: function () {
            var sum = this.items.reduce(function (acc, item) {
                return acc + (
                    (parseFloat(item.price) || 0) *
                    (parseInt(item.amount) || 1)
                );
            }, 0);
            return sum;
        },
    },
};
</script>