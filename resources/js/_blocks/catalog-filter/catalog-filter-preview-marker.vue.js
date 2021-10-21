/**
 * Маркер предпросмотра фильтра
 */
export default {
    props: {
        /**
         * Счетчик товаров
         * @type {Number}
         */
        counter: {
            type: Number,
            default: 0,
        },
        /**
         * Маркер активен
         * @type {Boolean}
         */
        active: {
            type: Boolean,
            default: false,
        },
        /**
         * Маркер плавающий
         * @type {Boolean}
         */
        float: {
            type: Boolean,
            default: true
        },
        /**
         * Последний активный элемент
         * @type {HTMLElement}
         */
        lastActiveElement: {
            type: HTMLElement,
            required: false,
        },
    },
    data: function () {
        let translations = {
            FOUND: 'Найдено',
            FOUND_0_ITEMS: 'товаров',
            FOUND_1_ITEM: 'товар',
            FOUND_2_ITEMS: 'товара',
            SHOW: 'Показать',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
        }
    },
    updated: function () {
        if (this.lastActiveElement && this.float) {
            // console.log(this.lastActiveElement)
            $(this.$el).css('top', 0);
            var neutralMarkerOffset = $(this.$el).offset().top;
            var markerHeight = $(this.$el).outerHeight();
            var objOffset = $(this.lastActiveElement).offset().top;
            var objHeight = $(this.lastActiveElement).outerHeight();

            var dy = objOffset - neutralMarkerOffset + ((objHeight - markerHeight) / 2);
            $(this.$el).css('top', dy);
        }
    },
    methods: {
        /**
         * Склонение числительных
         * @param  {Number} num Число для склонения
         * @param  {Array} texts Числительные в последовательности (0, 1, 2) - 
         *     например, ["товаров", "товар", "товара"]
         * @return {String}
         */
        numTxt: function (num, texts) {
            return window.numTxt(num, texts);
        },
    },
};