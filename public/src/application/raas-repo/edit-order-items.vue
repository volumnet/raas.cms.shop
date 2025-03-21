<style lang="scss">
.cms-shop-edit-order-items {
    &__add {
        &:before {
            @include fa('plus');
        }
    }
}
</style>

<template>
  <div data-role="multitable">
    <table class="cms-shop-edit-order-items raas-repo-table table table-striped">
      <thead>
        <tr>
          <th>
            <input type="checkbox" data-role="checkbox-all" value="all">
          </th>
          <th>{{ $root.translations.MATERIAL }}</th>
          <th>{{ $root.translations.NAME }}</th>
          <th>{{ $root.translations.ADDITIONAL_INFO }}</th>
          <th>{{ $root.translations.PRICE }}</th>
          <th>{{ $root.translations.AMOUNT }}</th>
          <th>{{ $root.translations.SUM }}</th>
          <th></th>
        </tr>
      </thead>
      <raas-repo-table-list 
        class="raas-repo-table__list" 
        :horizontal="horizontal" 
        :sortable="sortable && (items.length > 1)" 
        :required="required" 
        :model-value="items" 
        @update:model-value="changeItem($event);"
        @sort="sortable && sort($event)" 
        @delete="deleteItem($event);" 
        v-slot="repo"
      >
        <td>
          <template v-if="repo.modelValue.material && repo.modelValue.material.id">
            <input type="checkbox" data-role="checkbox-row" :value="repo.modelValue.material.id + '_' + repo.modelValue.meta">
          </template>
        </td>
        <td>
          <raas-field-ajax 
            name="material[]"
            :autocomplete-url="'ajax.php?p=cms&m=shop&sub=main&action=get_materials_by_field&cart_type=' + cartTypeId + '&search_string='"
            :model-value="repo.modelValue.material"
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, material: $event })"
          ></raas-field-ajax>
        </td>
        <td>
          <raas-field-text 
            class="span2" 
            name="material_name[]" 
            :model-value="repo.modelValue.name" 
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, name: $event })"
          />
        </td>
        <td>
          <raas-field-text 
            class="span2" 
            name="meta[]" 
            :model-value="repo.modelValue.meta" 
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, meta: $event })"
          />
        </td>
        <td>
          <raas-field-number 
            style="width: 5em"
            name="realprice[]" 
            step="0.01"
            :model-value="repo.modelValue.realprice" 
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, realprice: $event })"
          />
        </td>
        <td>
          <raas-field-number 
            class="span1"
            name="amount[]" 
            step="0.01"
            :model-value="repo.modelValue.amount" 
            @update:model-value="repo.emit('update:modelValue', {...repo.modelValue, amount: $event })"
          />
        </td>
        <td style="text-align: right; white-space: nowrap;">
          <template v-if="repo.modelValue.realprice && repo.modelValue.amount && parseFloat(repo.modelValue.realprice) && parseFloat(repo.modelValue.amount)">
            {{ formatPrice(parseFloat(repo.modelValue.realprice) * parseFloat(repo.modelValue.amount)) }}
          </template>
        </td>
      </raas-repo-table-list>
      <tfoot class="raas-repo-table__controls">
        <tr>
          <td colspan="2">
            <all-context-menu :menu="menu"></all-context-menu>
          </td>
          <td colspan="3"></td>
          <th>{{ $root.translations.ROLLUP }}</th>
          <th style="text-align: right; white-space: nowrap;">
            {{ formatPrice(sum) }}
          </th>
          <td>
            <button type="button" class="btn cms-shop-edit-order-items__add" @click="addItem()"></button>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<script>
import Repo from 'cms/application/raas-repo/raas-repo.vue.js';

export default {
    mixins: [Repo],
    props: {
        /**
         * ID# типа корзины
         * @type {Number}
         */
        cartTypeId: {
            type: Number,
            required: true,
        },
        /**
         * Контекстное меню
         * @type {Object}
         */
        menu: {
            type: Array,
            required: true,
        },

        defval: {
            type: Object,
            default() {
                return {
                    material: null,
                    name: '',
                    meta: '',
                    realprice: '',
                    amount: '',
                };
            },
        },
        sortable: {
            type: Boolean,
            default: true,
        },
    },
    methods: {
        formatPrice(x) {
            return window.formatPrice(x);
        },
        changeItem($event) {
            const newEvent = {...$event};
            if (!($event.target.value.material && $event.target.value.material.id) && 
                ($event.value.material && $event.value.material.id)
            ) {
                newEvent.value.name = newEvent.value.material.name;
                if (newEvent.value.material.price) {
                    newEvent.value.realprice = newEvent.value.material.price;
                }
            }
            Repo.methods.changeItem.call(this, newEvent);
        },
    },
    computed: {
        /**
         * Сумма по всем товарам
         * @return {Number}
         */
        sum() {
            let result = 0;
            for (let itemValue of this.pValue) {
                if (itemValue.realprice && itemValue.amount && parseFloat(itemValue.realprice) && parseFloat(itemValue.amount)) {
                    result += parseFloat(itemValue.realprice) * parseFloat(itemValue.amount);
                }
            }
            return result;
        },
    },
};
</script>