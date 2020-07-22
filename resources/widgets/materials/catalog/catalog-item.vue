<script>
export default {
    props: [
        'id', // ID# товара
        'price', // Цена товара 
        'priceold', // Старая цена товара 
        'meta', // Мета-данные, с которыми добавляется товар 
        'min', // Минимальное количество товара в корзине 
        'step', // Шаг товара в корзине 
        'image' // Изображение товара
    ],
    data: function () {
        return {
            amount: 1, // Количество товара для добавления в корзину
            inCart: false, // Находится ли товар в корзине
            inFavorites: false, // Находится ли товар в избранном
        }
    },
    mounted: function () {
        var self = this;
        window.setTimeout(function () {
            window.lightBoxInit(true)
        }, 0);
        $(document).on('raas.shop.cart-updated', function (e, data) {
            if (data.id == 'cart') {
                var inCart = false;
                if ((data.data.items)) {
                    for (var key in data.data.items) {
                        var item = data.data.items[key];
                        if ((item.id == self.id) && (item.meta == self.meta)) {
                            inCart = true;
                            break;
                        }
                    }
                }
                self.inCart = inCart;
            } else if (data.id == 'favorites') {
                var inFavorites = false;
                if ((data.data.items)) {
                    for (var key in data.data.items) {
                        var item = data.data.items[key];
                        if (item.id == self.id) {
                            inFavorites = true;
                            break;
                        }
                    }
                }
                self.inFavorites = inFavorites;
            }
        })
    },
    methods: {
        /**
         * Форматирование цены (отделение тысяч, два знака после запятой)
         * @param  {Number} x Цена
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },

        /**
         * Проверка корректности количества товара (не ниже минимума)
         */
        checkAmount: function () {
            this.amount = Math.max(this.min, this.amount);
        },

        /**
         * Добавляет/убирает товар в избранном
         */
        toggleFavorites: function () {
            if (!this.inFavorites) {
                $.RAAS.Shop.itemAddedToFavoritesModal.modal('show');
            } else {
                $.RAAS.Shop.itemDeletedFromFavoritesModal.modal('show');
            }
            $.RAAS.Shop.ajaxFavorites.set(this.id, this.inFavorites ? 0 : 1, '');
            return false;
        },

        /**
         * Добавляет товар в корзину
         */
        addToCart: function () {
            $.RAAS.Shop.itemAddedToCartModal.modal('show');
            $.RAAS.Shop.ajaxCart.add(this.id, this.amount, this.meta, this.price);
            return false;
        },

        /**
         * Добавляет/убирает товара в корзине
         */
        toggleCart: function () {
            if (!this.inCart) {
                $.RAAS.Shop.itemAddedToCartModal.modal('show');
            } else {
                $.RAAS.Shop.itemDeletedFromCartModal.modal('show');
            }
            $.RAAS.Shop.ajaxCart.set(this.id, this.inCart ? 0 : 1, '');
            return false;
        },
    },

}
</script>