<script>
import CatalogItem from './catalog-item.vue';

export default {
    mixins: [CatalogItem],
    data: function () {
        return {
            selectedImage: 0, // Выбранное изображение
        }
    },
}
</script>