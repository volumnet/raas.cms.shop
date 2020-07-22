<script>
import Cart from '../cart/cart.vue';
import FavoritesList from './favorites-list.vue';

export default {
    mixins: [Cart],
    components: {
        'favorites-list': FavoritesList,
    },
    data: function () {
        return window.raasShopFavoritesData;
    },
};
</script>