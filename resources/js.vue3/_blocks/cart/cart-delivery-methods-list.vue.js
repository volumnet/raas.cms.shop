/**
 * Компонент списка способов доставки
 */
export default {
    props: {
        /**
         * Источник данных
         * @type {Object[]} <pre><code>array<{
         *     id: Number ID# способа получения,
         *     name: String Краткое наименование,
         *     fullName: String Полное наименование,
         *     serviceURN: String URN сервиса,
         *     price?: Number Стоимость доставки,
         *     dateFrom?: String Минимальная дата доставки (ГГГГ-ММ-ДД)
         *     dateTo?: String Максимальная дата доставки (ГГГГ-ММ-ДД)
         * }></code></pre>
         */
        source: {
            type: Array,
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
    emits: ['update:modelValue'],
    mounted() {
        if (this.source.length == 1) {
            this.$emit('update:modelValue', this.source[0].id);
        }
    },
}
