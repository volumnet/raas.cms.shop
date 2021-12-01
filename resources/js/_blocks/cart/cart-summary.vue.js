/**
 * Компонент сводки по корзине
 */
export default {
    props: {
        /**
         * Корзина
         * @type {Object}
         */
        cart: {
            type: Object,
            default: false,
        },
        /**
         * Перешли к этапу оформления заказа
         * @type {Boolean}
         */
        proceed: {
            type: Boolean,
            default: false,
        },
        /**
         * Перешли к быстрому заказу
         * @type {Boolean}
         */
        quickorder: {
            type: Boolean,
            default: false,
        },
        /**
         * Промо-код
         */
        promo: {
            required: false,
        }
    },
    data: function () {
        let translations = {
            GOODS_TO_AMOUNT_OF: 'Товаров на сумму',
            TOTAL_SUM: 'Итого',
            FOR_FREE: 'Бесплатно',
            MINIMAL_ORDER_SUM: 'Минимальная сумма заказа',
            CHECKOUT: 'Оформить заказ',
            ENTER_PROMO_CODE: 'Введите промо-код',
            LOADING: 'Загрузка...',
            APPLY: 'Применить',
            PROMO_CODE_NOT_FOUND: 'Промо-код не найден',
            QUICK_ORDER: 'Быстрый заказ',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            discountCardNum: this.promo, // Скидочная карта
            translations, // Переводы
        };
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
    }
}
