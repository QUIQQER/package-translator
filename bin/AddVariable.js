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

    "package/quiqqer/translator/bin/Panel",
    "Locale",
    "Ajax",
    "qui/controls/windows/Prompt"

], function(Panel, Locale, Ajax, QUIPrompt)
{
    return function(Translator)
    {
        var group = Translator.getTranslationGroup();

        new QUIPrompt({
            name   : 'add_new_translation',
            icon   : 'icon-plus-sign-alt',
            titleicon : 'icon-plus-sign-alt',
            width  : 560,
            height : 200,
            group  : group,
            title  : Locale.get('package/translator', 'add.window.title', {
                group : group
            }),
            Translator  : Translator,
            information : Locale.get('package/translator', 'add.window.text', {
                group : group
            }),
            // no autoclose
            check  : function() {
                return false;
            },

            cancel_button : {
                text      : Locale.get('package/translator', 'add.window.btn.close'),
                textimage : 'icon-remove'
            },

            ok_button : {
                text      : Locale.get('package/translator', 'add.window.btn.add'),
                textimage : 'icon-plus'
            },

            events :
            {
                onEnter : function(result, Win)
                {
                    if ( result == '' ) {
                        return;
                    }

                    Ajax.post('package_quiqqer_translator_ajax_add', function()
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
        }).open();
    }
});
