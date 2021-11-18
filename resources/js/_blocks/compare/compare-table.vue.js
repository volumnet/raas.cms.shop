/**
 * Компонент таблицы сравнения
 */
export default {
    props: {
        /**
         * Товары данной группы, отсортированные в соответствии с настройками
         * @type {Array}
         */
        sortedItems: {
            type: Array,
            required: true,
        },
        /**
         * Распределение товаров по сторонам с учетом прокрутки
         * @type {Object} <pre><code>{
         *     String[] 'left'|'right' Сторона: Object[] Товар
         * }</code></pre>
         */
        sideItems: {
            type: Object,
            required: true,
        },
        /**
         * Поля для отображения
         * @type {Object[]}
         */
        fields: {
            type: Array,
            required: true,
        },
        /**
         * Параметры сортировки
         * @type {Object} <pre><code>{
         *     fieldId: Number ID# поля, по которому производится сортировка,
         *     desc: Boolean Сортировка от большего к меньшему
         * }</code></pre>
         */
        sort: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        let translations = {
            PROPERTIES: 'Характеристики',
            SHOW_ONLY_DIFFERENT: 'Только различающиеся',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations,
            onlyDifferentProps: false, // Отображать только отличающиеся характеристики
        };
    },
    methods: {
        /**
         * Поле уникальное по текущим товарам
         * @param  {Object} field Поле для проверки
         * @return {Boolean}
         */
        isUniqueField: function (field) {
            if (this.sortedItems.length <= 1) {
                return true;
            }
            let valuesA = JSON.stringify(
                this.sortedItems[0].props[field.id].map((x) => {
                    if (!x || (x == '0')) {
                        return '';
                    }
                    return x;
                })
            );
            for (let i = 1; i < this.sortedItems.length; i++) {
                let valuesB = JSON.stringify(
                    this.sortedItems[i].props[field.id].map((x) => {
                        if (!x || (x == '0')) {
                            return '';
                        }
                        return x;
                    })
                );
                if (valuesB != valuesA) {
                    return true;
                }
            }
            return false;
        },
        /**
         * Поле не пустое по текущим товарам
         * @param  {Object} field Поле для проверки
         * @return {Boolean}
         */
        isNotEmptyField: function (field) {
            let values = {};
            for (let item of this.sortedItems) {
                let value = item.props[field.id];
                if (!value || (value == '0')) {
                    value = '' 
                }
                if ((typeof(value) == 'object') && value.name) {
                    value = value.name;
                }
                values[value] = value;
            }
            if (field.datatype != 'checkbox') {
                delete values[''];
            }
            return Object.keys(values).length > 0;
        },
        /**
         * Характеристики товаров для отображения
         * @param {Object} field Поле для получения характеристик
         * @return {Array} <pre><code>{
         *     'left'|'right'[]: array<array<
         *         String|{
         *             href: String Ссылка,
         *             text: String Текст
         *         }
         *     >>
         * }</code></pre>
         */
        itemsValues: function (field) {
            let result = { left: [], right: [] };
            for (let side in this.sideItems) {
                for (let item of this.sideItems[side]) {
                    let itemValues = [];
                    for (let value of item.props[field.id]) {
                        if ((value == '0') && 
                            (['number', 'range'].indexOf(field.datatype) != -1)
                        ) {
                            value = '';
                        }
                        itemValues.push(value);
                    }
                    result[side].push(itemValues);
                }
            }
            return result;
        },
    },
    computed: {
        /**
         * Отображаемые поля
         * @return {Array} Массив полей
         */
        shownFields: function () {
            let result = this.fields.filter(field => this.isNotEmptyField(field));
            if (this.onlyDifferentProps) {
                result = result.filter(field => this.isUniqueField(field));
            }
            return result;
        },
    },
};