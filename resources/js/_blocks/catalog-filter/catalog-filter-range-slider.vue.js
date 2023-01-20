/**
 * Слайдер диапазона фильтра
 */
export default {
    props: {
        /**
         * Минимальное значение
         * @type {Number}
         */
        min: {
            type: Number,
            default: 0,
        },
        /**
         * Максимальное значение
         * @type {Number}
         */
        max: {
            type: Number,
            default: 0,
        },
        /**
         * Шаг
         * @type {Number}
         */
        step: {
            type: Number,
            default: 0,
        },
        /**
         * Значение от
         * @type {Number}
         */
        valueFrom: {
            type: Number,
            required: false,
        },
        /**
         * Значение до
         * @type {Number}
         */
        valueTo: {
            type: Number,
            required: false,
        },
    },
    mounted() {
        var $slider = $(this.$el);
        var sliderOptions = {
            range: true,
            min: parseFloat(this.min) || 0,
            max: parseFloat(this.max) || 0,
            step: parseFloat(this.step) || 0,
            values: this.values,
            slide: (event, ui) => {
                this.$emit('input', {
                    index: $slider.find('.ui-slider-handle').index(ui.handle),
                    value: ui.value,
                    target: event.target,
                });
            },
        };
        $slider.slider(sliderOptions)
    },
    computed: {
        /**
         * Значения от/до
         * @return {[Number, Number]}
         */
        values() {
            var values = [
                parseFloat(this.valueFrom) || parseFloat(this.min) || 0,
                parseFloat(this.valueTo) || parseFloat(this.max) || 0
            ];
            return values;
        }
    },
    watch: {
        valueFrom() {
            $(this.$el).slider('values', this.values);
        },
        valueTo() {
            $(this.$el).slider('values', this.values);
        },
    }
};