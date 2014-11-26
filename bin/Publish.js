/**
 * Publish the translations
 *
 * @module package/quiqqer/translator/bin/Publish
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require Ajax
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