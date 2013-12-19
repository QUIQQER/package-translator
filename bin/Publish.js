/**
 * Publish the translations
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/Publish
 * @package com.pcsg.qui.package.translator
 */

define('package/quiqqer/translator/bin/Publish', ['Ajax'], function(Ajax)
{
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