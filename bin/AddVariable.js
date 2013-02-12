/**
 * Translator add variable method
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/AddVariable
 * @package com.pcsg.qui.package.translator
 * @namespace QUI
 */

define('package/quiqqer/translator/bin/AddVariable', [

    "package/quiqqer/translator/bin/Panel"

], function()
{
    return function(Translator)
    {
        var group = Translator.getTranslationGroup();

        new QUI.controls.windows.Prompt({
            name   : 'add_new_translation',
            width  : 560,
            height : 200,
            group  : group,
            title  : QUI.Locale.get('package/translator', 'add.window.title', {
                group : group
            }),
            Translator  : Translator,
            information : QUI.Locale.get('package/translator', 'add.window.text', {
                group : group
            }),
            // no autoclose
            check  : function() {
                return false;
            },

            cancel_button : {
                text      : QUI.Locale.get('package/translator', 'add.window.btn.close'),
                textimage : URL_BIN_DIR +'16x16/cancel.png'
            },

            ok_button : {
                text      : QUI.Locale.get('package/translator', 'add.window.btn.add'),
                textimage : URL_BIN_DIR +'16x16/add.png'
            },

            events :
            {
                onEnter : function(result, Win)
                {
                    if ( result == '' ) {
                        return;
                    }

                    QUI.Ajax.post('package_quiqqer_translator_ajax_add', function()
                    {

                    }, {
                        'package'  : 'quiqqer/translator',
                        Translator : Translator,
                        groups     : group,
                        'var'      : result
                    });

                    Win.setValue( '' );
                },

                onClose : function(Win)
                {
                    Win.getAttribute( 'Translator' ).refresh();
                }
            }
        }).create();
    }
});
