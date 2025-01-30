/**
 * Компонент пользовательских данных заказа
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
    data() {
        let translations = {
            ORDER_STATUS_NEW: 'Новый',
            PAYMENT_PAID: 'Оплачен',
            PAYMENT_NOT_PAID: 'Не оплачен',
            STATUS: 'Статус',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
        };
    },
};