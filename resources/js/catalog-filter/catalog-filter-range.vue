<script>
import CatalogFilterRangeSlider from './catalog-filter-range-slider.vue';

/**
 * Диапазон фильтра
 */
export default {
    props: [
        'property', // Данные свойства 
        'data', // Данные по всем свойствам 
        'filter' // Данные фильтрации
    ],
    template: '#catalog-filter-range-template',
    components: {
        'catalog-filter-range-slider': CatalogFilterRangeSlider,
    },
    data: function () {
        return {
            valuefrom: '',
            valueto: '',
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
            this.valuefrom = (this.filter[this.property.id + ''] && parseFloat(this.filter[this.property.id + '']['from'])) || '';
            this.valueto = (this.filter[this.property.id + ''] && parseFloat(this.filter[this.property.id + '']['to'])) || '';
        },

        /**
         * Изменение через поле
         * @param  {Event} event Событие изменения
         */
        changeByInput: function (event) {
            if (this.valuefrom) {
                this.valuefrom = parseFloat(this.valuefrom) || 0;
                this.valuefrom = Math.min(this.valuefrom, this.max);
                this.valuefrom = Math.max(this.valuefrom, this.min);
            }
            if (this.valueto) {
                this.valueto = parseFloat(this.valueto) || 0;
                this.valueto = Math.min(this.valueto, this.max);
                this.valueto = Math.max(this.valueto, this.min);
            }
            // this.$forceUpdate();
            this.$emit('change', event);
        },

        /**
         * Изменение через слайдер
         * @param  {Event} event Событие изменения
         */
        changeBySlider: function (event) {
            var val = event.value;
            switch (event.index) {
                case 0:
                    this.valuefrom = val;
                    break;
                case 1:
                    this.valueto = val;
                    break;
            }
            this.$emit('change', event);
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
</script>