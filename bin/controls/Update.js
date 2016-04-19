/**
 * Displays the variable and the control can update the translation variable
 *
 * @module package/quiqqer/translator/bin/controls/Create
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @require Ajax
 * @require Locale
 * @require css!package/quiqqer/translator/bin/controls/Update.css
 *
 * @event onChange
 */
define('package/quiqqer/translator/bin/controls/Update', [

    'qui/QUI',
    'qui/controls/Control',
    'qui/controls/buttons/Button',
    'Ajax',
    'Locale',
    'package/quiqqer/translator/bin/classes/Translator',
    'package/quiqqer/translator/bin/controls/Create',

    'css!package/quiqqer/translator/bin/controls/Update.css'

], function (QUI, QUIControl, QUIButton, QUIAjax, QUILocale, Translator, Create) {
    "use strict";

    var Translate = new Translator();

    return new Class({

        Extends: Create,
        Type   : 'package/quiqqer/translator/bin/controls/Update',

        Binds: [
            '$onInject'
        ],

        options: {
            'group' : false,
            'var'   : false,
            datatype: 'php,js',
            html    : false,
            data    : {}
        },

        initialize: function (options) {
            this.parent(options);

            this.$Toggler = null;

            this.removeEvents('onInject');

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
                'class': 'quiqqer-translator-update',
                html   : ''
            });

            return Elm;
        },

        /**
         * event : on inject
         */
        $onInject: function () {
            var self = this,
                Elm  = this.getElm(),
                path = URL_BIN_DIR + '16x16/flags/';

            Elm.set('html', '');

            QUIAjax.get([
                'ajax_system_getAvailableLanguages',
                'package_quiqqer_translator_ajax_getVarData'
            ], function (languages, data) {

                var i, len, lang, Container;
                var current = QUILocale.getCurrent();

                // current language to the top
                languages.sort(function (a, b) {
                    if (a == current) {
                        return -1;
                    }

                    if (b == current) {
                        return 1;
                    }

                    return 0;
                });

                for (i = 0, len = languages.length; i < len; i++) {

                    lang = languages[i];

                    Container = new Element('div', {
                        'class': 'quiqqer-translator-create-entry',
                        html   : '<img src="' + path + lang + '.png" />' +
                                 '<input type="text" name="' + lang + '" />'
                    }).inject(Elm);

                    if (i > 0) {
                        Container.setStyles({
                            display: 'none',
                            opacity: 0
                        });
                    }

                    if (lang + '_edit' in data && data[lang + '_edit'] !== '') {
                        Container.getElement('input').value = data[lang + '_edit'];
                    }

                    if (lang in data && data[lang] !== '') {
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
                'package': 'quiqqer/translator',
                'group'  : this.getAttribute('group'),
                'var'    : this.getAttribute('var')
            });
        },

        /**
         * Create the translation
         *
         * @returns {Promise}
         */
        save: function () {
            var self = this,
                data = this.getData();

            data.datatype = this.getAttribute('datatype');
            data.html     = this.getAttribute('html') ? 1 : 0;

            return Translate.setTranslation(
                self.getAttribute('group'),
                self.getAttribute('var'),
                data
            ).then(function () {
                return Translate.publish(
                    self.getAttribute('group')
                );
            });
        }
    });
});
