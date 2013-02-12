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

    "package/quiqqer/translator/bin/Panel"

], function()
{
    return function(Translator, data)
    {
        var i, len;

        var group = Translator.getTranslationGroup(),
            Grid  = Translator.getGrid(),

            message = 'Möchten Sie folgende Element wirklich löschen:'+
                      '<ul style="margin-top: 10px">';

        for ( i = 0, len = data.length; i < len; i++ ) {
            message = message +'<li>'+ data[ i ].groups +' '+ data[ i ]['var'] +'</li>';
        }

        message = message +'</ul>';

        new QUI.controls.windows.Submit({
            name   : 'del_sel_items',
            title  : 'Übersetzungen löschen',
            icon   : URL_BIN_DIR +'16x16/trashcan_empty.png',
            width  : 500,
            height : 200,
            text   : message,
            data   : data,
            Translator  : Translator,
            textIcon    : URL_BIN_DIR +'32x32/trashcan_empty.png',
            information : 'Die Elemente sind unwiederruflich gelöscht.',

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


                    QUI.Ajax.post(

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
        }).create();
    }
});
