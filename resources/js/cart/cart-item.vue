<script>
export default {
    props: ['item'],
    template: '#cart-item-template',
    methods: {
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        checkAmount: function ($event) {
            this.item.amount = Math.max(this.item.min, this.item.amount);
            this.$emit('change', this.item);
        },
    },
};
</script>