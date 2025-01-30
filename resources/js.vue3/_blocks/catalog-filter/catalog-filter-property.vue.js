/**
 * Свойство фильтра
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
         * Можно выбирать несколько значений
         * @type {Boolean}
         */
        multiple: {
            type: Boolean,
            default: true,
        },
        /**
         * Источник данных фильтра
         * @type {Object} <pre><code>{ 
         *     String[] URN характеристики: mixed Значения
         * }</code></pre> GET-параметры
         */
        formData: {
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
    emits: ['update:modelValue', 'setactiveelement'],
    data() {
        let translations = {
            _YES: 'да',
            _NO: 'нет',
            DOESNT_MATTER: 'не важно',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            active: (this.filter[this.property.id + '']), // Свойство активно
            translations,
        };
    },
    mounted() {
        // var $inner = $('[data-raas-role="catalog-filter-property__inner"]', this.$el);
        // if (this.active) {
        //     $inner.show();
        // }
    },
    methods: {
        /**
         * Разворачивает/скрывает свойство
         */
        toggle() {
            this.active = !this.active;
            // var $inner = $('[data-raas-role="catalog-filter-property__inner"]', this.$el);
            // window.setTimeout(() => {
            //     if (this.active) {
            //         $inner.slideDown();
            //     } else {
            //         $inner.slideUp();
            //     }
            //   }, 0)
        }
    },
    computed: {
        /**
         * Реальный набор свойств для отображения
         * @return {Array}
         */
        realValues() {
            if ((this.property.datatype == 'checkbox') && !this.property.multiple) {
                var values = [];
                // console.log(this.property)
                if (this.multiple) {
                    values.push({
                        enabled: true,
                        value: '',
                        doRich: this.translations.DOESNT_MATTER,
                        checked: !this.filter[this.property.id] || !this.filter[this.property.id].length,
                    });
                }
                var affectedValues = [];
                Object.values(this.property.values).map(function (value) {
                    value.doRich = this.translations[parseInt(value.value) ? '_YES' : '_NO'];
                    affectedValues.push(parseInt(value.value));
                    values.push(value);
                });
                for (var i = 1; i >= 0; i--) {
                    if (affectedValues.indexOf(i) == -1) {
                        values.push({
                            enabled: false,
                            value: i + '',
                            doRich: this.translations[parseInt(i) ? '_YES' : '_NO'],
                            checked: false,
                        })
                    }
                }
                return values;
            }
            values = Object.values(this.property.values).sort((a, b) => {
                let aRich = (a.doRich || a) + '';
                let bRich = (b.doRich || b) + '';
                let aNum, bNum, regs;
                if (regs = /^(\d|,|\.)+/gi.exec(aRich)) {
                    aNum = parseFloat(regs[0]);
                }
                if (regs = /^(\d|,|\.)+/gi.exec(bRich)) {
                    bNum = parseFloat(regs[0]);
                }
                if (aNum && bNum) {
                    return Math.ceil(aNum * 100) - Math.ceil(bNum * 100);
                }
                if (aNum) {
                    return -1;
                }
                if (bNum) {
                    return 1;
                }
                return aRich.localeCompare(bRich);
            });
            return values;
        }
    },
};