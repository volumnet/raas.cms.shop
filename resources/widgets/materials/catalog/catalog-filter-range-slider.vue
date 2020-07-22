<script>
/**
 * Слайдер диапазона фильтра
 */
export default {
    props: [
        'min', // Минимальное значение 
        'max', // Максимальное значение 
        'step', // Шаг 
        'valuefrom', // Значение от 
        'valueto' // Значение до
    ],
    template: '#catalog-filter-range-slider-template',
    mounted: function () {
        var self = this;
        var $slider = $(this.$el);
        var sliderOptions = {
            range: true,
            min: parseFloat(this.min) || 0,
            max: parseFloat(this.max) || 0,
            step: parseFloat(this.step) || 0,
            values: this.values,
            slide: function(event, ui) {
                self.$emit('change', {
                    index: $slider.find('.ui-slider-handle').index(ui.handle),
                    value: ui.value,
                    target: event.target,
                });
            },
        };
        $slider.slider(sliderOptions)
    },
    updated: function () {
        $(this.$el).slider('values', this.values);
    },
    computed: {
        /**
         * Значения от/до
         * @return {[Number, Number]}
         */
        values: function () {
            var values = [
                parseFloat(this.valuefrom) || parseFloat(this.min) || 0,
                parseFloat(this.valueto) || parseFloat(this.max) || 0
            ];
            return values;
        }
    },
};
</script>