/**
 * Компонент балуна пункта самовывоза на карте
 */
export default {
    props: {
        /**
         * Пункт выдачи
         * @type {Object} <pre><code>{
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
         * }</code></pre>
         */
        point: {
            type: Object,
            required: true,
        },
    },
    data() {
        let translations = {
            DELIVERY_PRICE: 'Стоимость доставки',
            DELIVERY_DATES: 'Сроки доставки',
            SCHEDULE: 'Время работы',
            SELECT: 'Выбрать',
            PHONES: 'Телефоны',
            DAYS_0: 'дней',
            DAYS_1: 'день',
            DAYS_2: 'дня',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        let result = {
            translations, // Переводы
        };
        return result;
    },
    computed: {
        /**
         * Сроки доставки
         * @return {String}
         */
        dates() {
            let currentMoment = window.moment().startOf('day');
            let dates = [];
            let suffix;
            if (this.point.dateFrom) {
                let diffFrom = window.moment(this.point.dateFrom).diff(currentMoment, 'days');
                dates.push(diffFrom);
            }
            if (this.point.dateTo && (this.point.dateTo != this.point.dateFrom)) {
                let diffTo = window.moment(this.point.dateTo).diff(currentMoment, 'days');
                dates.push(diffTo);
            }
            if (dates) {
                suffix = window.numTxt(dates[dates.length - 1], [
                    this.translations.DAYS_0, 
                    this.translations.DAYS_1, 
                    this.translations.DAYS_2
                ]);
            }
            return dates.join('–') + (suffix ? ' ' + suffix : '');
        }
    },
}
