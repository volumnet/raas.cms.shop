/**
 * Компонент сравнения
 */
export default {
    props: {
        /**
         * Корзина сравнения
         * @type {Object}
         */
        cart: {
            type: Object,
            required: true,
        },
    },
    data: function () {
        let translations = {
            CLEAR_COMPARE_GROUP: 'Очистить группу',
            COMPARE_IS_LOADING: 'Сравнение загружается...',
            YOUR_COMPARE_LIST_IS_EMPTY: 'Пока ни одного товара не добавлено в сравнение',
            ARE_YOU_SURE_TO_DELETE_COMPARE_GROUP: 'Вы действительно хотите очистить группу сравнения?',
        };
        if (typeof window.translations == 'object') {
            Object.assign(translations, window.translations);
        }
        return {
            translations, // Переводы
            sort: {
                fieldId: 0, // ID# поля, по которому производится сортировка
                desc: false // Сортировка от большего к меньшему
            }, // Сортировка
            activeGroupId: null, // ID# активной группы
            offset: {
                left: 0, // Левое смещение
                right: 0, // Правое смещение
            }, // Смещение таблиц
            headerFixed: false, // Шапка зафиксирована
        };
    },
    mounted: function () {
        if (this.shownGroups.length > 0) {
            this.activeGroupId = this.shownGroups[0].id;
        }
        $(window)
            .on('load', () => {
                window.setTimeout(() => {
                    this.checkWindowPosition();
                }, 10)
            })
            .on('resize', this.checkWindowPosition.bind(this))
            .on('scroll', () => {
                window.setTimeout(() => {
                    this.checkWindowPosition();
                }, 10)
            });
        $(document).on('raas.shop.cart-updated', () => {
            this.checkActiveGroup();
        });
    },
    methods: {
        /**
         * Сверяет позицию окна
         */
        checkWindowPosition: function () {
            if ($(this.$refs.itemscontainer).length) {
                this.headerFixed = (this.$root.scrollTop > $(this.$refs.itemscontainer).offset().top);
            } else {
                this.headerFixed = false;
            }
        },
        /**
         * Запрашивает и удаляет текущую группу
         */
        requestGroupDelete: function () {
            this.$root.confirm(this.translations.ARE_YOU_SURE_TO_DELETE_COMPARE_GROUP)
                .then(() => {
                    if (this.activeGroup.itemsIds) {
                        this.cart.update('action=deleteGroup&id=' + this.activeGroupId);
                    }
                });
        },
        /**
         * Сортирует товары по полю (переключает по возрастанию или по убыванию)
         * @param  {Number} fieldId ID# поля
         */
        doSort: function (fieldId) {
            if (this.sort.fieldId == fieldId) {
                this.sort.desc = !this.sort.desc;
            } else {
                this.sort.fieldId = fieldId;
                this.sort.desc = false;
            }
            this.offset = {
                left: 0,
                right: 0,
            }
            $(this.$el).trigger('raas.shop.compare-sorted');
        },
        /**
         * Проверяет активную группу, при необходимости ставит новую
         */
        checkActiveGroup: function () {
            let groups = this.shownGroups.filter(group => group.id == this.activeGroupId);
            if (!groups.length) {
                if (this.shownGroups.length) {
                    this.activeGroupId = this.shownGroups[0].id;
                }
            }
        },
        
    },    
    computed: {
        /**
         * Отображаемые группы
         * @return {Array} <pre><code>array<{
         *     id: ID# группы,
         *     name: Наименование группы
         * }></code></pre>
         */
        shownGroups: function () {
            if (!(this.cart.additional && this.cart.additional.groups)) {
                return [];
            }
            let result = Object.values(this.cart.additional.groups).filter(group => {
                let groupItemsIds = group.itemsIds.slice(0);
                let matchingGoods = this.cart.items.filter(item => {
                    return groupItemsIds.indexOf(item.id) != -1;
                });
                return matchingGoods.length > 0;
            });
            return result;
        },
        /**
         * Активная группа
         * @return {Object} <pre><code>{
         *     id: ID# группы,
         *     name: Наименование группы
         * }</code></pre>
         */
        activeGroup: function () {
            return this.cart.additional.groups[this.activeGroupId];
        },
        /**
         * Товары данной группы, отсортированные в соответствии с настройками
         * @return {Object[]}
         */
        sortedItems: function () {
            let result = this.cart.items.filter(item => {
                if (this.activeGroup && this.activeGroup.itemsIds) {
                    return this.activeGroup.itemsIds.indexOf(item.id) !== -1;
                }
                return false;
            });
            if (this.sort.fieldId) {
                result.sort((itemA, itemB) => {
                    let valueA = (itemA.props[this.sort.fieldId] && itemA.props[this.sort.fieldId][0]) || '';
                    let valueB = (itemB.props[this.sort.fieldId] && itemB.props[this.sort.fieldId][0]) || '';
                    if ((typeof(valueA) == 'object') && valueA.text) {
                        valueA = valueA.text;
                    }
                    if ((typeof(valueB) == 'object') && valueB.text) {
                        valueB = valueB.text;
                    }
                    let result = 0;
                    if (valueA < valueB) {
                        result = -1;
                    } else if (valueA > valueB) {
                        result = 1;
                    }
                    if (this.sort.desc) {
                        result = -1 * result;
                    }
                    return result;
                });
            }
            return result;
        },
        /**
         * Товары по сторонам (без учета смещения)
         * @return {Object} <pre><code>{
         *     'left'|'right'[] Сторона => array<Object> товары
         * }</code></pre>
         */
        sideItems: function () {
            let left = this.sortedItems;
            let right = this.sortedItems;
            if (this.sortedItems.length > 1) {
                right = right.slice(1).concat(right.slice(0, 1));
            }
            let result = { left, right };
            return result;
        },
        /**
         * Товары по сторонам (с учетом смещения)
         * @return {Object} <pre><code>{
         *     'left'|'right'[] Сторона => array<Object> товары
         * }</code></pre>
         */
        sideItemsShifted: function () {
            let result = { 
                left: [], 
                right: [] 
            };
            for (let side of ['left', 'right']) {
                let sideResult = this.sideItems[side];
                sideResult = sideResult.slice(this.offset[side]).concat(
                    sideResult.slice(0, this.offset[side])
                );
                result[side] = sideResult;
            }
            return result;
        },
    },
};