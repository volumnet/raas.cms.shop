<script>
/**
 * Маркер предпросмотра фильтра
 */
export default {
    props: [
        'counter', // Счетчик товаров 
        'active', // Счетчик активен 
        'float', // Плавающий счетчик 
        'lastactiveelement' // Последний активный элемент (для привязки)
    ],
    template: '#catalog-filter-preview-marker-template',
    data: function () {
        return {
            top: 0,
        }
    },
    updated: function () {
        if (this.lastactiveelement && this.float) {
            $(this.$el).css('top', 0);
            var neutralMarkerOffset = $(this.$el).offset().top;
            var markerHeight = $(this.$el).outerHeight();
            var objOffset = $(this.lastactiveelement).offset().top;
            var objHeight = $(this.lastactiveelement).outerHeight();

            var dy = objOffset - neutralMarkerOffset + ((objHeight - markerHeight) / 2);
            $(this.$el).css('top', dy);
        }
    },
    methods: {
        /**
         * Склонение числительных
         * @param  {Number} num Число для склонения
         * @param  {Array} texts Числительные в последовательности (0, 1, 2) - 
         *                       например, ["товаров", "товар", "товара"]
         * @return {String}
         */
        numTxt: function (num, texts) {
            return window.numTxt(num, texts);
        },
    },
};
</script>