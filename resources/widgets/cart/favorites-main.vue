<script>
import CartMain from './cart-main.vue';
export default {
    props: {
        title: {
            type: String,
            default: 'Избранное',
        },
        remoteCartId: {
            type: String,
            default: 'favorites'
        },
    },
    template: '#favorites-main-template',
    mixins: [CartMain],
};
</script>