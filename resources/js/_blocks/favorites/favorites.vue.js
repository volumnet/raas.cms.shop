/**
 * Компонент избранного
 */
export default {
    props: {
        /**
         * Корзина избранного
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        let translations = {
            ARE_YOU_SURE_TO_CLEAR_FAVORITES: 'Вы действительно хотите очистить избранное?',
            FAVORITES_IS_LOADING: 'Избранное загружается...',
            YOUR_FAVORITES_IS_EMPTY: 'Пока ни одного товара не добавлено в избранное',
            CLEAR_FAVORITES: 'Очистить избранное',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
        };
    },
    methods: {
        /**
         * Запрос на очистку корзины
         */
        requestClear: function () {
            this.$root.requestCartClear(
                this.cart, 
                this.translations.ARE_YOU_SURE_TO_CLEAR_FAVORITES
            );
        },
    },
};
