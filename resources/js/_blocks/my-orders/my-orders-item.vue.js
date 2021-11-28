/**
 * Компонент элемента списка "Мои заказы"
 */
export default {
    props: {
        /**
         * ID# блока
         * @type {Number}
         */
        blockId: {
            type: Number,
            required: true,
        },
        /**
         * Заказ для отображения
         * @type {Object}
         */
        item: {
            type: Object,
            required: true,
        },
        /**
         * Максимальная длина описания заказа
         * @type {Object}
         */
        descriptionLength: {
            type: Number,
            default: 64,
        },
    },
    data: function () {
        let translations = {
            ARE_YOU_SURE_TO_DELETE_ORDER: 'Вы действительно хотите удалить заказ?',
            BACK_TO_ORDERS: 'Назад к заказам',
            DELETE: 'Удалить',
            DELETE_ORDER: 'Удалить заказ',
            ORDER_STATUS_NEW: 'Новый',
            PAYMENT_PAID: 'Оплачен',
            PAYMENT_NOT_PAID: 'Не оплачен',
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
         * Форматирование цены
         * @param {Number} x Цена для форматирования
         * @return {String}
         */
        formatPrice: function (x) {
            return window.formatPrice(x);
        },
        /**
         * Удаление товара с запросом
         * @param {Object} item Заказ на удаление
         */
        deleteItem: async function (item) {
            if (!this.canBeDeleted) {
                return;
            }
            try {
                let confirmText = this.translations.ARE_YOU_SURE_TO_DELETE_ORDER;
                if (await this.$root.confirm(confirmText)) {
                    let result = await $.getJSON(this.itemDeleteURL);
                    this.$emit('delete', { item, result })
                }
            } catch (e) {}
        },
    },
    computed: {
        /**
         * Может ли пользователь удалить заказ
         * @return {Boolean}
         */
        canBeDeleted: function () {
            if (this.item.status && this.item.status.id) {
                return false;
            } 
            if (this.item.paid) {
                return false;
            } 
            if (this.item.vis) {
                return false;
            }
            return true;
        },
        /**
         * Возвращает форматированную дату заказа
         * @return {String}
         */
        formattedDate: function () {
            let lang = $('html').attr('lang') || 'ru';
            let momentFormat;
            switch (lang) {
                case 'en':
                    /**
                     * Формат Moment.JS
                     * @type {String}
                     */
                    momentFormat = 'MM/DD/YYYY HH:mm';
                    break;
                default:
                    momentFormat = 'DD.MM.YYYY HH:mm';
                    break;
            }
            let moment = window.moment(
                this.item.post_date, 
                'YYYY-MM-DD HH:mm:ss', 
                true
            );
            let result = moment.format(momentFormat);
            return result;
        },
        /**
         * Описание заказа
         * @return {String}
         */
        description: function () {
            let length = 0;
            let resultArr = [];
            for (let item of this.item.items) {
                if (!item.price) {
                    continue;
                }
                let itemName = '';
                if (item.amount > 1) {
                    itemName += item.amount + ' x ';
                }
                itemName += item.name;
                length += itemName.length;
                if (length < this.descriptionLength) {
                    resultArr.push(itemName);
                } else {
                    break;
                }
            }
            let result = resultArr.join(', ');
            if (length > this.descriptionLength) {
                result += '...';
            }
            return result;
        },
        /**
         * URL конкретного заказа
         * @return {String}
         */
        itemURL: function () {
            let result = window.location.pathname + '?id=' + this.item.id;
            return result;
        },
        /**
         * URL на удаление заказа
         * @return {String}
         */
        itemDeleteURL: function () {
            let result = this.itemURL + '&action=delete&AJAX=' + this.blockId;
            return result;
        },
    },
};