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
    data() {
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
    mounted() {
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
        clickInformer(e) {
            if (!this.isCartPage &&
                this.cart.dataLoaded && 
                (this.cart.count > 0) &&
                (this.$root.windowWidth >= this.$root.mediaTypes.lg) &&
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
        openList() {
            this.listActive = true;
        },

        /**
         * Закрывает список товаров
         */
        closeList() {
            this.listActive = false;
        },

        /**
         * Переключает список товаров
         */
        toggleList() {
            this.listActive = !this.listActive;
        },

        /**
         * Удаляет товар
         * @param {Object} item Товар для удаления
         */
        deleteItem(item) {
            this.$root.requestItemDelete(item, this.cart);
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
        formatPrice(x) {
            return window.formatPrice(x);
        },
    },
    computed: {
        /**
         * Находимся на странице корзины
         * @return {Boolean}
         */
        isCartPage() {
            return /\/cart\//gi.test(window.location.pathname);
        },
        /**
         * Распаковка текущего экземпляра для слота
         * @return {Object}
         */
        self() { 
            return {
                href: this.href,
                title: this.title,
                cart: this.cart,
                listActive: this.listActive,
                translations: this.translations,
                clickInformer: this.clickInformer.bind(this),
                openList: this.openList.bind(this),
                closeList: this.closeList.bind(this),
                toggleList: this.toggleList.bind(this),
                deleteItem: this.deleteItem.bind(this),
                changeItem: this.changeItem.bind(this),
                formatPrice: this.formatPrice.bind(this),
                isCartPage: this.isCartPage,
            };
        },
    },
};