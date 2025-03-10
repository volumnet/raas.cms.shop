/**
 * Компонент мини-списка в заказе
 */
export default {
    props: {
        cart: {
            type: Object,
            required: true,
        },
    },
    emits: ['back'],
    data() {
        let translations = {
            BACK: 'Назад',
            YOUR_ORDER: 'Ваш заказ',
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
         * Форматирует цену
         * @param {Number} x Цена для форматирования
         * @return {String}
         */
        formatPrice(x) {
            return window.formatPrice(x);
        },
    },
};
