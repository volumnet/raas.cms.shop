<script>
import CartMain from './cart-main.vue';
import FavoritesMain from './favorites-main.vue';
import AddedModal from './added-modal.vue';
import Vue from 'vue/dist/vue.js'
import CookieCart from './cookiecart.js';
import AjaxCart from './ajaxcart.js';

window.RAASShopAddedModal = Vue.component('added-modal', AddedModal);
export default {
    components: {
        'cart-main': CartMain,
        'favorites-main': FavoritesMain,
    },
    data: function () {
        const CART_TYPE_ID = 1;
        const FAVORITES_TYPE_ID = 2;
        const cookieCart = new CookieCart(CART_TYPE_ID);
        const cookieFavorites = new CookieCart(FAVORITES_TYPE_ID, true);
        const ajaxCart = new AjaxCart(
            'cart', 
            cookieCart, 
            '/ajax/cart/?AJAX=1', 
            true
        );
        const ajaxFavorites = new AjaxCart(
            'favorites', 
            cookieFavorites, 
            '/ajax/favorites/?AJAX=1'
        );
        return {
            CART_TYPE_ID: CART_TYPE_ID,
            FAVORITES_TYPE_ID: FAVORITES_TYPE_ID,
            cart: ajaxCart,
            favorites: ajaxFavorites,
            addedModal: new window.RAASShopAddedModal(),
        }
    },
    mounted: function () {
        let $addedModal = $('<div></div>');
        $('body').append($addedModal);
        this.addedModal.$mount($addedModal[0]);
    }
};
</script>