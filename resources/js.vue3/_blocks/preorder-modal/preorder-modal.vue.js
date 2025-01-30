/**
 * Форма предзаказа товара
 * @requires AJAXForm
 * @requires AJAXFormStandalone
 */
export default {
    props: {
        /**
         * Заголовок формы
         * @type {String}
         */
        title: {
            type: String,
            default: 'Предзаказ товара',
        },
    },
    data() {
        return {
            item: null,
        };
    },
    methods: {
        show(item, description = '') {
            if (item) {
                this.success = false;
                this.loading = false;
                this.errors = {};
                this.formData._description_ = '';
                this.item = item;
                this.formData._description_ = description;
                $(this.$el).modal('show');
            }
        },
    },
    computed: {
        materialUrl() {
            if (this.item && this.item.url && this.item.url != window.location.pathname) {
                return this.item.url;
            }
            return '#';
        },
    },
}
