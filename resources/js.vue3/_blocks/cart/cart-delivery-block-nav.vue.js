/**
 * Компонент навигации блока доставки
 */
export default {
    props: {
        /**
         * Типы получения
         * @type {Array} <pre><code>array<{
         *     urn: String URN способа получения,
         *     name: String Наименование способа получения,
         *     source: array<{
         *         id: Number ID# способа получения,
         *         name: String Краткое наименование,
         *         fullName: String Полное наименование,
         *         serviceURN: String URN сервиса,
         *         price?: Number Стоимость доставки,
         *         dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *         dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         *     }> Способы получения
         * }></code></pre>
         */
        source: {
            type: Array,
            required: true,
        },
        /**
         * URN текущего типа получения
         * @type {String}
         */
        modelValue: {
            type: String,
            required: true,
        }
    },
    emits: ['update:modelValue'],
}