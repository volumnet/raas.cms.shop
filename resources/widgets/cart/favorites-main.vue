<template>
  <a href="/favorites/" class="favorites-main" rel="nofollow" :class="{ 'favorites-main_active': dataLoaded }">
    <span class="favorites-main__amount" v-if="amount > 0">
      {{ amount }}
    </span>
    <span class="favorites-main__text">
      <span class="favorites-main__title">{{title}}</span>
    </span>
  </a
></template>

<script>
import CartMain from './cart-main.vue';
export default {
    props: {
        title: {
            type: String,
            default: 'Избранное',
        },
    }
    mixins: [CartMain],
};
</script>