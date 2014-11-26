
/**
 * Translator add variable method
 *
 * @module package/quiqqer/translator/bin/AddVariable
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require package/quiqqer/translator/bin/Panel
 * @require Locale
 * @require Ajax
 * @require qui/controls/windows/Prompt
 */

define([

    "package/quiqqer/translator/bin/Panel",
    "Locale",
    "Ajax",
    "qui/controls/windows/Prompt"

], function(Panel, Locale, Ajax, QUIPrompt)
{
    "use strict";

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
                    if ( result === '' ) {
                        return;
                    }

                    Ajax.post('package_quiqqer_translator_ajax_add', function() {
                        // nothing
                    }, {
                        'package' : 'quiqqer/translator',
                        groups    : group,
                        'var'     : result
                    });

                    Win.setValue( '' );
                },

                onClose : function(Win) {
                    Translator.refresh();
                }
            }
        }).open();
    };
});
