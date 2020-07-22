<script>
import CartList from './cart-list.vue';

export default {
    props: ['cart'],
    components: {
        'cart-list': CartList,
    },
    data: function () {
        return window.raasShopCartData;
    },
    mounted: function () {
        // $('input[name="phone"]').inputmask('+9 (999) 999-99-99', { showMaskOnHover: false });
    },
    methods: {
        requestItemDelete: function (item) {
            var self = this;
            window.app.confirm('Вы действительно хотите удалить этот товар?')
                .then(function () {
                    self.items = self.items.filter(function (x) {
                        return (x.id != item.id) ||
                               (x.meta != item.meta);
                    });
                    self.cart.set(item.id, 0, item.meta, item.price);
                });
        },
        requestClear: function () {
            var self = this;
            window.app.confirm('Вы действительно хотите очистить ' + ((this.cart.id == 'favorites') ? 'избранное' : 'корзину') + '?')
                .then(function () {
                    self.cart.clear();
                    self.items = [];
                });
        },
    },
};
</script>