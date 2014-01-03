/**
 * Translator delete variables method
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/Import
 * @package com.pcsg.qui.package.translator
 * @namespace QUI
 *
 * @event onUpload [ this ]
 */

define('package/quiqqer/translator/bin/Import', [

    'qui/controls/Control',
    'controls/upload/Form',
    'Locale',
    'qui/controls/buttons/Button',

    'css!package/quiqqer/translator/bin/Import.css'

], function(QUIControl, UploadForm, Locale, QUIButton)
{
    /**
     * @calss package/quiqqer/translator/bin/Import
     *
     * @param {Object} options - Control options
     */
    return new Class({

        Extends : QUIControl,
        Type    : 'package/quiqqer/translator/bin/Import',

        initialize: function(options)
        {
            this.parent( options );
        },

        /**
         * Create the DOMNode of the Import
         */
        create : function()
        {
            this.$Elm = new Element( 'div', {
                'class' : 'qui-package-translator-import box smooth',
                html : '<h1>'+ Locale.get( 'package/translator', 'import.window.title' ) +'</h1>' +
                       '<div class="description">' +
                           Locale.get( 'package/translator', 'import.window.text' ) +
                       '</div>' +
                       '<div class="qui-package-translator-upload"></div>'
            });

            return this.$Elm;
        },

        /**
         * initlialize the drag drop
         */
        initUpload : function()
        {
            var Upload = this.$Elm.getElement( '.qui-package-translator-upload' );

            var Form = new UploadForm({
                Drops  : [ this.$Elm ],
                styles : {
                    margin : '20px 0 0',
                    float  : 'left',
                    clear  : 'both'
                },
                events  :
                {
                    /// drag drop events
                    onDragenter: function(event, Elm, Upload)
                    {
                        if ( Elm.hasClass( 'qui-package-translator-import' ) ) {
                            Elm.addClass( 'dragdrop' );
                        }
                    },

                    onDragend : function(event, Elm, Upload)
                    {
                        if ( Elm.hasClass( 'qui-package-translator-import' ) ) {
                            Elm.removeClass( 'dragdrop' );
                        }
                    },

                    onSubmit : function(Form)
                    {
                        this.fireEvent( 'upload', [ this ] );
                    }.bind( this ),

                    onComplete : function(Form, File, result)
                    {
                        if ( typeof result === 'undefined' ) {
                            return;
                        }

                        var entry;
                        var message = '<p>' +
                                      Locale.get(
                                          'package/translator',
                                          'message.import.success'
                                      ) +
                                      ': </p><ul>';

                        for ( var i = 0, len = result.length; i < len; i++ )
                        {
                            entry   = result[ i ];
                            message = message +'<li>'+ entry.group +' '+ entry['var'];

                            if ( entry.locale )
                            {
                                message = message +'<ul>';

                                for ( var lang in entry.locale )
                                {
                                    message = message +'<li>'+ lang +': '+
                                              entry.locale[ lang ] +'</li>';
                                }

                                message = message +'</ul>';
                            }

                            message = message +'</li>';
                        }

                        message = message +'</ul>';

                        QUI.getMessageHandler(function(MH) {
                            MH.addSuccess( message );
                        });
                    }
                }
            });

            // Form.setParam('onstart', 'ajax_media_checkreplace');
            Form.setParam( 'onfinish', 'package_quiqqer_translator_ajax_file_import' );
            Form.setParam( 'overwrite', 1 );

            if ( QUIQQER_CONFIG.globals.development )
            {
                new Element('div', {
                    html : '<input type="checkbox" name="overwrite" id="overwrite+'+ this.getId() +'" checked="checked" />' +
                           '<label for="overwrite+'+ this.getId() +'">' +
                               Locale.get(
                                   'package/translator',
                                   'import.window.text.debug.overwrite.vars'
                               ) +
                           '</label>',
                    styles : {
                        'float' : 'left',
                        width   : '100%',
                        margin  : '20px 0 10px 0',
                        textDecoration : 'underline'
                    }
                }).inject( Upload );

                Upload.getElements( '[name="overwrite"]' ).addEvents({
                    change : function()
                    {
                        Form.setParam( 'overwrite', this.checked ? 1 : 0 );
                    }
                });
            }

            Form.inject( Upload );

            // Send
            new QUIButton({
                text   : Locale.get( 'package/translator', 'import.window.btn.upload' ),
                textimage : URL_BIN_DIR +'16x16/upload.png',
                Form   : Form,
                styles : {
                    margin : '20px 0 0',
                    float  : 'left',
                    clear  : 'both'
                },
                events :
                {
                    onClick : function(Btn) {
                        Btn.getAttribute( 'Form' ).submit();
                    }
                }
            }).inject(
                Upload
            );
        }
    });

});
