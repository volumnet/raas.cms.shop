/**
 * Получает заглушку города
 * @return {Object}
 */
const getDummy = () => {
    return { name: '', region: '', fullName: '', urn: '' };
};

/**
 * Поле города (вариант 2023 с поправкой совпадающих названий)
 * !!! НЕОБХОДИМ РУЧНОЙ ИМПОРТ raas-field, т.к. находится в другой библиотеке
 * !!! Требует hidden-полей city, region и cityURN
 * !!! Поле name не применяется
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
            default: getDummy,
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
            default() {
                return [];
            },
        },
    },
    data() {
        return {
            pValue: getDummy(), // Данные "в моменте"
            searchString: '',
            useAutocompletion: false, // Открыть список подсказок
            pristine: true, // Не меняли searchString
            autocompleteSelected: null, // Выбранная подсказка
        };
    },
    mounted() {
        this.setCity(this.value, true);
        this.$el.classList.remove('form-control');
    },
    methods: {
        /**
         * Получает полное название (с регионом) города
         * @param  {Object} city Город
         * @return {String}
         */
        getFullName(city) {
            let fullName = city.name;
            if (city.region && (city.region != city.name)) {
                fullName += ', ' + city.region;
            }
            return fullName
        },
        /**
         * Устанавливает город
         * @param {Object} city Город
         * @param {Boolean} outer Внешняя установка (если false, то внутренняя), 
         *     используется только при установке/смене value
         */
        setCity(city, outer = false) {
            this.pValue = city;
            this.useAutocompletion = false;
            this.pristine = true;
            this.searchString = this.pValue.name || '';
            if (!outer) {
                this.$emit('input', city);
            }
        },
        /**
         * Проверяет город при вводе текста
         */
        checkCity() {
            this.pristine = false;
            this.pValue = this.arbitraryCity;
            if (this.foundCity) {
                this.setCity(this.foundCity);
            } else {
                this.useAutocompletion = true;
            }
        },
        /**
         * Событие при расфокусировке поля и дочерних элементов
         * @param {Event} e Событие
         */
        onBlur(e) {
            if (!this.pristine) {
                requestAnimationFrame(() => {
                    if (!this.$el.contains(document.activeElement) && !this.$el.contains(e.relatedTarget)) {
                        if (this.allowArbitrary) {
                            this.setCity(this.pValue);
                        } else {
                            this.setCity(this.value, true);
                        }
                    }
                });
            }
        },
        /**
         * Смещает фокус по элементам подсказок
         * @param {Number} shift Смещение (1 - следующий, -1 - предыдущий)
         */
        focusAutocompleteShift(shift = 1) {
            let shiftTo = document.activeElement[((shift < 0) ? 'previous' : 'next') + 'ElementSibling'];
            if (shiftTo) {
                shiftTo.focus();
            }
        },
    },
    computed: {
        /**
         * Текущий город (pValue + fullName)
         * @return {Object}
         */
        currentCity() {
            return { ...this.pValue, fullName: this.getFullName(this.pValue) }
        },
        /**
         * Отформатированный список источника
         * @return {Object[]}
         */
        formattedSource() {
            return this.source.map(x => {
                return { ...x, fullName: this.getFullName(x) };
            });
        },
        /**
         * Найденный город (однозначное соответствие)
         * @return {Object|null}
         */
        foundCity() {
            // Минимальная длина поисковой строки уже учтена в matches
            if (this.matches.length == 1) {
                // поисковая строка совпадает с названием города
                // Либо в случае если есть запятая, не раньше 4-го символа поисковой строки 
                // (подходит по области без строго соответствия)
                if ((this.matches[0].name.toLowerCase() == this.searchString.trim().toLowerCase()) || 
                    (this.searchString.indexOf(',') >= 3)
                ) {
                    return this.matches[0]; 
                }
            }
            return null;
        },
        /**
         * Произвольный город из поисковой строки
         * @return {Object}
         */
        dummyCity() {
            return { ...getDummy() };
        },
        /**
         * Произвольный город из поисковой строки
         * @return {Object}
         */
        arbitraryCity() {
            return { ...this.dummyCity, name: this.searchString, fullName: this.searchString };
        },
        /**
         * Совпадения по вводу
         * @return {Object[]}
         */
        matches() {
            let searchString = (this.searchString || '').trim().toLowerCase();
            if (!searchString || searchString.length < 3) {
                return []; // Если искомое название города меньше 3 символов, подсказки не выводим
            }

            let result = this.formattedSource.filter((x) => {
                return x.fullName.toLowerCase().indexOf(searchString) == 0;
            }).map((x) => {
                let searchArr = searchString.split(',').map(x => x.trim());
                let searchCity = searchArr[0];
                let searchRegion = searchArr.slice(1).join(', ');

                let cityHint, regionHint;

                let i = x.name.toLowerCase().indexOf(searchCity);
                if (i != -1) {
                    cityHint = x.name.substring(0, i) 
                        + '<em>' + x.name.substring(i, i + searchCity.length) + '</em>' 
                        + x.name.substring(i + searchCity.length);
                } else {
                    cityHint = x.name;
                }

                let j = x.region.toLowerCase().indexOf(searchRegion);
                if (j != -1) {
                    regionHint = x.region.substring(0, j) 
                        + '<em>' + x.region.substring(j, j + searchRegion.length) + '</em>' 
                        + x.region.substring(j + searchRegion.length);
                } else {
                    regionHint = x.region;
                }
                return { ...x, cityHint, regionHint };
            });
            return result;
        },
        /**
         * Слушатели событий полей (с учетом v-model)
         * @return {Object}
         */
        inputListeners() {
            let result = {...this.$listeners};
            delete result.input;
            return result;
        },
    },
    watch: {
        value(newVal, oldVal) {
            if ((newVal.name != oldVal.name) || (newVal.region != oldVal.region) || (newVal.urn != oldVal.urn)) {
                this.setCity(this.value, true);
            }
        },
    },
}