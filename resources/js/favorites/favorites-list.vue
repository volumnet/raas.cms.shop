<script>
import CartList from '../cart/cart-list.vue';
import FavoritesItem from './favorites-item.vue';

export default {
    mixins: [CartList],
    components: {
        'favorites-item': FavoritesItem,
    },
    template: '#favorites-list-template',
};
</script>