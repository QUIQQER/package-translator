/**
 * Translator delete variables method
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/DeleteVariables
 * @package com.pcsg.qui.package.translator
 * @namespace QUI
 */

define('package/quiqqer/translator/bin/DeleteVariables', [

    "package/quiqqer/translator/bin/Panel",
    "Locale",
    "Ajax",
    "qui/controls/windows/Confirm"

], function(Panel, Locale, Ajax, QUIConfirm)
{
    "use strict";

    return function(Translator, data)
    {
        var i, len;

        var group = Translator.getTranslationGroup(),
            Grid  = Translator.getGrid(),

            message = Locale.get( 'package/translator', 'del.window.text' ) +
                      '<ul style="margin-top: 10px">';

        for ( i = 0, len = data.length; i < len; i++ ) {
            message = message +'<li>'+ data[ i ].groups +' '+ data[ i ]['var'] +'</li>';
        }

        message = message +'</ul>';

        new QUIConfirm({
            name   : 'del_sel_items',
            title  : Locale.get( 'package/translator', 'del.window.title' ),
            icon   : 'icon-trash',
            width  : 500,
            height : 200,
            text   : message,
            data   : data,
            Translator  : Translator,
            textIcon    : 'icon-trash',

            information : Locale.get(
                'package/translator',
                'del.window.text.information'
            ),

            events :
            {
                onSubmit : function(Win)
                {
                    Win.Loader.show();

                    var list = [],
                        data = Win.getAttribute( 'data' );

                    for ( var i = 0, len = data.length; i < len; i++ )
                    {
                        list.push({
                            'var'    : data[ i ]['var'],
                            'groups' : data[ i ].groups
                        });
                    }


                    Ajax.post(

                        'package_quiqqer_translator_ajax_delete',

                        function(result, Request)
                        {
                            Request.getAttribute( 'Translator' ).refresh();
                        },

                        {
                            'package'  : 'quiqqer/translator',
                            Translator : Translator,
                            data       : JSON.encode( list ),
                            Translator : Win.getAttribute( 'Translator' )
                        }
                    );
                }
            }
        }).open();
    }
});
