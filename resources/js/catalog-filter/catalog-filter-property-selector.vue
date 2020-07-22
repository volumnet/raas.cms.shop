<script>
/**
 * Выпадающее меню в фильтре
 */
export default {
    props: [
        'property', // Свойство 
        'values', // Данные по значениям 
        'data' // Общие данные фильтра
    ],
    template: '#catalog-filter-property-selector-template',
};
</script>