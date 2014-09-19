/**
 * Publish the translations
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module URL_OPT_DIR/quiqqer/translator/bin/Publish
 */

define(['Ajax'], function(Ajax)
{
    "use strict";

    return {

        publish : function(Translator, oncomplete)
        {
            oncomplete = oncomplete || function() { };

            Ajax.post('package_quiqqer_translator_ajax_create', oncomplete, {
                'package'  : 'quiqqer/translator',
                Translator : Translator
            });
        }
    };
});