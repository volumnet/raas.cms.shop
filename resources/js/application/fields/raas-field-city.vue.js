/**
 * Поле города
 * !!!НЕОБХОДИМ РУЧНОЙ ИМПОРТ raas-field, т.к. находится в другой библиотеке
 */
export default {
    props: {
        /**
         * Разрешить выбор не из списка
         * @type {Boolean}
         */
        allowArbitrary: {
            type: Boolean,
            default: true,
        },        
        /**
         * Значение
         */
        value: {
            default: '',
        },
        /**
         * Источник
         * @type {Array} <pre><code>array<{
         *     value: String Значение (наименование города),
         *     name: String Подпись (наименование города),
         *     region: String Наименование региона
         * }></code></pre>
         */
        source: {
            type: Array,
            default: function () {
                return [];
            },
        },
        name: {

        }
    },
    data: function () {
        return {
            pValue: this.value || '', // Внутреннее значение
            useAutocompletion: false, // Открыть список подсказок
        };
    },
    mounted: function () {
        this.$el.classList.remove('form-control');
        $('body').on('click', () => {
            window.setTimeout(() => {
                if (!this.allowArbitrary) {
                    // console.log(this.value);
                    this.pValue = this.value;
                }
                this.useAutocompletion = false;
            }, 100);
        });
        $(this.$refs.input).on('click', (e) => {
            e.stopPropagation();
        });
        $(this.$refs.field).on('blur', (e) => {
            if (this.allowArbitrary) {
                this.change(e.target.value, true, false)
            }
        });

    },
    updated: function () {
        this.$el.classList.remove('form-control');
    },
    methods: {
        /**
         * Изменяет значение города
         * @param {String} value Новое значение
         * @param {Boolean} useAutocompletion Открыть список выбора
         * @param {Boolean} emitOuter Сгенерировать внешнее событие (jQuery)
         */
        change: function (value, useAutocompletion = false, emitOuter = false) {
            let found;
            // console.log(value, useAutocompletion, emitOuter)
            if (found = this.getCityByName(value)) {
                value = found.name;
            }
            this.pValue = value || '';
            // console.log(this.pValue)
            this.useAutocompletion = useAutocompletion;
            // console.log(this.allowArbitrary, value, useAutocompletion, found)
            if (this.allowArbitrary || !value || (!useAutocompletion && found)) {
                this.$emit('input', value);
                this.$emit('citychange', this.selectedCity);
                // console.log(value)
                // console.log($(this.$refs.field)[0])
                if (emitOuter) {
                    $(this.$refs.field).trigger('change');
                }
            }
        },
        getMatches: function (name) {
            let searchString = name.trim().toLowerCase();
            if ((searchString.length < 3) || (this.selectedCity && this.allowArbitrary)) {
                return [];
            }
            let result = this.source.filter((x) => {
                return x.value.toLowerCase().indexOf(searchString) != -1;
            }).map((x) => {
                let y = Object.assign({}, x);
                let i = x.value.toLowerCase().indexOf(searchString);
                let html = y.value.substring(0, i) 
                    + '<em>' + y.value.substring(i, i + searchString.length) + '</em>' 
                    + y.value.substring(i + searchString.length);
                y.hint = html;
                return y;
            });
            // console.log(result)
            return result;
        },
        getCityByName: function (name) {
            let matching = this.source.filter((x) => {
                return (x.value.trim().toLowerCase() == name.trim().toLowerCase());
            });
            if (matching.length > 0) {
                return matching[0];
            }
            return null;
        }
    },
    computed: {
        /**
         * Выбранный город
         * @return {Object} <pre><code>{
         *     value: String Значение (наименование города),
         *     name: String Подпись (наименование города),
         *     region: String Наименование региона
         * }</code></pre>
         */
        selectedCity: function () {
            return this.getCityByName(this.pValue || '');
        },
        /**
         * Полное название (с регионом) выбранного города
         * @return {String}
         */
        selectedCityFullName: function () {
            if (!this.selectedCity) {
                return '';
            }
            let result = this.selectedCity.name;
            if (this.selectedCity.region && 
                (this.selectedCity.region != this.selectedCity.name)
            ) {
                result += ', ' + this.selectedCity.region;
            }
            return result;
        },
        /**
         * Совпадения по вводу
         * @return {Array} <pre><code>array<{
         *     value: String Значение (наименование города),
         *     name: String Подпись (наименование города),
         *     region: String Наименование региона
         * }></code></pre>
         */
        matches: function () {
            return this.getMatches(this.pValue);
        },
        /**
         * Слушатели событий полей (с учетом v-model)
         * @return {Object}
         */
        inputListeners: function () {
            return Object.assign({}, this.$listeners, {
                input: (event) => {
                    this.change(event.target.value, true, false);
                },
            });
        },

    },
    watch: {
        value: function () {
            this.pValue = this.value || '';
        }
    },
}