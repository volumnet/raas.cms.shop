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
         *     available: Boolean Товар в наличии,
         * }</code></pre>
         */
        item: {
            type: Object,
            required: true
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
        let amount = 1;
        if (this.item.available && (this.item.min > 1)) {
            amount = this.item.min;
        }
        return {
            amount: amount, // Количество товара для добавления в корзину
            inCart: false, // Находится ли товар в корзине
            inFavorites: false, // Находится ли товар в избранном
            inCompare: false, // Находится ли товар в сравнении
            translations, // Переводы
        };
    },
    mounted: function () {
        $(document).on('raas.shop.cart-updated', () => {
            this.checkCarts();
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
         * Проверяет наличие в корзинах
         */
        checkCarts: function () {
            this.inCart = !!window.app.cart.checkAmount(this.item);
            this.inFavorites = !!window.app.favorites.checkAmount(this.item);
            this.inCompare = !!window.app.compare.checkAmount(this.item);
        },


        /**
         * Устанавливает количество товара (не ниже минимума)
         * @param {Number} amount Количество для установки
         */
        setAmount: function (amount) {
            this.amount = Math.max(this.item.min, amount);
        },


        /**
         * Получает данные для модального окна корзин
         * @param {Cart} cart Корзина
         * @param {Number} amount 1 - товар добавлен, -1 - товар удален
         * @return {Object}
         */
        getCartModalData: function (cart, amount = 1) {
            let suffix = '_' + cart.id.toUpperCase();
            let titleURN = ((amount > 0) ? 'ADDED_TO' : 'DELETED_FROM');
            let result = {
                items: [Object.assign({}, this.item, { amount })],
                title: this.translations[titleURN + suffix],
                href: '/cart/', 
                submitTitle: this.translations['GO_TO' + suffix], 
                dismissTitle: this.translations.CONTINUE_SHOPPING,
            };
            if (cart.id == 'cart') {
                result.cart = cart;
            }
            return result;
        },


        /**
         * Добавляет товар в корзину
         */
        addToCart: function () {
            this.inCart = true;
            window.app.cart.add(this.item, this.amount);
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(window.app.cart, this.amount);
                window.app.addedModal.show(modalData);
            });
        },


        /**
         * Добавляет/убирает товара в корзине
         */
        toggleCart: function () {
            window.app.cart.set(this.item, this.inCart ? 0 : this.amount);
            this.inCart = !this.inCart;
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(
                    window.app.cart, 
                    this.inCart ? 1 : -1
                );
                window.app.addedModal.show(modalData);
            });
        },


        /**
         * Добавляет/убирает товар в избранном
         */
        toggleFavorites: function () {
            window.app.favorites.set(this.item, this.inFavorites ? 0 : 1);
            this.inFavorites = !this.inFavorites;
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(
                    window.app.favorites, 
                    this.inFavorites ? 1 : -1
                );
                window.app.addedModal.show(modalData);
            });
        },


        /**
         * Добавляет/убирает товар в избранном
         */
        toggleCompare: function () {
            window.app.compare.set(this.item, this.inCompare ? 0 : 1);
            this.inCompare = !this.inCompare;
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(
                    window.app.compare, 
                    this.inCompare ? 1 : -1
                );
                window.app.addedModal.show(modalData);
            });
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