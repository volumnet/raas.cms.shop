<script>
import CatalogFilterRange from './catalog-filter-range.vue';
import CatalogFilterPropertyList from './catalog-filter-property-list.vue';
import CatalogFilterPropertySelector from './catalog-filter-property-selector.vue';

/**
 * Свойство фильтра
 */
export default {
    props: [
        'property', // Свойство 
        'multiple', // Можно выбирать несколько значений одного свойства 
        'data', // Данные по свойствам 
        'filter' // Данные фильтрации
    ],
    template: '#catalog-filter-property-template',
    components: {
        'catalog-filter-range': CatalogFilterRange, 
        'catalog-filter-property-list': CatalogFilterPropertyList, 
        'catalog-filter-selector': CatalogFilterPropertySelector
    },
    data: function () {
        return {
            active: (this.filter[this.property.id + '']),
        };
    },
    mounted: function () {
        var $inner = $('[data-raas-role="catalog-filter-property__inner"]', this.$el);
        if (this.active) {
            $inner.show();
        }
    },
    methods: {
        /**
         * Разворачивает/скрывает свойство
         */
        toggle: function () {
            this.active = !this.active;
            var $inner = $('[data-raas-role="catalog-filter-property__inner"]', this.$el);
            var self = this;
            window.setTimeout(function () {
                if (self.active) {
                    $inner.slideDown();
                } else {
                    $inner.slideUp();
                }
              }, 0)
        }
    },
    computed: {
        /**
         * Реальный набор свойств для отображения
         * @return {Array}
         */
        realValues: function () {
            if ((this.property.datatype == 'checkbox') && !this.property.multiple) {
                var values = [];
                // console.log(this.property)
                if (this.multiple) {
                    values.push({
                        enabled: true,
                        value: '',
                        doRich: 'не важно',
                        checked: !this.filter[this.property.id] || !this.filter[this.property.id].length,
                    });
                }
                var affectedValues = [];
                Object.values(this.property.values).map(function (value) {
                    value.doRich = parseInt(value.value) ? 'да' : 'нет';
                    affectedValues.push(parseInt(value.value));
                    values.push(value);
                });
                for (var i = 1; i >= 0; i--) {
                    if (affectedValues.indexOf(i) == -1) {
                        values.push({
                            enabled: false,
                            value: i + '',
                            doRich: parseInt(i) ? 'да' : 'нет',
                            checked: false,
                        })
                    }
                }
                return values;
            }
            return this.property.values;
        }
    },
};
</script>