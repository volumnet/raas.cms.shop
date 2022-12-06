/**
 * Компонент товара
 * !!! НЕ ПЕРЕДЕЛЫВАТЬ window.app в this.$root, иначе сломается динамическая подгрузка
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
        /**
         * Автоматически изменяять количество по корзине
         * @type {Object}
         */
        bindAmountToCart: {
            type: Boolean,
            default: false,
        },
    },
    data: function () {
        let translations = {
            ADDED_TO_CART: 'Товар добавлен в корзину',
            ADDED_TO_COMPARE: 'Товар добавлен в сравнение',
            ADDED_TO_FAVORITES: 'Товар добавлен в избранное',
            CART_DELETE_CONFIRM: 'Вы действительно хотите удалить этот товар из корзины?',
            COMPARE_DELETE_CONFIRM: 'Вы действительно хотите удалить этот товар из сравнения?',
            CONTINUE_SHOPPING: 'Продолжить',
            DELETE: 'Удалить',
            DELETE_FROM_CART: 'Удалить из корзины',
            DELETE_FROM_COMPARISON: 'Удалить из сравнения',
            DELETE_FROM_FAVORITES: 'Удалить из избранного',
            DELETED_FROM_CART: 'Товар удален из корзины',
            DELETED_FROM_COMPARE: 'Товар удален из сравнения',
            DELETED_FROM_FAVORITES: 'Товар удален из избранного',
            DO_BUY: 'Купить',
            FAVORITES_DELETE_CONFIRM: 'Вы действительно хотите удалить этот товар из избранного?',
            GO_TO_CART: 'В корзину',
            GO_TO_COMPARE: 'В сравнение',
            GO_TO_FAVORITES: 'В избранное',
            IN_COMPARISON: 'В сравнении',
            IN_FAVORITES: 'В избранном',
            TO_COMPARISON: 'В сравнение',
            TO_FAVORITES: 'В избранное',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            amount: (this.item.min > 1) ? this.item.min : 1, // Количество товара для добавления в корзину
            inCart: 0, // Находится ли товар в корзине
            inFavorites: 0, // Находится ли товар в избранном
            inCompare: 0, // Находится ли товар в сравнении
            translations, // Переводы
        };
    },
    mounted: function () {
        $(document).on('raas.shop.cart-updated', () => {
            this.checkCarts();
        });
        window.setTimeout(() => {
            this.checkCarts();
        }, 0); // таймаут чтобы инициализировалась переменная window.app
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
            this.inCart = window.app.cart.checkAmount(this.actualItem);
            if (this.bindAmountToCart) {
                this.amount = this.inCart;
            }
            this.inFavorites = window.app.favorites.checkAmount(this.actualItem);
            this.inCompare = window.app.compare.checkAmount(this.actualItem);
        },


        /**
         * Устанавливает количество товара (не ниже минимума)
         * @param {Number} amount Количество для установки
         */
        setAmount: function (amount) {
            let newAmount = Math.max(
                this.bindAmountToCart ? 0 : this.actualItem.min, 
                amount
            );
            let step = parseInt(this.actualItem.step) || 1;
            if ((step > 0) && (newAmount % step != 0)) {
                newAmount = Math.ceil(newAmount / step) * step;
            }
            this.amount = newAmount;
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
                items: [Object.assign({}, this.actualItem, { amount })],
                title: this.translations[titleURN + suffix],
                href: '/' + cart.id + '/', 
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
            this.inCart++;
            window.app.cart.add(this.actualItem, this.amount);
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(window.app.cart, this.amount);
                window.app.addedModal.show(modalData);
            });
        },


        /**
         * Устанавливает количество товара в корзине
         */
        setCart: function () {
            this.inCart = this.amount;
            window.app.cart.set(this.actualItem, this.amount);
        },


        /**
         * Добавляет/убирает товара в корзине
         */
        toggleCart: function () {
            window.app.cart.set(this.actualItem, this.inCart ? 0 : this.amount);
            this.inCart = this.inCart ? 0 : this.amount;
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
            window.app.favorites.set(this.actualItem, this.inFavorites ? 0 : 1);
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
            window.app.compare.set(this.actualItem, this.inCompare ? 0 : 1);
            this.inCompare = !this.inCompare;
            $(document).one('raas.shop.cart-updated', () => {
                let modalData = this.getCartModalData(
                    window.app.compare, 
                    this.inCompare ? 1 : -1
                );
                window.app.addedModal.show(modalData);
            });
        },


        /**
         * Удаление товара из корзины с подтверждением
         */
        requestItemDeleteFromCart: function () {
            return window.app.requestItemDelete(
                this.actualItem, 
                window.app.cart, 
                this.translations.CART_DELETE_CONFIRM
            );
        },


        /**
         * Удаление товара из избранного с подтверждением
         */
        requestItemDeleteFromFavorites: function () {
            return window.app.requestItemDelete(
                this.actualItem, 
                window.app.favorites, 
                this.translations.FAVORITES_DELETE_CONFIRM
            );
        },


        /**
         * Удаление товара из избранного с подтверждением
         */
        requestItemDeleteFromCompare: function () {
            return window.app.requestItemDelete(
                this.actualItem, 
                window.app.compare, 
                this.translations.COMPARE_DELETE_CONFIRM
            );
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
        /**
         * Мета-данные товара (строка)
         * @return {String}
         */
        meta: function () {
            if ((this.actualItem.metaJSON instanceof Object) && 
                (Object.keys(this.actualItem.metaJSON).length > 0)
            ) {
                return JSON.stringify(this.actualItem.metaJSON);
            } else if (this.actualItem.meta) {
                return this.actualItem.meta;
            }
            return '';
        },
        /**
         * Актуальный товар для покупки (с учетом meta)
         * @return {Object}
         */
        actualItem: function () {
            return this.item;
        },
    },
};