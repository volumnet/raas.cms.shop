/**
 * Mixin приложения с интернет-магазином
 */
export default {
    data: function () {
        let translations = {
            ARE_YOU_SURE_TO_CLEAR_CART: 'Вы действительно хотите очистить корзину?',
            CART_DELETE_CONFIRM: 'Вы действительно хотите удалить этот товар?',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            cart: null, // Объект корзины
            favorites: null, // Объект избранного
            compare: null, // Объект сравнения
            translations, // Переводы
        }
    },
    mounted: function () {
        window.setTimeout(() => {
            this.cart && this.cart.update();
            this.favorites && this.favorites.update();
            this.compare && this.compare.update();
        }, 0);

        $(document).on('raas.shop.cart-updated', (e, data) => {
            this.$forceUpdate();
        });
    },
    methods: {
        /**
         * Запрос на удаление
         * @param  {Object} item Товар для удаления
         * @param  {Cart} cart Корзина
         * @param  {String} confirmText Текст подтверждения
         */
        requestItemDelete: async function (item, cart, confirmText) {
            if (!confirmText) {
                confirmText = this.translations.CART_DELETE_CONFIRM;
            }
            try {
                if (await this.confirm(confirmText)) {
                    cart.delete(item);
                }
            } catch (e) {}
        },
        /**
         * Запрос на очистку корзины
         * @param  {Cart} cart Корзина
         * @param  {String} confirmText Текст подтверждения
         */
        requestCartClear: async function (cart, confirmText) {
            if (!confirmText) {
                confirmText = this.translations.ARE_YOU_SURE_TO_CLEAR_CART;
            }
            try {
                if (await this.confirm(confirmText)) {
                    cart.clear();
                }
            } catch (e) {}
        },
    },
    computed: {
        // Компонент "Товар добавлен в ..."
        addedModal: function () {
            return this.$refs.addedModal;
        },
    },
};