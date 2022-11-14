/**
 * Компонент блока формы корзины
 */
export default {
    props: {
        /**
         * Заголовок блока
         * @type {String}
         */
        title: {
            type: String,
            required: true,
        },
        /**
         * Статус блока
         * @type {Number} <pre><code>
         *    < 0 - уже прошли блок
         *    0 - текущий
         *    > 0 - еще не дошли до него
         * </code></pre>
         */
        status: {
            type: Number,
            required: true,
        },
        /**
         * Заголовок кнопки "Далее"
         * @type {String}
         */
        submitTitle: {
            type: String,
        },
        /**
         * Форма загружается
         * @type {Boolean}
         */
        loading: {
            type: Boolean,
            default: false,
        },
        /**
         * Условие валидности для активации кнопки
         * @type {Boolean}
         */
        validity: {
            type: Boolean,
            default: true,
        },
        /**
         * Последний блок (кнопка отправляет форму)
         * @type {Boolean}
         */
        lastBlock: {
            type: Boolean,
            default: false,
        },
    },
    data: function () {
        let translations = {
            EDIT: 'Редактировать',
            GO_NEXT: 'Далее',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
        };
    },
    methods: {
        /**
         * Проверяет правильность заполнения полей
         * @param {Boolean} silent Проверить втихую и выдать значение
         * @return {Boolean}
         */
        validate: function (silent = false) {
            let $fields = $('input, select, textarea', this.$el);
            let valid = true;
            $fields.each(function () {
                let result = silent ? this.validity.valid : this.reportValidity();
                if (!result) {
                    valid = false;
                    return false;
                }
            });
            return valid;
        },
        /**
         * Проверяет правильность заполнения полей, если всё правильно, выбрасывает событие submit
         */
        doProcess: function () {
            if (this.validate()) {
                this.$emit('submit');
            }
        },
    },
}