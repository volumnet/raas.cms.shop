/**
 * Компонент товара
 */
export default {
    props: {
        /**
         * Товар
         * <pre><code>{
         *     id: Number ID#,
         *     name: String Наименование,
         *     price: Number Стоимость,
         *     price_old: Number Старая цена,
         *     meta: String мета-данные, с которыми добавляется товар
         *     min: Number Минимальное количество для добавления в корзину
         *     step: Шаг корзины,
         *     image: String Ссылка на изображение,
         * }</code></pre>
         */
        item: {
            type: Object,
            required: true
        },
        /**
         * Корзина
         */
        cart: {
            type: Object,
        },
        /**
         * Избранное
         */
        favorites: {
            type: Object,
        },
        /**
         * Сравнение
         * @type {Object}
         */
        compare: {
            type: Object,
        },
    },
    data: function () {
        let translations = {
            ADDED_TO_CART: 'Товар добавлен в корзину',
            DELETED_FROM_CART: 'Товар удален из корзины',
            GO_TO_CART: 'Перейти в корзину',
            ADDED_TO_FAVORITES: 'Товар добавлен в избранное',
            DELETED_FROM_FAVORITES: 'Товар удален из избранного',
            GO_TO_FAVORITES: 'Перейти в избранное',
            ADDED_TO_COMPARE: 'Товар добавлен в сравнение',
            DELETED_FROM_COMPARE: 'Товар удален из сравнения',
            GO_TO_COMPARE: 'Перейти в сравнение',
            CONTINUE_SHOPPING: 'Продолжить покупки',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            amount: this.item.min || 1, // Количество товара для добавления в корзину
            inCart: false, // Находится ли товар в корзине
            inFavorites: false, // Находится ли товар в избранном
            inCompare: false, // Находится ли товар в сравнении
            translations, // Переводы
        };
    },
    mounted: function () {
        this.inCart = !!this.checkInCart(this.cart);
        this.inFavorites = !!this.checkInCart(this.favorites);
        this.inCompare = !!this.checkInCart(this.compare);
        $(document).on('raas.shop.cart-updated', () => {
            this.inCart = !!this.checkInCart(this.cart);
            this.inFavorites = !!this.checkInCart(this.favorites);
            this.inCompare = !!this.checkInCart(this.compare);
        });
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
                let result = parseInt(cart.cookieCart.rawItems()[this.item.id][this.item.meta || '']);
                return result;
            } catch (e) {
                return 0;
            }
        },


        /**
         * Устанавливает количество товара (не ниже минимума)
         * @param {Number} amount Количество для установки
         */
        setAmount: function (amount) {
            this.amount = Math.max(this.item.min, amount);
        },


        /**
         * Получает данные для модального окна корзины
         * @param  {Number} amount 1 - товар добавлен, -1 - товар удален
         * @return {Object}
         */
        getCartModalData: function (amount = 1) {
            return {
                items: [Object.assign({}, this.item, { amount: this.amount })],
                cart: this.cart,
                title: this.translations[(amount > 0) ? 'ADDED_TO_CART' : 'DELETED_FROM_CART'],
                href: '/cart/', 
                submitTitle: this.translations.GO_TO_CART, 
                dismissTitle: this.translations.CONTINUE_SHOPPING,
            };
        },


        /**
         * Получает данные для модального окна избранного
         * @param  {Number} amount amount 1 - товар добавлен, -1 - товар удален
         * @return {Object}
         */
        getFavoritesModalData: function (amount = 1) {
            return {
                items: [Object.assign({}, this.item, { amount: this.amount })],
                title: this.translations[(amount > 0) ? 'ADDED_TO_FAVORITES' : 'DELETED_FROM_FAVORITES'],
                href: '/favorites/', 
                submitTitle: this.translations.GO_TO_FAVORITES, 
                dismissTitle: this.translations.CONTINUE_SHOPPING,
            };
        },


        /**
         * Получает данные для модального окна сравнения
         * @param  {Number} amount amount 1 - товар добавлен, -1 - товар удален
         * @return {Object}
         */
        getCompareModalData: function (amount = 1) {
            return {
                items: [Object.assign({}, this.item, { amount: this.amount })],
                title: this.translations[(amount > 0) ? 'ADDED_TO_COMPARE' : 'DELETED_FROM_COMPARE'],
                href: '/compare/', 
                submitTitle: this.translations.GO_TO_COMPARE, 
                dismissTitle: this.translations.CONTINUE_SHOPPING,
            };
        },


        /**
         * Добавляет товар в корзину
         */
        addToCart: function () {
            this.inCart = true;
            this.cart.add(this.item.id, this.amount, this.item.meta, this.item.price);
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData();
                window.app.addedModal.show(modalData);
            })
        },


        /**
         * Добавляет/убирает товара в корзине
         */
        toggleCart: function () {
            let added = (this.inCart ? -1 : 1);
            this.cart.set(this.item.id, this.inCart ? 0 : 1, '');
            this.inCart = !this.inCart;
            let modalData = this.getCartModalData(added);
            window.app.addedModal.show(modalData);
        },


        /**
         * Добавляет/убирает товар в избранном
         */
        toggleFavorites: function () {
            let added = (this.inFavorites ? -1 : 1);
            this.favorites.set(this.item.id, this.inFavorites ? 0 : 1, '');
            this.inFavorites = !this.inFavorites;
            let modalData = this.getFavoritesModalData(added);
            window.app.addedModal.show(modalData);
        },


        /**
         * Добавляет/убирает товар в избранном
         */
        toggleCompare: function () {
            let added = (this.inCompare ? -1 : 1);
            this.compare.set(this.item.id, this.inFavorites ? 0 : 1, '');
            this.inCompare = !this.inCompare;
            let modalData = this.getFavoritesModalData(added);
            window.app.addedModal.show(modalData);
        },
    },
    computed: {
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self: function () { 
            return { ...this };
        },
    },
};