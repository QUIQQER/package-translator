/**
 * Translator delete variables method
 *
 * @module package/quiqqer/translator/bin/DeleteVariables
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require package/quiqqer/translator/bin/Panel
 * @require Locale
 * @require Ajax
 * @require qui/controls/windows/Confirm
 */
define('package/quiqqer/translator/bin/DeleteVariables', [

    "package/quiqqer/translator/bin/Panel",
    "Locale",
    "Ajax",
    "qui/controls/windows/Confirm"

], function (Panel, Locale, Ajax, QUIConfirm) {
    "use strict";

    return function (Translator, data) {
        var i, len;

        var message = Locale.get('quiqqer/translator', 'del.window.text') +
                      '<ul style="margin-top: 10px">';

        for (i = 0, len = data.length; i < len; i++) {
            message = message + '<li>' + data[i].groups + ' ' + data[i]['var'] + '</li>';
        }

        message = message + '</ul>';

        new QUIConfirm({
            name       : 'del_sel_items',
            title      : Locale.get('quiqqer/translator', 'del.window.title'),
            icon       : 'fa fa-trash',
            width      : 500,
            height     : 200,
            text       : message,
            data       : data,
            textIcon   : 'fa fa-trash',
            information: Locale.get('quiqqer/translator', 'del.window.text.information'),

            events: {
                onSubmit: function (Win) {
                    Win.Loader.show();

                    var list = [],
                        data = Win.getAttribute('data');

                    for (var i = 0, len = data.length; i < len; i++) {
                        list.push({
                            'var'   : data[i]['var'],
                            'groups': data[i].groups,
                            'id'    : data[i].id
                        });
                    }

                    Ajax.post('package_quiqqer_translator_ajax_delete', function () {
                        Translator.refresh();
                    }, {
                        'package': 'quiqqer/translator',
                        data     : JSON.encode(list)
                    });
                }
            }
        }).open();
    };
});
