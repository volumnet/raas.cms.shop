/**
 * Список товаров в информере корзины
 */
export default {
    props: {
        /**
         * Корзина
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
        /**
         * Подпись "Товаров в корзине"
         * @type {Object}
         */
        itemsInCartCaption: {
            type: String,
            default: 'Товаров в корзине',
        },
        /**
         * Подпись кнопки "Перейти в корзину"
         * @type {Object}
         */
        goToCartCaption: {
            type: String,
            default: 'Перейти в корзину',
        },
    },
    data: function () {
        let translations = {
            TO_SUM: 'На сумму',
            CONTINUE_SHOPPING: 'Продолжить покупки',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
        };
    },
    mounted: function () {
        $(this.$el).on('click', () => {
            return false;
        })
    },
    methods: {
        /**
         * Форматирует цену
         * @param {Number} x Цена для форматирования
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
    },
};