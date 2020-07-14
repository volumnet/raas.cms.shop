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
        },
        remoteCartId: {
            type: String,
            default: 'cart'
        },
    },
    template: '#cart-main-template',
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