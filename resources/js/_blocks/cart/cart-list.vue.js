/**
 * Список корзины
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
    },
    data: function () {
        let translations = {
            AMOUNT: 'Количество',
            ARE_YOU_SURE_TO_CLEAR_CART: 'Вы действительно хотите очистить корзину?',
            CONTINUE_SHOPPING: 'Продолжить покупки',
            GO_TO_CART: 'Перейти в корзину',
            IMAGE: 'Изображение',
            ITEMS_IN_CART: 'Товаров в корзине',
            NAME: 'Наименование',
            PRICE: 'Стоимость',
            SUM: 'Сумма',
            TO_SUM: 'На сумму',
            TOTAL_SUM: 'Итого',
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
         * Форматирование цены (отделение тысяч, два знака после запятой)
         * @param  {Number} x Цена
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        /**
         * Запрос на очистку корзины
         */
        requestClear: function () {
            return this.$root.requestCartClear(
                this.cart, 
                this.translations.ARE_YOU_SURE_TO_CLEAR_CART
            );
        },
    },
};