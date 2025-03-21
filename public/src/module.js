import priceloaderComponents from './components/priceloader';
import EditOrderItems from './application/raas-repo/edit-order-items.vue';

window.raasComponents = {
    ...window.raasComponents,
    ...priceloaderComponents,
    'cms-shop-edit-order-items': EditOrderItems,
};