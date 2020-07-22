<script>
export default {
    props: [
        'id', // ID# товара
        'name', // Наименование товара
        'price', // Цена товара 
        'priceold', // Старая цена товара 
        'meta', // Мета-данные, с которыми добавляется товар 
        'min', // Минимальное количество товара в корзине 
        'step', // Шаг товара в корзине 
        'image', // Изображение товара
        'cart', // Корзина
        'favorites', // Избранное
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

        this.inCart = !!this.checkInCart(this.cart);
        this.inFavorites = !!this.checkInCart(this.favorites);
        // console.log(this.id, this.inFavorites)
        // console.log(this.$el)
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
         * Проверка, присутствует ли товар в корзине
         * @param {AjaxCart} cart Корзина для проверки
         * @return {Number} Количество товара в корзине
         */
        checkInCart: function (cart) {
            try {
                let result = parseInt(cart.cookieCart.rawItems()[this.id][this.meta || '']);
                return result;
            } catch (e) {
                return 0;
            }
        },

        /**
         * Проверка корректности количества товара (не ниже минимума)
         */
        checkAmount: function () {
            this.amount = Math.max(this.min, this.amount);
        },

        /**
         * Получает данные для модального окна корзины
         * @param  {Number} amount Количество добавленного товара
         * @return {Object}
         */
        getCartModalData: function (amount = 1) {
            return {
                items: [{
                    image: this.image,
                    name: this.name,
                    price: this.price,
                    amount: this.amount,
                }],
                cart: this.cart.cookieCart,
                title: 'Товар ' + ((amount > 0) ? 'добавлен в корзину' : 'удален из корзины'),
                href: '/cart/', 
                submitTitle: 'Перейти в корзину', 
                dismissTitle: 'Продолжить покупки'
            };
        },

        /**
         * Получает данные для модального окна избранного
         * @param  {Number} amount Количество добавленного товара
         * @return {Object}
         */
        getFavoritesModalData: function (amount = 1) {
            return {
                items: [{
                    image: this.image,
                    name: this.name,
                    price: this.price,
                    amount: this.amount,
                }],
                title: 'Товар ' + ((amount > 0) ? 'добавлен в избранное' : 'удален из избранного'),
                href: '/favorites/', 
                submitTitle: 'Перейти в избранное', 
                dismissTitle: 'Продолжить покупки'
            };
        },

        /**
         * Добавляет/убирает товар в избранном
         */
        toggleFavorites: function () {
            this.favorites.set(this.id, this.inFavorites ? 0 : 1, '');
            this.inFavorites = !!this.checkInCart(this.favorites);
            var added = (this.inFavorites ? 1 : -1);
            var modalData = this.getFavoritesModalData(added);
            window.app.addedModal.show(modalData);
            return false;
        },

        /**
         * Добавляет товар в корзину
         */
        addToCart: function () {
            this.cart.add(this.id, this.amount, this.meta, this.price);
            this.inCart = !!this.checkInCart(this.cart);
            var modalData = this.getCartModalData();
            window.app.addedModal.show(modalData);
            return false;
        },

        /**
         * Добавляет/убирает товара в корзине
         */
        toggleCart: function () {
            this.cart.set(this.id, this.inCart ? 0 : 1, '');
            this.inCart = !!this.checkInCart(this.cart);
            var added = (!this.inCart ? 1 : -1);
            var modalData = this.getCartModalData(added);
            window.app.addedModal.show(modalData);
            return false;
        },
    },

}
</script>