
/**
 * Translator panel
 *
 * @module package/quiqqer/translator/bin/Panel
 * @author www.pcsg.de (Henning Leutz)
 *
 * @require qui/QUI
 * @require qui/controls/desktop/Panel
 * @require qui/controls/buttons/Button
 * @require qui/controls/buttons/Seperator
 * @require qui/controls/buttons/Select
 * @require qui/controls/windows/Confirm
 * @require qui/utils/Elements
 * @require Ajax
 * @require Locale
 * @require Editors
 * @require controls/grid/Grid
 * @require css!package/quiqqer/translator/bin/Panel.css
 */

define([

    "qui/QUI",
    "qui/controls/desktop/Panel",
    "qui/controls/buttons/Button",
    "qui/controls/buttons/Seperator",
    "qui/controls/buttons/Select",
    "qui/controls/windows/Confirm",
    "qui/utils/Elements",
    "Ajax",
    "Locale",
    "Editors",
    "controls/grid/Grid",

    "css!package/quiqqer/translator/bin/Panel.css"

], function()
{
    "use strict";

    var QUI	               = arguments[ 0 ],
        QUIPanel           = arguments[ 1 ],
        QUIButton          = arguments[ 2 ],
        QUIButtonSeperator = arguments[ 3 ],
        QUISelect          = arguments[ 4 ],
        QUIConfirm         = arguments[ 5 ],
        QUIElementUtils    = arguments[ 6 ],

        Ajax    = arguments[ 7 ],
        Locale  = arguments[ 8 ],
        Editors = arguments[ 9 ],
        Grid    = arguments[ 10 ];


    return new Class({

        Extends : QUIPanel,
        Type    : 'URL_OPT_DIR/quiqqer/translator/bin/Panel',

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
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onRefresh : this.$onRefresh
            });

            this.$Container = null;
            this.$Grid      = null;
            this.$Editor    = null;
            this.$groups    = {};

            this.$EditorHeader     = null;
            this.$devMessageShowed = false;
        },

        /**
         * Return the actually grid
         *
         * @return {null|Object} null | controls/grid/Grid
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

            this.$Container = new Element('div', {
                'class' : 'qui-translater'
            }).inject( this.getBody() );

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


            var bodyHeight = Body.getSize().y,
                height     = bodyHeight;


            if ( this.getAttribute( 'search' ) )
            {
                height = height - 110;
            } else
            {
                height = height - 40;
            }

            if ( this.$Editor )
            {
                height = 300;

                this.$Editor.getElm().setStyles({
                    height : bodyHeight - 410
                });

                this.$Editor.setHeight( bodyHeight - 410 );
            }

            Grid.setHeight( height );
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

                function(result)
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
                        if ( !document.id('download-frame') )
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

                        document.id('download-frame').set( 'src', url );

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

            require(['package/quiqqer/translator/bin/AddVariable'], function(add) {
                add( self );
            });
        },

        /**
         * Opens the delete dialog
         */
        deleteVariables : function()
        {
            var self = this,
                Grid = this.getGrid(),
                data = Grid.getSelectedData();

            require(['package/quiqqer/translator/bin/DeleteVariables'], function(del) {
                del( self, data );
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
            var self = this;

            this.getButtonBar()
                .getChildren( 'publish' )
                .setAttribute( 'textimage', 'icon-refresh' );

            require(['package/quiqqer/translator/bin/Publish'], function(Publisher)
            {
                Publisher.publish(self, function()
                {
                    self.getButtonBar()
                        .getChildren( 'publish' )
                        .setAttribute( 'textimage', 'icon-reply' );
                });
            });
        },

        /**
         * create the grid
         */
        $loadGrid : function()
        {
            var self = this;

            Ajax.get('package_quiqqer_translator_ajax_translations', function(translations)
            {
                var Body   = self.getBody(),
                    cols   = [],
                    langs  = translations.langs,
                    height = Body.getSize().y - 40,
                    width  = Body.getSize().x - 40;

                cols.push({
                    header    : Locale.get( 'package/translator', 'grid.title.variable' ),
                    dataIndex : 'var',
                    dataType  : 'string',
                    width     : 150,
                    editable  : true
                });

                if ( self.getAttribute( 'search' ) )
                {
                    cols.push({
                        header    : Locale.get( 'package/translator', 'grid.title.group' ),
                        dataIndex : 'groups',
                        dataType  : 'string',
                        width     : 150
                    });

                    self.$attentionBox();
                }

                var dev = ( QUIQQER_CONFIG.globals.development ).toInt();

                // Sprachen
                for ( var i = 0, len = langs.length; i < len; i++ )
                {
                    cols.push({
                        header    : langs[ i ],
                        dataIndex : langs[ i ],
                        dataType  : 'code',
                        width     : 300,
                        editable  : true
                    });

                    if ( dev == 1 )
                    {
                        cols.push({
                            header    : langs[ i ] +'_edit',
                            dataIndex : langs[ i ] +'_edit',
                            dataType  : 'code',
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
                    header    : Locale.get( 'package/translator', 'grid.title.html' ),
                    dataIndex : 'html',
                    dataType  : 'bool',
                    width     : 50,
                    editable  : true
                });

                if ( self.$Grid ) {
                    self.$Grid.destroy();
                }

                self.$Grid = new Grid( self.$Container, {
                    columnModel : cols,
                    pagination  : true,
                    filterInput : true,
                    buttons     : [{
                        name      : 'add',
                        text      : Locale.get( 'package/translator', 'btn.add.var.text' ),
                        textimage : 'icon-plus',
                        events    : {
                            onClick : self.addVariable
                        }
                    }, {
                        name      : 'del',
                        text      : Locale.get( 'package/translator', 'btn.del.var.text' ),
                        textimage : 'icon-trash',
                        events    : {
                            onMousedown : self.deleteVariables
                        }
                    }],

                    editable       : false,
                    editondblclick : false,

                    perPage     : self.getAttribute( 'limit' ),
                    page        : self.getAttribute( 'page' ),
                    sortOn      : self.getAttribute( 'field' ),
                    width       : width,
                    height      : height,
                    onrefresh   : function(me)
                    {
                        var options = me.options;

                        self.setAttribute( 'field', options.sortOn );
                        self.setAttribute( 'order', options.sortBy );
                        self.setAttribute( 'limit', options.perPage );
                        self.setAttribute( 'page', options.page );

                        self.refresh();
                    },

                    alternaterows     : true,
                    resizeColumns     : true,
                    selectable        : true,
                    multipleSelection : true,
                    resizeHeaderOnly  : true
                });

                // Events
                self.$Grid.addEvents({
                    onClick    : self.$gridClick,
                    onDblClick : self.$gridDblClick,
                    onBlur     : self.$gridBlur
                });

                self.$Grid.setData( translations.data );
                self.$gridBlur();
                self.resize();
                self.Loader.hide();

                // dev info
                if ( dev && self.$devMessageShowed === false )
                {
                    self.$devMessageShowed = true;

                    QUI.getMessageHandler(function(MessageHandler)
                    {
                        MessageHandler.addInformation(
                            'QUIQQER ist im Entwicklungsmodus, '+
                            'daher werden im Übersetzer die lang_edit Spalten angezeigt<br />'+
                            'Mehr Informationen im quiqqer/translator Wiki unter: '+
                            '<a href="https://dev.quiqqer.com/quiqqer/package-translator/wikis/home" target="_blank">'+
                                'https://dev.quiqqer.com/quiqqer/package-translator/wikis/home'+
                            '</a>'
                        );
                    });
                }

            }, {
                'package' : 'quiqqer/translator',
                groups : this.getTranslationGroup(),
                params : JSON.encode({
                    field : this.getAttribute( 'field' ),
                    order : this.getAttribute( 'order' ),
                    limit : this.getAttribute( 'limit' ),
                    page  : this.getAttribute( 'page' )
                }),
                search : JSON.encode( this.getAttribute( 'search' ) )
            });
        },

        /**
         * Create the buttons
         */
        $loadButtons : function()
        {
            var self = this;

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
                    events :
                    {
                        onChange : function()
                        {
                            // grid sheet to 1
                            self.setAttribute( 'page', 1 );
                            self.$loadGrid();
                        }
                    }
                })
            );

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'import',
                text      : Locale.get( 'package/translator', 'btn.import.text' ),
                textimage : 'icon-upload',
                disabled  : true,
                events : {
                    onClick : this.importTranslation
                }
            });

            this.addButton({
                name      : 'export',
                text      : Locale.get( 'package/translator', 'btn.export.text' ),
                textimage : 'icon-download',
                disabled  : true,
                events : {
                    onClick : this.exportGroup
                }
            });

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'publish',
                text      : Locale.get( 'package/translator', 'btn.publish.text' ),
                textimage : 'icon-reply',
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
            var self = this;

            Ajax.get('package_quiqqer_translator_ajax_groups', function(result)
            {
                var i, g, len, group;

                var ButtonBar = self.getButtonBar(),

                    Sel1   = ButtonBar.getChildren( 'translater/group/begin' ),
                    Sel2   = ButtonBar.getChildren( 'translater/group/end' ),
                    groups = self.$groups;

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

                self.$groups = groups;

                for ( g in groups )
                {
                    if ( groups.hasOwnProperty( g ) ) {
                        Sel1.appendChild( g, g, URL_BIN_DIR + '16x16/flags/default.png' );
                    }
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
                'package'  : 'quiqqer/translator'
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
                    message : Locale.get( 'package/translator', 'search.params.active' ),
                    events  :
                    {
                        onClick : function(Message)
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
                }).inject( self.getBody(), 'top' );
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
                Delete = Grid.getAttribute( 'buttons' ).del;

            if ( len === 0 )
            {
                Delete.disable();
                return;
            }

            Delete.enable();
            event.evt.stop();
        },

        /**
         * dblclick on the grid -> edit entry
         *
         * @param {Object} data - grid selected data
         */
        $gridDblClick : function(data)
        {
            var self    = this,
                Cell    = data.cell,
                row     = data.row,
                Grid    = this.getGrid(),
                Content = this.getContent(),

                gridData    = data.target.$data,
                columnModel = data.target.$columnModel,
                localeData  = gridData[ row ];

            var index = QUIElementUtils.getChildIndex( Cell );

            if ( index === 0 ) {
                return;
            }

            // html and typ only editable at development
            var Column = columnModel[ index ],
                dev    = ( QUIQQER_CONFIG.globals.development ).toInt();

            if ( !dev && ( Column.dataIndex === 'html' || Column.dataIndex === 'datatype' ) ) {
                return;
            }


            Grid.setHeight( 300 );

            if ( this.$Editor )
            {
                this.$gridDblClickHeaderCreate( localeData, Column, row );
                this.$Editor.setContent( Cell.get('text') );

                if ( ( localeData.html ).toInt() === 1 )
                {
                    this.$Editor.switchToWYSIWYG();
                    this.$Editor.showToolbar();

                } else
                {
                    this.$Editor.switchToSource();
                    this.$Editor.hideToolbar();
                }

                this.$Editor.focus();

                return;
            }


            this.Loader.show();

            Editors.getEditor(null, function(Editor)
            {
                self.$Editor = Editor;

                var height = Content.getSize().y - 410;

                if ( height < 100 ) {
                    height = 100;
                }

                self.$EditorHeader = new Element( 'div', {
                    'class' : 'qui-translater-editable-header'
                }).inject( Content );

                self.$gridDblClickHeaderCreate( localeData, Column, row );

                var EditorContainer = new Element( 'div', {
                    'class' : 'qui-translater-editable',
                    styles  : {
                        height : height
                    }
                }).inject( Content );

                // minimal toolbar
                self.$Editor.setAttribute('buttons', {
                    lines : [
                        [[

                        { type : 'button', button : 'Source' },
                        { type : "seperator" },
                        { type : "button", button : "Bold" },
                        { type : "button", button : "Italic" },
                        { type : "button", button : "Underline" },
                        { type : "button", button : "Strike" },
                        { type : "button", button : "Subscript" },
                        { type : "button", button : "Superscript" },
                        { type : "seperator" },
                        { type : "button", button : "RemoveFormat" },
                        { type : "seperator" },
                        { type : "button", button : "NumberedList" },
                        { type : "button", button : "BulletedList" },
                        { type : "seperator" },
                        { type : "button", button : "Outdent" },
                        { type : "button", button : "Indent" },
                        { type : "seperator" },
                        { type : "button", button : "Blockquote" },
                        { type : "button", button : "CreateDiv" },
                        { type : "seperator" },
                        { type : "button", button : "JustifyLeft" },
                        { type : "button", button : "JustifyCenter" },
                        { type : "button", button : "JustifyRight" },
                        { type : "button", button : "JustifyBlock" },
                        { type : "seperator" },
                        { type : "button", button : "Link" },
                        { type : "button", button : "Unlink" },
                        { type : "button", button : "Image" }

                        ]]
                    ]
                });

                self.$Editor.addEvent('onLoaded', function()
                {
                    if ( ( localeData.html ).toInt() === 1 )
                    {
                        self.$Editor.switchToWYSIWYG();
                        self.$Editor.showToolbar();

                    } else
                    {
                        self.$Editor.switchToSource();
                        self.$Editor.hideToolbar();
                    }

                    self.Loader.hide();
                });

                self.$Editor.inject( EditorContainer );
                self.$Editor.setContent( Cell.get('html') );
                self.$Editor.setHeight( height );
            });
        },

        /**
         * Jelper fpr edit header creation
         *
         * @param {Object} Data - row data
         * @param {Object} Column - Grid column
         * @param {Number} row . Grid row
         */
        $gridDblClickHeaderCreate : function(Data, Column, row)
        {
            var self = this;

            this.$EditorHeader.set(
                'html',

                '<div class="qui-translater-editable-header-text">' +
                    'Bearbeiten von: <b>'+ Data.groups +' - '+ Data['var'] +' ('+ Column.header +')</b>'+
                '</div>'
            );

            new QUIButton({
                textimage : 'icon-save',
                text      : 'Speichern',
                styles : {
                    'float' : 'right'
                },
                events :
                {
                    onClick : function()
                    {
                        self.Loader.show();

                        Data[ Column.dataIndex ] = self.$Editor.getContent();

                        // refresh grid
                        if ( typeof row !== 'undefined' ) {
                            self.getGrid().setDataByRow( row, Data );
                        }

                        self.$saveData( Data, function() {
                            self.Loader.hide();
                        });
                    }
                }
            }).inject( this.$EditorHeader );
        },

        /**
         * onblur on the grid
         */
        $gridBlur : function()
        {
            var Grid = this.getGrid();

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
         * @param {Object} data - row data
         * @param {Function} [callback] - optional, callback function
         */
        $saveData : function(data, callback)
        {
            Ajax.post('package_quiqqer_translator_ajax_update', function()
            {
                if ( typeof callback !== 'undefined' ) {
                    callback();
                }
            }, {
                'package' : 'quiqqer/translator',
                groups    : data.groups,
                data      : JSON.encode( data )
            });
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
                require(['package/quiqqer/translator/bin/Import'], function(Import)
                {
                    var TranslatorImport = new Import({
                        events :
                        {
                            onUpload : function() {
                                Sheet.hide();
                            }
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

            var self  = this,
                Sheet = this.createSheet();

            Sheet.addEvent('onOpen', function(Sheet)
            {
                Sheet.addButton(
                    new QUIButton({
                        text      : Locale.get( 'package/translator', 'btn.search.sheet.text' ),
                        textimage : 'icon-search',
                        events    :
                        {
                            onClick : function() {
                                Sheet.getBody().getElement( 'form').fireEvent( 'submit' );
                            }
                        }
                    })
                );

                Ajax.get( 'package_quiqqer_translator_ajax_template_search', self.$searchTemplate, {
                    'package' : 'quiqqer/translator',
                    Sheet     : Sheet
                });

                self.Loader.hide();
            });

            Sheet.show();
        },

        /**
         * set the search template into the sheet body
         *
         * @param {String} result - html template
         * @param {Object} Request - qui/classes/request/Ajax
         */
        $searchTemplate : function(result, Request)
        {
            var Form, elements;

            var Sheet = Request.getAttribute( 'Sheet' ),
                Body  = Sheet.getBody();

            Body.set( 'html', result );
            Body.setStyle( 'overflow', 'auto' );

            Form     = Body.getElement( 'form' );
            elements = Form.elements;

            var enableDisableFields = function()
            {
                var emptyTranslations = elements.emptyTranslations;

                if ( emptyTranslations.checked )
                {
                    Array.each( elements, function(Elm)
                    {
                        if ( Elm.name == 'emptyTranslations' ) {
                            return;
                        }

                        Elm.disabled = true;

                        if ( Elm.type == 'checkbox' )
                        {
                            Elm.checked = false;
                        } else
                        {
                            Elm.value = '';
                        }
                    });

                    elements.search.blur();

                    return;
                }

                Array.each( elements, function(Elm)
                {
                    if ( Elm.name == 'emptyTranslations' ) {
                        return;
                    }

                    Elm.disabled = false;

                    if ( Elm.type == 'checkbox' ) {
                        Elm.checked = true;
                    }

                    elements.search.focus();
                });
            };

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

            elements.emptyTranslations.addEvent( 'change', enableDisableFields );

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

            if ( this.getAttribute( 'search' ) ) {
                elements.emptyTranslations.checked = this.getAttribute( 'search' ).emptyTranslations;
            }


            elements.search.focus();
            enableDisableFields();


            var self = this;

            Form.addEvents({
                /**
                 * @param {DOMEvent} [event]
                 */
                submit : function(event)
                {
                    if ( typeof event !== 'undefined' ) {
                        event.stop();
                    }


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

                    self.setAttribute('search', {
                        search : elements.search.value,
                        emptyTranslations : elements.emptyTranslations.checked,
                        fields : fields
                    });

                    Sheet.hide();

                    self.$loadGrid();
                    self.resize();
                }
            });
        }
    });
});
