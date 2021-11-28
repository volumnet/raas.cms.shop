/**
 * Компонент списка товаров заказа
 */
export default {
    props: {
        /**
         * Заказ
         * @type {Object}
         */
        item: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        let translations = {
            AMOUNT: 'Количество',
            NAME: 'Наименование',
            PRICE: 'Стоимость',
            SUM: 'Сумма',
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
    },
};
