/**
 * Диапазон фильтра
 */
export default {
    props: {
        /**
         * Свойство
         * @type {Object} <pre><code>{
         *     id: Number ID# свойства,
         *     urn: String URN свойства,
         *     datatype: String Тип данных свойства,
         *     multiple: Number|Boolean Множественное ли свойство,
         *     name: String Наименование свойства,
         *     priority: Number Порядок отображения
         *     stdSource: null|Array Источник данных свойства,
         *     values: {
         *         String|Number ID# значения: {
         *             enabled: Boolean Возможна ли отметка свойства,
         *             value: String|Number Значение,
         *             doRich: String Человеко-понятное значение,
         *             checked: Boolean Отмечено ли свойство
         *         }
         *     }
         * }</code></pre>
         */
        property: {
            type: Object,
            required: true,
        },
        /**
         * Источник данных фильтра
         * @type {Object} <pre><code>{ 
         *     String|Number[] ID# характеристики: array<String> Значения
         * }</code></pre> Данные фильтра
         */
        filter: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        return {
            valueFrom: null, // Значение с
            valueTo: null, // Значение по
        };
    },
    mounted: function () {
        this.init();
    },
    methods: {
        /**
         * Установка
         */
        init: function () {
            this.valueFrom = (this.filter[this.property.id + ''] && parseFloat(this.filter[this.property.id + '']['from'])) || null;
            this.valueTo = (this.filter[this.property.id + ''] && parseFloat(this.filter[this.property.id + '']['to'])) || null;
        },

        /**
         * Изменение через поле
         * @param {Event} event Событие изменения
         */
        changeByInput: function (event) {
            if (this.valueFrom) {
                this.valueFrom = parseFloat(this.valueFrom) || 0;
                this.valueFrom = Math.min(this.valueFrom, this.max);
                this.valueFrom = Math.max(this.valueFrom, this.min);
            }
            if (this.valueTo) {
                this.valueTo = parseFloat(this.valueTo) || 0;
                this.valueTo = Math.min(this.valueTo, this.max);
                this.valueTo = Math.max(this.valueTo, this.min);
            }
            // this.$forceUpdate();
            this.$emit('input', event);
        },

        /**
         * Изменение через слайдер
         * @param {Event} event Событие изменения
         */
        changeBySlider: function (event) {
            var val = event.value;
            switch (event.index) {
                case 0:
                    this.valueFrom = val;
                    break;
                case 1:
                    this.valueTo = val;
                    break;
            }
            this.$emit('input', event);
        },
    },
    computed: {
        /**
         * Доступные значения
         * @return {Array}
         */
        values: function () {
            var values = Object.keys(this.property.values || []).map(function (x) {
                return parseFloat(x) || 0;
            }).filter(function (x) {
                return x != 0;
            }).sort();
            return values;
        },

        /**
         * Минимальное значение
         * @return {Number}
         */
        min: function () {
            if (this.values.length) {
                return Math.min.apply(null, this.values) || 0;
            }
            return 0;
        },

        /**
         * Максимальное значение
         * @return {Number}
         */
        max: function () {
            if (this.values.length) {
                return Math.max.apply(null, this.values) || 0;
            }
            return 0;
        },

        /**
         * Шаг диапазона
         * @return {Number}
         */
        step: function () {
            if (this.values.length > 1) {
                var minRealStep = null;
                var delta;
                for (var i = 0; i < this.values.length - 1; i++) {
                    delta = this.values[i + 1] - this.values[i];
                    if ((delta > 0) && (!minRealStep || (delta < minRealStep))) {
                        minRealStep = delta;
                    }
                }
                var step = Math.pow(10, Math.floor(Math.log10(minRealStep)));
                return step;
            }
            return 1;
        },
    },
    watch: {
        filter: function (x) {
            this.init();
        }
    },
};