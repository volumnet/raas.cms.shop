<script>
import CatalogFilterProperty from './catalog-filter-property.vue';

/**
 * Список свойств фильтра
 */
export default {
    props: [
        'properties', // Свойства 
        'multiple', // Можно выбирать несколько значений одного свойств 
        'data', // Текущие данные по свойствам 
        'filter' // Данные фильтрации
    ],
    template: '#catalog-filter-properties-list-template',
    components: {
        'catalog-filter-property': CatalogFilterProperty,
    },
};
</script>