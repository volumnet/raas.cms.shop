/**
 * Товар в корзине
 */
export default {
    props: {
        /**
         * Товар в корзине
         * @type {Object}
         */
        item: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        return {
            /**
             * Текущее количество товара
             * @type {Number}
             */
            amount: parseInt(this.item.amount) || parseInt(this.item.min) || 1,
            /**
             * Текущая модификация
             * @type {Object}
             */
            metaJSON: this.item.metaJSON,
        }
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
        /**
         * Проверяет количество товара, чтобы не выходило за рамки
         */
        checkAmount: function () {
            let newAmount = Math.max(this.item.min, this.amount);
            let step = parseInt(this.item.step) || 1;
            if ((step > 0) && (newAmount % step != 0)) {
                newAmount = Math.ceil(newAmount / step) * step;
            }
            this.amount = newAmount;
        },
    },
    computed: {
        /**
         * Мета-данные товара (строка)
         * @return {String}
         */
        meta: function () {
            if ((this.metaJSON instanceof Object) && 
                (Object.keys(this.metaJSON).length > 0)
            ) {
                return JSON.stringify(this.metaJSON);
            }
            return '';
        },
    },
};