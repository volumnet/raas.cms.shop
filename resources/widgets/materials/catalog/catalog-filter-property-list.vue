<script>
import CatalogFilterPropertyValue from './catalog-filter-property-value.vue';

/**
 * Список значений фильтра
 */
export default {
    props: [
        'property', // Свойство
        'values' // Данные по значениям
    ],
    template: '#catalog-filter-property-list-template',
    components: {
        'catalog-filter-property-value': CatalogFilterPropertyValue,
    },
};
</script>