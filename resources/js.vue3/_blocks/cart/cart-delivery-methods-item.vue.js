/**
 * Компонент способа доставки
 */
export default {
    props: {
        /**
         * Метод доставки
         * @type {Object} <pre><code>{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }</code></pre>
         */
        method: {
            type: Object,
            required: true,
        },
        /**
         * Наименование контрола доставки
         * @type {Object}
         */
        name: {
            type: String,
            required: true,
        },
        /**
         * ID# выбранной доставки
         * @type {Object}
         */
        modelValue: {
            required: true,
        },
    },
    data() {
        let translations = {
            DAYS_0: 'дней',
            DAYS_1: 'день',
            DAYS_2: 'дня',
            FOR_FREE: 'Бесплатно',
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
         * Даты доставки
         * @return {String}
         */
        dates() {
            let currentMoment = window.moment().startOf('day');
            let dates = [];
            let suffix;
            if (this.method.dateFrom) {
                let diffFrom = window.moment(this.method.dateFrom).diff(currentMoment, 'days');
                dates.push(diffFrom);
            }
            if (this.method.dateTo && (this.method.dateTo != this.method.dateFrom)) {
                let diffTo = window.moment(this.method.dateTo).diff(currentMoment, 'days');
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
