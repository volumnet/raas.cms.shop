/**
 * Компонент списка пунктов доставки
 */
export default {
    props: {
        /**
         * Пункты выдачи
         * @type {Array} <pre><code>array<{
         *     id: String ID# пункта выдачи,
         *     name: String Наименование пункта выдачи,
         *     address: String Адрес пункта выдачи
         *     description: String Подсказка к адресу,
         *     lat: Number Широта,
         *     lon: Number Долгота,
         *     serviceURN: String URN сервиса,
         *     serviceName: String Наименование сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки,
         *     dateTo?: String Максимальная дата доставки,
         *     schedule?: String Время работы,
         *     phones?: String[] Телефоны (Последние 10 цифр),
         *     images?: String[] URL картинок,
         * }></code></pre>
         */
        points: {
            type: Array,
            default: function () {
                return [];
            },
        },
        /**
         * Наименование контрола ID# пункта выдачи
         * @type {Object}
         */
        name: {
            type: String,
            required: true,
        },
        /**
         * ID# выбранного пункта выдачи
         * @type {Object}
         */
        value: {
            required: true,
        },
        /**
         * Список активен
         * @type {Boolean}
         */
        active: {
            type: Boolean,
            default: true,
        },
    },
    data: function () {
        let translations = {
            PICKUP_POINTS: 'Пункты выдачи',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        let result = {
            translations, // Переводы
        };
        return result;
    },
}
