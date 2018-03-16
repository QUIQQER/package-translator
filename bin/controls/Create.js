/**
 * Create new translation variable
 *
 * @module package/quiqqer/translator/bin/controls/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @event onChange
 */
define('package/quiqqer/translator/bin/controls/Create', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale',
    'package/quiqqer/translator/bin/classes/Translator',

    'css!package/quiqqer/translator/bin/controls/Create.css'

], function (QUI, QUIControl, QUIButton, QUIAjax, QUILocale, Translator) {
    "use strict";

    var Translate = new Translator();

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/translator/bin/controls/Create',

        Binds: [
            '$onInject',
            'toggle',
            'open',
            'close'
        ],

        options: {
            'group'  : false,
            'var'    : false,
            'package': false,
            datatype : 'php,js',
            html     : false,
            data     : {}
        },

        initialize: function (options) {
            this.parent(options);

            this.$Toggler = null;

            this.addEvents({
                onInject: this.$onInject
            });
        },

        /**
         * Return the domnode element
         *
         * @returns {HTMLElement}
         */
        create: function () {
            var Elm = this.parent();

            Elm.set({
                'class': 'quiqqer-translator-create',
                html   : ''
            });

            return Elm;
        },

        /**
         * Create the translation
         *
         * @returns {Promise}
         */
        createTranslation: function () {

            var self = this,
                data = this.getData();

            data.package  = this.getAttribute('package');
            data.datatype = this.getAttribute('datatype');
            data.html     = this.getAttribute('html') ? 1 : 0;

            return Translate.add(
                this.getAttribute('group'),
                this.getAttribute('var'),
                this.getAttribute('package')
            ).then(function () {
                return Translate.setTranslation(
                    self.getAttribute('group'),
                    self.getAttribute('var'),
                    data
                );
            }).then(function () {
                return Translate.refreshLocale();

            }).then(function () {
                return Translate.publish(
                    self.getAttribute('group')
                );

            }).catch(function () {

                return Translate.setTranslation(
                    self.getAttribute('group'),
                    self.getAttribute('var'),
                    data
                ).then(function () {
                    return Translate.refreshLocale();

                }).then(function () {
                    return Translate.publish(
                        self.getAttribute('group')
                    );
                });
            });
        },

        /**
         * Save method -> same same as by Update
         * @returns {*|Promise}
         */
        save: function () {
            return this.createTranslation();
        },

        /**
         * Return the translation data
         *
         * @return {Object} - {en: '', de: ''}
         */
        getData: function () {
            var result = {},
                list   = this.getElm().getElements('input'),
                dev    = parseInt(QUIQQER_CONFIG.globals.development);

            for (var i = 0, len = list.length; i < len; i++) {
                result[list[i].name] = list[i].value;

                if (dev) {
                    result[list[i].name + '_edit'] = list[i].value;
                }
            }

            return result;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this,
                Elm  = this.getElm(),
                path = URL_BIN_DIR + '16x16/flags/';

            Elm.set('html', '');

            QUIAjax.get('ajax_system_getAvailableLanguages', function (languages) {

                var i, len, lang, Container;

                var current = QUILocale.getCurrent(),
                    data    = self.getAttribute('data');

                // current language to the top
                languages.sort(function (a, b) {
                    if (a === current) {
                        return -1;
                    }

                    if (b === current) {
                        return 1;
                    }

                    return 0;
                });

                for (i = 0, len = languages.length; i < len; i++) {

                    lang = languages[i];

                    Container = new Element('div', {
                        'class': 'quiqqer-translator-entry',
                        html   : '<img src="' + path + lang + '.png" />' +
                        '<input type="text" name="' + lang + '" />'
                    }).inject(Elm);

                    if (i > 0) {
                        Container.setStyles({
                            display: 'none',
                            opacity: 0
                        });
                    }

                    if (lang in data) {
                        Container.getElement('input').value = data[lang];
                    }
                }

                self.$Toggler = new QUIButton({
                    icon  : 'fa fa-arrow-circle-o-right',
                    styles: {
                        position: 'absolute',
                        right   : 0
                    },
                    events: {
                        onClick: self.toggle
                    }
                }).inject(Elm);

                Elm.getElements('input').addEvent('change', function () {
                    self.setAttribute('data', self.getData());
                    self.fireEvent('change', [self]);
                });

            }, {
                'package': 'quiqqer/translator'
            });
        },

        /**
         * Toggle the open status
         */
        toggle: function () {
            if (this.$Toggler.nodeName === 'SPAN') {
                if (this.getElm().hasClass('quiqqer-t-entry__minimize')) {
                    this.getElm().removeClass('quiqqer-t-entry__minimize');
                    this.open();
                    return;
                }

                this.close().then(function () {
                    this.getElm().addClass('quiqqer-t-entry__minimize');
                }.bind(this));
                return;
            }

            if (this.$Toggler.isActive()) {
                this.close();
                return;
            }

            this.open();
        },

        /**
         * shows all translation entries
         */
        open: function () {
            var self = this,
                list = this.getElm().getElements('.quiqqer-translator-entry');

            var First = list.shift();

            list.setStyles({
                display: null,
                height : 0
            });

            moofx(First).animate({
                height: 34
            });

            moofx(list).animate({
                height : 34,
                opacity: 1
            }, {
                duration: 200,
                callback: function () {
                    self.$Toggler.setAttribute(
                        'icon',
                        'fa fa-arrow-circle-o-down'
                    );

                    if ("setActive" in self.$Toggler) {
                        self.$Toggler.setActive();
                    }
                }
            });
        },

        /**
         * shows all translation entries
         *
         * @return {Promise}
         */
        close: function () {
            var self = this,
                list = this.getElm().getElements('.quiqqer-translator-entry');

            var First = list.shift();

            First.setStyle('height', null);

            return new Promise(function (resolve) {
                moofx(list).animate({
                    height : 0,
                    opacity: 0
                }, {
                    duration: 200,
                    callback: function () {
                        self.$Toggler.setAttribute(
                            'icon',
                            'fa fa-arrow-circle-o-right'
                        );

                        if ("setNormal" in self.$Toggler) {
                            self.$Toggler.setNormal();
                        }

                        resolve();
                    }
                });
            });
        }
    });
});
