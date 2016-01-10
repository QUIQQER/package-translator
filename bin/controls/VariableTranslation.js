/**
 * Displays one translation variable
 *
 * @package package/quiqqer/translator/bin/controls/VariableTranslation
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require Ajax
 * @require utils/Panels
 * @require package/quiqqer/translator/bin/Panel
 * @require css!package/quiqqer/translator/bin/controls/VariableTranslation.css
 */
define('package/quiqqer/translator/bin/controls/VariableTranslation', [

    'qui/QUI',
    'qui/controls/Control',
    'Ajax',
    'utils/Panels',
    'package/quiqqer/translator/bin/Panel',

    'css!package/quiqqer/translator/bin/controls/VariableTranslation.css'

], function (QUI, QUIControl, QUIAjax, PanelUtils, Translator) {
    "use strict";

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/translator/bin/controls/VariableTranslation',

        Binds: [
            'edit',
            '$onCreate',
            '$onInject'
        ],

        option: {
            'group': '',
            'var'  : ''
        },

        initialize: function (options) {
            this.parent(options);

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on Create
         */
        create: function () {
            var Elm = this.parent();

            Elm.addClass('quiqqer-translator-variabletranslation');
            Elm.addClass('smooth');

            Elm.addEvent('click', this.edit);

            new Element('span', {
                'class': 'icon-spinner icon-spin fa fa-spinner fa-spin'
            }).inject(Elm);

            return Elm;
        },

        /**
         * event on inject
         */
        $onInject: function () {

            var self = this;

            QUIAjax.get([
                'ajax_system_getAvailableLanguages',
                'package_quiqqer_translator_ajax_getVarData'
            ], function (languages, data) {

                var i, len, lang, text;
                var Container = new Element('div'),
                    path      = URL_BIN_DIR + '16x16/flags/';

                for (i = 0, len = languages.length; i < len; i++) {
                    lang = languages[i];
                    text = data[lang] || '--';

                    new Element('span', {
                        'class': 'quiqqer-translator-variabletranslation-entry',
                        html   : '<img src="' + path + lang + '.png" />' +
                                 text
                    }).inject(Container);
                }

                Container.inject(
                    self.getElm().set('html', '')
                );
            }, {
                'package': 'quiqqer/translator',
                'group'  : this.getAttribute('group'),
                'var'    : this.getAttribute('var')
            });
        },

        /**
         * Opens the translator with the variable
         */
        edit: function () {
            PanelUtils.openPanelInTasks(
                new Translator({
                    group : this.getAttribute('group'),
                    search: {
                        search: this.getAttribute('var')
                    }
                })
            );
        }
    });
});
