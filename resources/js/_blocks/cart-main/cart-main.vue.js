/**
 * Информер корзины
 */
export default {
    props: {
        /**
         * Ссылка на страницу корзины
         * @type {String}
         */
        href: {
            type: String,
            default: '/cart/',
        },
        /**
         * Заголовок
         * @type {String}
         */
        title: {
            type: String,
        },
        /**
         * Корзина
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        let translations = {
            ITEMS_IN_CART: 'Товаров в корзине',
            GO_TO_CART: 'Перейти в корзину',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            /**
             * Активен ли список
             * @type {Boolean}
             */
            listActive: false,
            /**
             * Переводы
             * @type {Object}
             */
            translations,
        }
    },
    mounted: function () {
        $('.body').on('click', () => { 
            this.listActive = false;
        }).on('click', '.cart-main-list', (e) => { 
            e.stopPropagation();
        });
    },
    methods: {
        /**
         * Действие по клику на информер
         * @param {Event} e Событие
         */
        clickInformer: function (e) {
            if (!this.isCartPage &&
                this.cart.dataLoaded && 
                (this.cart.count > 0) &&
                (window.app.windowWidth >= window.app.mediaTypes.lg) &&
                !this.listActive
            )  {
                this.listActive = true;
                e.stopPropagation();
                e.preventDefault();
            }
        },

        /**
         * Открывает список товаров
         */
        openList: function () {
            this.listActive = true;
        },

        /**
         * Закрывает список товаров
         */
        closeList: function () {
            this.listActive = false;
        },

        /**
         * Переключает список товаров
         */
        toggleList: function () {
            this.listActive = !this.listActive;
        },

        /**
         * Удаляет товар
         * @param {Object} item Товар для удаления
         */
        deleteItem: function (item) {
            window.app.requestItemDelete(item, this.cart);
        },

        /**
         * Меняет товар
         * @param  {Object} item Товар для изменения
         * @param  {String} meta Новые мета-данные
         * @param  {Number} amount Новое количество
         */
        changeItem: async function (item, meta, amount) {
            if (meta != item.meta) {
                let newItem = Object.assign({}, item, { meta, amount });
                await this.cart.delete(item);
                await this.cart.add(item, amount);
            } else {
                await this.cart.set(item, amount);
            }
        },
        /**
         * Форматирует цену
         * @param {Number} x Цена для форматирования
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
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
         * Находимся на странице корзины
         * @return {Boolean}
         */
        isCartPage: function () {
            return /\/cart\//gi.test(window.location.pathname);
        }
    },
};