/**
 * Добавленный товар
 */
export default {
    props: {
        /**
         * Добавленный товар
         */
        item: {
            type: Object,
            required: true,
        },
    },
    methods: {
        /**
         * Форматирование цены
         * @param  {Number} x Цена
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
    },
};