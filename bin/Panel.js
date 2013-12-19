/**
 * Translator panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/Panel
 * @package com.pcsg.qui.package.translator
 * @namespace QUI
 */

define('package/quiqqer/translator/bin/Panel', [

    "qui/QUI",
    "qui/controls/desktop/Panel",
    "qui/controls/buttons/Button",
    "qui/controls/buttons/Seperator",
    "qui/controls/buttons/Select",
    "qui/controls/windows/Confirm",
    "Ajax",
    "Locale",
    "controls/grid/Grid",

    "css!package/quiqqer/translator/bin/Panel.css"

], function(QUI, QUIPanel, QUIButton, QUIButtonSeperator, QUISelect, QUIConfirm, Ajax, Locale, Grid)
{
    return new Class({

        Extends : QUIPanel,
        Type    : 'package/quiqqer/translator/bin/Panel',

        Binds : [
            'exportGroup',
            'search',
            'addVariable',
            'deleteVariables',
            'addGroup',
            'publish',
            'importTranslation',
            '$onCreate',
            '$onResize',
            '$onRefresh',
            '$loadSubGroups',
            '$loadGrid',
            '$gridClick',
            '$gridDblClick',
            '$gridBlur',
            '$onEditComplete',
            '$searchTemplate',
            '$attentionBox'
        ],

        options : {
            field  : '',
            order  : '',
            limit  : 20,
            page   : 1,
            search : false
        },

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', Locale.get( 'package/translator', 'panel.title' ) );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/flags/default.png' );

            this.parent( options );

            this.addEvents({
                'onCreate'  : this.$onCreate,
                'onResize'  : this.$onResize,
                'onRefresh' : this.$onRefresh
            });

            this.$Container = null;
            this.$Grid      = null;
            this.$groups    = {};
        },

        /**
         * Return the actually grid
         *
         * @return {false|controls/grid/Grid}
         */
        getGrid : function()
        {
            return this.$Grid;
        },

        /**
         * Internal creation
         */
        $onCreate : function()
        {
            this.Loader.show();

            this.$Container = new Element( 'div' ).inject( this.getBody() );

            this.$loadButtons();
            this.$loadGroups.delay( 500, this );
        },

        /**
         * event: resize
         */
        $onResize : function()
        {
            if ( !this.getGrid() ) {
                return;
            }

            var Body = this.getBody(),
                Grid = this.getGrid();

            if ( this.getAttribute( 'search' ) )
            {
                Grid.setHeight( Body.getSize().y - 90 );
            } else
            {
                Grid.setHeight( Body.getSize().y - 40 );
            }

            Grid.setWidth( Body.getSize().x - 40 );
        },

        /**
         * Refresh the grid and the Panel
         */
        $onRefresh : function()
        {
            if ( !this.getGrid() ) {
                return;
            }

            var self = this;

            this.Loader.show();

            Ajax.get(
                'package_quiqqer_translator_ajax_translations',

                function(result, Request)
                {
                    self.getGrid().setData( result.data );
                    self.$gridBlur();
                    self.$attentionBox();

                    self.Loader.hide();
                },

                {
                    'package'  : 'quiqqer/translator',

                    groups  : this.getTranslationGroup(),
                    params  : JSON.encode({
                        field : this.getAttribute( 'field' ),
                        order : this.getAttribute( 'order' ),
                        limit : this.getAttribute( 'limit' ),
                        page  : this.getAttribute( 'page' )
                    }),
                    search : JSON.encode( this.getAttribute( 'search' ) )
                }
            );
        },

        /**
         * Return the selected translation group
         *
         * @return {String}
         */
        getTranslationGroup : function()
        {
            var ButtonBar = this.getButtonBar(),

                Sel1 = ButtonBar.getChildren( 'translater/group/begin' ),
                Sel2 = ButtonBar.getChildren( 'translater/group/end' );

            return Sel1.getValue() +'/'+ Sel2.getValue();
        },

        /**
         * Export the selected group and opens a download dialog
         */
        exportGroup : function()
        {
            var id = this.getId();

            new QUIConfirm({
                title : Locale.get( 'package/translator', 'export.window.title' ),
                text  : Locale.get( 'package/translator', 'export.window.text' ),
                icon  : URL_BIN_DIR +'16x16/export.png',

                information : '<p>' +
                             '<input id="edit_false'+ id +'" type="radio" name="edit" value="0" />' +
                             '<label for="edit_false'+ id +'">'+
                                 Locale.get( 'package/translator', 'export.window.option.orig' ) +
                             '</label>' +
                         '</p>' +
                         '<p>' +
                             '<input id="edit_true'+ id +'" type="radio" name="edit" value="1" checked="checked" />' +
                             '<label for="edit_true'+ id +'">' +
                                 Locale.get( 'package/translator', 'export.window.option.edit' ) +
                             '</label>' +
                         '</p>',

                events :
                {
                    onSubmit : function(Win)
                    {
                        var Body = Win.getBody(),
                            edit = Body.getElement( 'input[value="1"]' ).checked,

                            url  = Ajax.$url +'?'+
                                   Ajax.parseParams('package_quiqqer_translator_ajax_export', {
                                       'package'  : 'quiqqer/translator',
                                       group      : this.getTranslationGroup(),
                                       edit       : edit ? 1 : 0
                                   });

                        // create a iframe
                        if ( !$('download-frame') )
                        {
                            new Element('iframe#download-frame', {
                                styles : {
                                    position : 'absolute',
                                    width    : 100,
                                    height   : 100,
                                    left     : -400,
                                    top      : -400
                                }
                            }).inject( document.body );
                        }

                        $('download-frame').set( 'src', url );

                    }.bind( this )
                }
            }).create();
        },

        /**
         * Opens the add variable dialog
         */
        addVariable : function()
        {
            var self = this;

            require(['package/quiqqer/translator/bin/AddVariable'], function(Add) {
                Add( self );
            });
        },

        /**
         * Opens the delete dialog
         */
        deleteVariables : function(event)
        {
            var self = this,
                Grid = this.getGrid(),
                data = Grid.getSelectedData();

            require(['package/quiqqer/translator/bin/DeleteVariables'], function(Del) {
                Del( self, data );
            });
        },

        /**
         *
         */
        addGroup : function()
        {

        },

        /**
         * Starts the publishing process
         */
        publish : function()
        {
            this.getButtonBar()
                .getChildren( 'publish' )
                .setAttribute( 'textimage', URL_BIN_DIR +'images/loader.gif' );

            require([

                'package/quiqqer/translator/bin/Publish'

            ], function(Publisher)
            {
                Publisher.publish(this, function(result, Request)
                {
                    Request.getAttribute( 'Translator' ).getButtonBar()
                        .getChildren( 'publish' )
                        .setAttribute( 'textimage', URL_BIN_DIR +'16x16/global.png' );

                });

            }.bind( this ));
        },

        /**
         * create the grid
         */
        $loadGrid : function()
        {
            Ajax.get(

                'package_quiqqer_translator_ajax_translations',

                function(translations, Request)
                {
                    var Translator = Request.getAttribute( 'Translator' ),
                        Body       = Translator.getBody(),

                        cols  = [],
                        langs = translations.langs,

                        height = Body.getSize().y - 40,
                        width  = Body.getSize().x - 40;


                    cols.push({
                        header    : Locale.get( 'package/translator', 'grid.title.variable' ),
                        dataIndex : 'var',
                        dataType  : 'string',
                        width     : 150,
                        editable  : true
                    });

                    if ( Translator.getAttribute( 'search' ) )
                    {
                        cols.push({
                            header    : Locale.get( 'package/translator', 'grid.title.group' ),
                            dataIndex : 'groups',
                            dataType  : 'string',
                            width     : 150
                        });

                        Translator.$attentionBox();
                    }

                    var dev = QUI.config('globals').development;

                    // Sprachen
                    for ( var i = 0, len = langs.length; i < len; i++ )
                    {
                        cols.push({
                            header    : langs[ i ],
                            dataIndex : langs[ i ],
                            dataType  : 'string',
                            editType  : 'textarea',
                            width     : 300,
                            editable  : true
                        });

                        if ( dev == 1 )
                        {
                            cols.push({
                                header    : langs[ i ] +'_edit',
                                dataIndex : langs[ i ] +'_edit',
                                dataType  : 'string',
                                editType  : 'textarea',
                                width     : 300,
                                editable  : true
                            });
                        }
                    }

                    cols.push({
                        header    : Locale.get( 'package/translator', 'grid.title.type' ),
                        dataIndex : 'datatype',
                        dataType  : 'string',
                        width     : 50,
                        editable  : true
                    });

                    cols.push({
                        header    : Locale.get( 'package/translator', 'grid.title.define' ),
                        dataIndex : 'datadefine',
                        dataType  : 'string',
                        width     : 100,
                        editable  : true
                    });

                    if ( Translator.$Grid ) {
                        Translator.$Grid.destroy();
                    }

                    Translator.$Grid = new Grid( Translator.$Container, {
                        columnModel : cols,
                        pagination  : true,
                        filterInput : true,
                        buttons     : [{
                            name      : 'add',
                            text      : Locale.get( 'package/translator', 'btn.add.var.text' ),
                            textimage : URL_BIN_DIR +'16x16/add.png',
                            events    : {
                                onClick : Translator.addVariable
                            }
                        }, {
                            name      : 'del',
                            text      : Locale.get( 'package/translator', 'btn.del.var.text' ),
                            textimage : URL_BIN_DIR +'16x16/trashcan_empty.png',
                            events    : {
                                onMousedown : Translator.deleteVariables
                            }
                        }],

                        editable       : true,
                        editondblclick : true,

                        perPage     : Translator.getAttribute( 'limit' ),
                        page        : Translator.getAttribute( 'page' ),
                        sortOn      : Translator.getAttribute( 'field' ),
                        width       : width,
                        height      : height,
                        onrefresh   : function(me)
                        {
                            var options = me.options;

                            this.setAttribute( 'field', options.sortOn );
                            this.setAttribute( 'order', options.sortBy );
                            this.setAttribute( 'limit', options.perPage );
                            this.setAttribute( 'page', options.page );

                            this.refresh();

                        }.bind( Translator ),

                        alternaterows     : true,
                        resizeColumns     : true,
                        selectable        : true,
                        multipleSelection : true,
                        resizeHeaderOnly  : true
                    });

                    // Events
                    Translator.$Grid.addEvents({
                        onClick    : Translator.$gridClick,
                        onDblClick : Translator.$gridDblClick,
                        onBlur     : Translator.$gridBlur,
                        onEditComplete : Translator.$onEditComplete
                    });

                    Translator.$Grid.setData( translations.data );
                    Translator.$gridBlur();
                    Translator.resize();
                    Translator.Loader.hide();
                },

                {
                    'package'  : 'quiqqer/translator',
                    Translator : this,

                    groups  : this.getTranslationGroup(),
                    params  : JSON.encode({
                        field : this.getAttribute( 'field' ),
                        order : this.getAttribute( 'order' ),
                        limit : this.getAttribute( 'limit' ),
                        page  : this.getAttribute( 'page' )
                    }),
                    search : JSON.encode( this.getAttribute( 'search' ) )
                }
            );
        },

        /**
         * Create the buttons
         */
        $loadButtons : function()
        {
            this.addButton({
                name  : 'search',
                title : Locale.get( 'package/translator', 'btn.search.title' ),
                alt   : Locale.get( 'package/translator', 'btn.search.alt' ),
                icon  : 'icon-search',
                events : {
                    onClick : this.search
                }
            });

            this.addButton( new QUIButtonSeperator() );

            this.addButton(
                new QUISelect({
                    name : 'translater/group/begin',
                    styles : {
                        width: 100
                    },
                    events : {
                        onChange : this.$loadSubGroups
                    }
                })
            );

            this.addButton(
                new QUISelect({
                    name   : 'translater/group/end',
                    styles : {
                        width: 100
                    },
                    events : {
                        onChange : this.$loadGrid
                    }
                })
            );

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'import',
                text      : Locale.get( 'package/translator', 'btn.import.text' ),
                textimage : URL_BIN_DIR +'16x16/import.png',
                events : {
                    onClick : this.importTranslation
                }
            });

            this.addButton({
                name      : 'export',
                text      : Locale.get( 'package/translator', 'btn.export.text' ),
                textimage : URL_BIN_DIR +'16x16/export.png',
                events : {
                    onClick : this.exportGroup
                }
            });

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'publish',
                text      : Locale.get( 'package/translator', 'btn.publish.text' ),
                textimage : URL_BIN_DIR +'16x16/global.png',
                events : {
                    onClick : this.publish
                }
            });

        },

        /**
         * Load the groups in the DropDown
         */
        $loadGroups : function()
        {
            Ajax.get('package_quiqqer_translator_ajax_groups', function(result, Request)
            {
                var i, g, len, group;

                var Translator = Request.getAttribute( 'Translator' ),
                    ButtonBar  = Translator.getButtonBar(),

                    Sel1   = ButtonBar.getChildren( 'translater/group/begin' ),
                    Sel2   = ButtonBar.getChildren( 'translater/group/end' ),
                    groups = Translator.$groups;

                for ( i = 0, len = result.length; i < len; i++ )
                {
                    group = result[ i ].split( '/' );

                    if ( typeof groups[ group[ 0 ] ] === 'undefined' ) {
                        groups[ group[ 0 ] ] = [];
                    }

                    if ( typeof group[ 1 ] !== 'undefined' ) {
                        groups[ group[ 0 ] ].push( group[ 1 ] );
                    }
                }

                Translator.$groups = groups;

                for ( g in groups ) {
                    Sel1.appendChild( g, g, URL_BIN_DIR +'16x16/flags/default.png' );
                }


                if ( Sel1.firstChild() ) {
                    Sel1.setValue( Sel1.firstChild().getAttribute( 'value' ) );
                }

                if ( Sel2.firstChild() )
                {
                    (function()
                    {
                        this.setValue(
                            this.firstChild().getAttribute( 'value' )
                        );

                        this.close();
                    }.delay( 100, Sel2 ));
                }

            }, {
                'package'  : 'quiqqer/translator',
                Translator : this
            });
        },

        /**
         * Load the sub groups at the second select dropdown
         */
        $loadSubGroups : function(value)
        {
            var ButtonBar = this.getButtonBar(),
                Sel2      = ButtonBar.getChildren( 'translater/group/end' ),
                groups    = {};

            Sel2.clear();

            if ( typeof this.$groups[ value ] !== 'undefined' ) {
                groups = this.$groups[ value ];
            }

            for ( var i = 0, len = groups.length; i < len; i++ )
            {
                Sel2.appendChild(
                    groups[ i ],
                    groups[ i ],
                    URL_BIN_DIR +'16x16/flags/default.png'
                );
            }

            if ( !groups.length )
            {
                Sel2.disable();
            } else
            {
                Sel2.enable();
                Sel2.open();
            }
        },

        /**
         * Create a attention box that the search is on
         */
        $attentionBox : function()
        {
            if ( this.getAttribute( 'search' ) === false ) {
                return;
            }

            if ( this.getBody().getElement( '.message-attention' ) ) {
                return;
            }

            var Bar  = this.getButtonBar(),
                self = this;

            Bar.getChildren( 'translater/group/begin' ).disable();
            Bar.getChildren( 'translater/group/end' ).disable();
            Bar.getChildren( 'export' ).disable();

            require(['qui/controls/messages/Attention'], function(Attention)
            {
                new Attention({
                    message    : Locale.get( 'package/translator', 'search.params.active' ),
                    events     :
                    {
                        onClick : function(Message, event)
                        {
                            self.setAttribute( 'search', false );
                            Message.destroy();

                            var Bar = self.getButtonBar();

                            Bar.getChildren( 'translater/group/begin' ).enable();
                            Bar.getChildren( 'translater/group/end' ).enable();
                            Bar.getChildren( 'export' ).enable();

                            self.$loadGrid();
                            self.resize();
                        }
                    },
                    styles  : {
                        margin : '0 0 20px',
                        'border-width' : 1,
                        cursor : 'pointer'
                    }
                }).inject( this.getBody(), 'top' );
            });

        },

        /**
         * Grid methods
         */

        /**
         * event: on grid click
         *
         * @param {Object} event - Grid Event
         */
        $gridClick : function(event)
        {
            var len    = event.target.selected.length,
                Grid   = this.getGrid(),
                Delete = Grid.getAttribute( 'buttons' ).del,
                Add    = Grid.getAttribute( 'buttons' ).add;

            if ( len === 0 )
            {
                Delete.disable();
                return;
            }

            Delete.enable();
            event.evt.stop();
        },

        /**
         * dblclick on the grid
         *
         * @param {Object} data - grid selected data
         */
        $gridDblClick : function(data)
        {
            /*
            this.openGroup(
                data.target.getDataByRow( data.row ).id
            );
            */
        },

        /**
         * onblur on the grid
         */
        $gridBlur : function()
        {
            var Grid   = this.getGrid();

            Grid.unselectAll();
            Grid.removeSections();

            Grid.getAttribute( 'buttons' ).del.disable();

            if ( this.getAttribute( 'search' ) ) {
                Grid.getAttribute( 'buttons' ).add.disable();
            }
        },

        /**
         * event: on grid edit complete
         *
         * @param {Object} params - grid edit params
         */
        $onEditComplete : function(params)
        {
            var newdata = this.getGrid().getDataByRow( params.row );

            Ajax.post(
                'package_quiqqer_translator_ajax_update',
                false,
                {
                    'package' : 'quiqqer/translator',
                    groups    : newdata.groups,
                    data      : JSON.encode( newdata )
                }
            );
        },

        /**
         * opens the importation sheet
         */
        importTranslation : function()
        {
            this.Loader.show();

            var Sheet = this.createSheet();

            Sheet.addEvent('onOpen', function(Sheet)
            {
                require([

                    'package/quiqqer/translator/bin/Import'

                ], function(Import)
                {
                    var TranslatorImport = new Import({
                        events :
                        {
                            onUpload : function() {
                                this.hide();
                            }.bind( Sheet )
                        }
                    });

                    TranslatorImport.inject( Sheet.getBody() );
                    TranslatorImport.initUpload();
                });

                this.Loader.hide();

            }.bind( this ));

            Sheet.show();
        },

        /**
         * Opens the translater search
         */
        search : function()
        {
            this.Loader.show();

            var Sheet = this.createSheet();

            Sheet.addEvent('onOpen', function(Sheet)
            {
                Sheet.addButton(
                    new QUIButton({
                        text      : Locale.get( 'package/translator', 'btn.search.sheet.text' ),
                        textimage : URL_BIN_DIR +'16x16/search.png',
                        events    :
                        {
                            onClick : function(Btn)
                            {

                            }
                        }
                    })
                );

                Ajax.get(
                    'package_quiqqer_translator_ajax_template_search',
                    this.$searchTemplate,
                    {
                        'package'  : 'quiqqer/translator',
                        Translator : this,
                        Sheet      : Sheet
                    }
                );

                this.Loader.hide();
            }.bind( this ));

            Sheet.show();
        },

        /**
         * set the search template into the sheet body
         *
         * @param {String} result - html template
         * @param {QUI.classes.request.Ajax} Request
         */
        $searchTemplate : function(result, Request)
        {
            var Form, elements;

            var Translator = Request.getAttribute( 'Translator' ),
                Sheet      = Request.getAttribute( 'Sheet' ),
                Body       = Sheet.getBody();

            Body.set( 'html', result );

            Form     = Body.getElement( 'form' );
            elements = Form.elements;

            // set values
            var search = false,
                fields = false;

            if ( this.getAttribute( 'search' ) && this.getAttribute( 'search' ).search ) {
                search = this.getAttribute( 'search' ).search;
            }

            if ( this.getAttribute( 'search' ) && this.getAttribute( 'search' ).fields ) {
                fields = this.getAttribute( 'search' ).fields;
            }

            if ( search ) {
                elements.search.value = search;
            }

            if ( fields )
            {
                for ( var i = 0, len = fields.length; i < len; i++ )
                {
                    if ( elements[ fields[ i ] ] ) {
                        elements[ fields[ i ] ].checked = true;
                    }

                    // language
                    if ( fields[ i ].length == 2 &&
                         Form.getElement( '[value="'+ fields[ i ] +'"]' ) )
                    {
                        Form.getElement( '[value="'+ fields[ i ] +'"]' ).checked = true;
                    }
                }
            } else
            {
                Form.getElements( '[type="checkbox"]' ).set( 'checked', true );
            }

            elements.search.focus();

            Form.addEvents({
                submit : function(event)
                {
                    event.stop();

                    var fields = [];

                    if ( elements.groups.checked ) {
                        fields.push( 'groups' );
                    }

                    if ( elements['var'].checked ) {
                        fields.push( 'var' );
                    }

                    if ( elements.datatype.checked ) {
                        fields.push( 'datatype' );
                    }

                    if ( elements.datadefine.checked ) {
                        fields.push( 'datadefine' );
                    }

                    if ( elements.lang )
                    {
                        // langs
                        for ( var i = 0, len = elements.lang.length; i < len; i++ )
                        {
                            if ( elements.lang[ i ].checked ) {
                                fields.push( elements.lang[ i ].value );
                            }
                        }
                    }

                    Translator.setAttribute('search', {
                        search : elements.search.value,
                        fields : fields
                    });

                    Sheet.hide();

                    Translator.$loadGrid();
                    Translator.resize();
                }
            });
        }
    });
});
