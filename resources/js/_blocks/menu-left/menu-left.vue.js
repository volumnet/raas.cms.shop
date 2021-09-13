/**
 * Левое меню
 */
export default {
    props: {
        /**
         * ID# страницы
         */
        pageId: {
            type: Number,
        },
        /**
         * Использовать AJAX-загрузку
         * @type {Boolean}
         */
        useAjax: {
            type: Boolean,
            default: false,
        },
    },
    mounted: function () {
        if (this.useAjax) {
            $(window).one('load', () => {
                window.setTimeout(() => {
                    this.getAJAXMenu();
                    this.ajaxLoaded = true;
                }, 50);
            });
        }
        
        $(this.$el).on('click', '.menu-left__children-trigger', function () {
            $(this)
                .closest('.menu-left__item')
                .toggleClass('menu-left__item_focused');
            return false;
        })
    },
    methods: {
        /**
         * Получает полное меню через AJAX
         */
        getAJAXMenu: function () {
            $.get(this.ajaxURL, (result) => {
                let $remoteMenu = $(result);
                let $localMenu = $(this.$el);

                let $localCatalogList = $('.menu-left__list_main', $localMenu);
                let $remoteCatalogList = $('.menu-left__list_main', $remoteMenu);
                $localCatalogList.replaceWith($remoteCatalogList);
            })
        },
    },
    computed: {
        /**
         * Путь для AJAX-запроса
         * @return {String}
         */
        ajaxURL: function () {
            return '/ajax/menu_left/?id=' + this.pageId;
        },
        /**
         * Аналог this для привязки к слоту
         * @return {Object}
         */
        self: function () {
            return { ...this };
        },
    }

}