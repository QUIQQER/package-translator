/**
 * Translator
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package package/quiqqer/translator/bin/classes/Translator
 *
 * @require qui/QUI
 * @require qui/classes/DOM
 * @require Ajax
 * @require Locale
 */
define('package/quiqqer/translator/bin/classes/Translator', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax',
    'Locale'

], function (QUI, QDOM, QUIAjax, QUILocale) {
    "use strict";

    return new Class({
        Extends: QDOM,
        Type   : 'package/quiqqer/translator/bin/classes/Translator',

        initialize: function (options) {
            this.parent(options);
        },

        /**
         * refresh the translation in the locale
         *
         * @return {Promise}
         */
        refreshLocale: function () {
            return new Promise(function (resolve, reject) {
                QUIAjax.get(
                    'package_quiqqer_translator_ajax_refreshLocale',
                    function (data) {

                        var lang, group;

                        for (lang in data) {
                            if (!data.hasOwnProperty(lang)) {
                                continue;
                            }

                            for (group in data[lang]) {
                                if (!data[lang].hasOwnProperty(group)) {
                                    continue;
                                }

                                QUILocale.set(
                                    lang,
                                    group,
                                    data[lang][group]
                                );
                            }
                        }

                        resolve();
                    }, {
                        'package': 'quiqqer/translator',
                        onError  : reject
                    });
            });
        }
    });
});