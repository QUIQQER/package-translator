
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

define('package/quiqqer/translator/bin/Panel', [

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
            '$attentionBox',
            'addTranslationGroup'
        ],

        options : {
            field  : '',
            order  : '',
            limit  : 500,
            page   : 1,
            search : false
        },

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', Locale.get( 'quiqqer/translator', 'panel.title' ) );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/flags/default.png' );

            this.parent( options );

            this.addEvents({
                onCreate  : this.$onCreate,
                onResize  : this.$onResize,
                onRefresh : this.$onRefresh
            });

            this.$Container  = null;
            this.$Grid       = null;
            this.$Editor     = null;
            this.$groups     = {};
            this.$groupcount = 0;
            this.$langs      = [];

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

            Ajax.get( 'package_quiqqer_translator_ajax_translations', function(result)
            {
                self.getGrid().setData( result.data );
                self.$gridBlur();
                self.$attentionBox();

                self.Loader.hide();
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
            var id      = this.getId(),
                group   = this.getTranslationGroup(),
                devMode = ( QUIQQER_CONFIG.globals.development ).toInt(),

                content = '<div class="qui-translator-export-group">' +
                              '<h3>' + Locale.get( 'quiqqer/translator', 'export.window.group' ) + '</h3>' +
                              '<input id="qui-translator-export-group-current" type="radio" name="export_group" value="' + group + '" checked="checked"/>' +
                              '<label for="qui-translator-export-group-current">' +
                                  Locale.get( 'quiqqer/translator', 'export.window.group.current.label', { group : group } ) +
                              '</label>' +
                              '<input id="qui-translator-export-group-all" type="radio" name="export_group" value="all"/>' +
                              '<label for="qui-translator-export-group-all">' +
                                  Locale.get( 'quiqqer/translator', 'export.window.group.all.label', { count : this.$groupcount } ) +
                              '</label>' +
                           '</div>' +
                           '<div class="qui-translator-export-language">' +
                              '<h3>' + Locale.get( 'quiqqer/translator', 'export.window.language' ) + '</h3>' +
                           '</div>' +
                           '<div class="qui-translator-export-type">' +
                                '<h3>' + Locale.get( 'quiqqer/translator', 'export.window.type' ) + '</h3>' +
                                '<input id="qui-translator-export-type-original" type="radio" name="export_type" value="original" checked="checked"/>' +
                                '<label for="qui-translator-export-type-original">' +
                                    Locale.get( 'quiqqer/translator', 'export.window.type.original.label' ) +
                                '</label>' +
                                '<input id="qui-translator-export-type-edit" type="radio" name="export_type" value="edit"/>' +
                                '<label for="qui-translator-export-type-edit">' +
                                    Locale.get( 'quiqqer/translator', 'export.window.type.edit.label' ) +
                                '</label>' +
                                '<input id="qui-translator-export-type-edit-overwrite" type="checkbox" name="export_type_overwrite" checked="checked"/>' +
                                '<label for="qui-translator-export-type-edit-overwrite">' +
                                    Locale.get( 'quiqqer/translator', 'export.window.type.edit.overwrite.label' ) +
                                '</label>' +
                           '</div>';

            var ConfirmWindow = new QUIConfirm({
                title : Locale.get( 'quiqqer/translator', 'export.window.title' ),
                icon  : 'icon-download',

                events :
                {
                    onSubmit : function(Win)
                    {
                        var i, len;
                        var Body      = Win.getContent(),
                            grp       = Body.getElement( '#qui-translator-export-group-current' ),
                            langs     = Body.getElements( '.qui-translator-export-language input' ),
                            type      = Body.getElement( '#qui-translator-export-type-original'),
                            overwrite = Body.getElement( '#qui-translator-export-type-edit-overwrite'),
                            _group    = 'all',
                            _langs    = [],
                            _type     = 'original';

                        // Gruppe
                        if ( grp.checked ) {
                            _group = group;
                        }

                        // Sprachen
                        for ( i = 0, len = langs.length; i < len; i++ )
                        {
                            if ( !langs[ i ].checked ) {
                                continue;
                            }

                            _langs.push ( langs[ i ].value );
                        }

                        // Typ
                        if ( !type.checked )
                        {
                            if( !overwrite.checked )
                            {
                                _type = 'edit';
                            } else
                            {
                                _type = 'edit_overwrite';
                            }
                        }

                        require(['DownloadManager'], function(DownloadManager)
                        {
                            DownloadManager.download( 'package_quiqqer_translator_ajax_export', {
                                'package' : 'quiqqer/translator',
                                group     : _group,
                                langs     : JSON.encode( _langs ),
                                type      : _type
                            });
                        });
                    }
                }
            });

            ConfirmWindow.create();
            ConfirmWindow.setContent( content );

            // Sprachen reinladen
            var l,
                Langs  = ConfirmWindow.getContent().getElement( '.qui-translator-export-language' ),
                langs  = this.$langs;

            for ( var i = 0, len = langs.length; i < len; i++ )
            {
                l = langs[ i ];

                new Element( 'input', {
                    type    : 'checkbox',
                    name    : 'export_language',
                    value   : l,
                    id      : 'qui-translator-export-lang-' + l,
                    checked : 'checked'
                }).inject( Langs );

                new Element( 'label', {
                    'for' : 'qui-translator-export-lang-' + l,
                    html  : l
                }).inject( Langs );
            }

            // Edit-Option
            var Content        = ConfirmWindow.getContent(),
                OrigOption     = Content.getElement( '#qui-translator-export-type-original'),
                OrigLabel      = Content.getElement( 'label[for="qui-translator-export-type-original"]' ),
                EditOption     = Content.getElement( '#qui-translator-export-type-edit' ),
                EditLabel      = Content.getElement( 'label[for="qui-translator-export-type-edit"]' ),
                Overwrite      = Content.getElement( '#qui-translator-export-type-edit-overwrite' ),
                OverwriteLabel = Content.getElement( 'label[for="qui-translator-export-type-edit-overwrite"]' );

            if ( !devMode )
            {
                OrigOption.setStyle( 'display', 'none' );
                OrigLabel.setStyle( 'display', 'none' );

                EditOption.checked = true;
                EditOption.setStyle( 'display', 'none' );
                EditLabel.setStyle( 'display', 'none' );

                ConfirmWindow.open();
                return;
            }

            Overwrite.setStyle( 'display', 'none' );
            OverwriteLabel.setStyle( 'display', 'none' );

            EditOption.addEvent( 'click', function(event)
            {
                Overwrite.setStyle( 'display', '' );
                OverwriteLabel.setStyle( 'display', '' );
            });

            OrigOption.addEvent( 'click', function(event)
            {
                Overwrite.setStyle( 'display', 'none' );
                OverwriteLabel.setStyle( 'display', 'none' );
            });

            ConfirmWindow.open();
        },

//        /**
//         * Opens the add variable dialog
//         */
//        addVariable : function()
//        {
//            var self = this;
//
//            require(['package/quiqqer/translator/bin/AddVariable'], function(add) {
//                add( self );
//            });
//        },

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
                    header    : Locale.get( 'quiqqer/translator', 'grid.title.variable' ),
                    dataIndex : 'var',
                    dataType  : 'string',
                    width     : 150,
                    editable  : true
                });

                if ( self.getAttribute( 'search' ) )
                {
                    cols.push({
                        header    : Locale.get( 'quiqqer/translator', 'grid.title.group' ),
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
                    header    : Locale.get( 'quiqqer/translator', 'grid.title.type' ),
                    dataIndex : 'datatype',
                    dataType  : 'string',
                    width     : 50,
                    editable  : true
                });

                cols.push({
                    header    : Locale.get( 'quiqqer/translator', 'grid.title.html' ),
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
                        text      : Locale.get( 'quiqqer/translator', 'btn.add.var.text' ),
                        textimage : 'icon-plus',
                        events    : {
                            onClick : self.addVariable
                        }
                    }, {
                        name      : 'del',
                        text      : Locale.get( 'quiqqer/translator', 'btn.del.var.text' ),
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

                self.$langs = langs;

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
                title : Locale.get( 'quiqqer/translator', 'btn.search.title' ),
                alt   : Locale.get( 'quiqqer/translator', 'btn.search.alt' ),
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
                        width: '17%'
                    },
                    events :
                    {
                        onChange : function(value)
                        {
                            var ButtonBar = self.getButtonBar(),
                                ImportBtn = ButtonBar.getChildren( 'import' ),
                                ExportBtn = ButtonBar.getChildren( 'export' ),
                                Grid      = self.getGrid();

                            ImportBtn.disable();
                            ExportBtn.disable();

                            if ( Grid )
                            {
                                var i, len;
                                var gridBtns = Grid.getButtons();

                                for ( i = 0, len = gridBtns.length; i < len; i++ ) {
                                    gridBtns[ i ].disable();
                                }
                            }

                            self.$loadSubGroups( value );
                        }
                    }
                })
            );

            this.addButton(
                new QUISelect({
                    name   : 'translater/group/end',
                    styles : {
                        width: '17%'
                    },
                    events :
                    {
                        onChange : function()
                        {
                            var ButtonBar = self.getButtonBar(),
                                ImportBtn = ButtonBar.getChildren( 'import' ),
                                ExportBtn = ButtonBar.getChildren( 'export' ),
                                Grid      = self.getGrid();

                            ImportBtn.enable();
                            ExportBtn.enable();

                            if ( Grid )
                            {
                                var i, len;
                                var gridBtns = Grid.getButtons();

                                for ( i = 0, len = gridBtns.length; i < len; i++ ) {
                                    gridBtns[ i ].enable();
                                }
                            }

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
                text      : Locale.get( 'quiqqer/translator', 'btn.import.text' ),
                textimage : 'icon-upload',
                disabled  : false,
                events : {
                    onClick : this.importTranslation
                }
            });

            this.addButton({
                name      : 'export',
                text      : Locale.get( 'quiqqer/translator', 'btn.export.text' ),
                textimage : 'icon-download',
                disabled  : false,
                events : {
                    onClick : this.exportGroup
                }
            });

            this.addButton( new QUIButtonSeperator() );

            this.addButton({
                name      : 'publish',
                text      : Locale.get( 'quiqqer/translator', 'btn.publish.text' ),
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

                    groups = {};

                // clear first
                Sel1.clear();
                Sel2.clear();

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

                self.$groups     = groups;
                self.$groupcount = result.length;

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

            var onClick = function()
            {
                self.Loader.show();

                self.setAttribute( 'search', false );
                self.setAttribute( 'page', 1 );

                var Bar = self.getButtonBar();

                Bar.getChildren( 'translater/group/begin' ).enable();
                Bar.getChildren( 'translater/group/end' ).enable();
                Bar.getChildren( 'export' ).enable();

                self.$loadGrid();
                self.resize();
            };

            require(['qui/controls/messages/Attention'], function(Attention)
            {
                new Attention({
                    message : Locale.get( 'quiqqer/translator', 'search.params.active' ),
                    events  :
                    {
                        onClick : function(Message)
                        {
                            Message.destroy();
                            onClick();
                        },

                        onDestroy : onClick
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

                self.$EditorContainer = EditorContainer;

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

                self.$Editor.setAttribute( 'showLoader', false );

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

                    self.$Editor.setHeight( height );
                    self.Loader.hide();
                });

                self.$Editor.inject( EditorContainer );
                self.$Editor.setContent( Cell.get('text') );
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
                text      : Locale.get( 'quiqqer/translator', 'edit.btn.save' ),
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

            new QUIButton({
                textimage : 'icon-remove',
                text      : Locale.get( 'quiqqer/translator', 'edit.btn.close' ),
                styles : {
                    'float'     : 'right',
                    marginRight : 5
                },
                events :
                {
                    onClick : function()
                    {
                        self.$EditorHeader.destroy();
                        self.$EditorContainer.destroy();
                        self.$Editor = null;
                        self.fireEvent( 'onResize' );
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

            var self              = this,
                devMode           = ( QUIQQER_CONFIG.globals.development ).toInt(),
                overwriteOriginal = false;

            require( [ 'controls/upload/Form' ], function(UploadForm)
            {
                var Popup = new QUIConfirm({
                    icon      : 'icon-file-upload',
                    title     : Locale.get( 'quiqqer/translator', 'import.window.title' ),
                    maxWidth  : 425,
                    maxHeight : 325,
                    events    :
                    {
                        onSubmit : function(Win) {
                            LogoUploadForm.submit();
                        }
                    }
                });

                Popup.create();

                var content = '<p class="qui-translator-import-descripton">' +
                                Locale.get( 'quiqqer/translator', 'import.window.description' ) +
                              '<p>';

                if ( devMode )
                {
                    content += '<input type="checkbox" id="qui-translator-import-overwrite"/>' +
                               '<label for="qui-translator-import-overwrite">' +
                                    Locale.get( 'quiqqer/translator', 'import.window.overwrite.label' ) +
                               '</label>'
                }

                Popup.setContent( content );

                var LogoUploadForm = new UploadForm({
                    multible   : false,
                    sendbutton : false,
//                maxuploads : 5,
                    uploads    : 1,
                    styles     : {
                        clear  : 'both',
                        float  : 'left',
                        margin : 0,
                        width  : '100%',
                        marginTop : 25,
                    },
                    events     :
                    {
                        onComplete : function(Control, File, result)
                        {
                            self.Loader.hide();
                            self.$Grid.refresh();
                            Popup.close();
                        },

                        onAdd : function(Control, File)
                        {
                            if ( typeof FileReader === 'undefined' ) {
                                return;
                            }

                            var Reader = new FileReader();

                            Reader.readAsDataURL( File );
                        },

                        onError : function(Control, Error)
                        {
                            QUI.getMessageHandler(function(MH)
                            {
                                MH.addError(
                                    Error.getAttribute( 'message' ),
                                    self.$ImgDrop
                                );
                            });
                        },

                        onSubmit : function(data, Control)
                        {
                            self.Loader.show();

                            var Overwrite = Popup.getContent().getElement( '#qui-translator-import-overwrite');

                            var checked = Overwrite ? Overwrite.checked : false;

                            Control.setParam(
                                'overwriteOriginal',
                                checked ? 1 : 0
                            );
                        }
                    }
                });

                LogoUploadForm.setParam(
                    'onfinish',
                    'package_quiqqer_translator_ajax_import'
                );

                LogoUploadForm.setParam( 'package', 'quiqqer/translator' );

                LogoUploadForm.inject( Popup.getContent() );

                Popup.open();

                self.Loader.hide();
            });
        },

        addVariable : function()
        {
            var self = this;

            var content = '<div class="qui-translator-add-variable">' +
                              '<h3>' +
                                Locale.get( 'quiqqer/translator', 'add.window.text' ) +
                              '</h3>' +
                              '<span class="qui-translator-add-variable-group">' +
                                Locale.get( 'quiqqer/translator', 'add.window.group', { group : this.getTranslationGroup() } ) +
                              '</span>' +
                              '<div class="qui-translator-add-variable-labels">' +
                              '<label for="qui-translator-add-variable-maingroup">' +
                                Locale.get( 'quiqqer/translator', 'add.window.maingroup.label' ) +
                              '</label>' +
                              '<label for="qui-translator-add-variable-subgroup">' +
                                Locale.get( 'quiqqer/translator', 'add.window.subgroup.label' ) +
                              '</label>' +
                              '<label for="qui-translator-add-variable-variable">' +
                                Locale.get( 'quiqqer/translator', 'add.window.variable.label' ) +
                              '</label>' +
                              '</div>' +
                              '<div class="qui-translator-add-variable-inputs">' +
                              '<input type="text" name="qui-translator-add-variable-maingroup"' +
                              ' placeholder="' +
                                Locale.get( 'quiqqer/translator', 'add.window.maingroup.placeholder' ) +
                              '" id="qui-translator-add-variable-group"/>' +
                              '<input type="text" name="qui-translator-add-variable-subgroup"' +
                              ' placeholder="' +
                                Locale.get( 'quiqqer/translator', 'add.window.subgroup.placeholder' ) +
                              '" id="qui-translator-add-variable-group"/>' +
                              '<input type="text" name="qui-translator-add-variable-variable"' +
                              ' placeholder="' +
                                Locale.get( 'quiqqer/translator', 'add.window.variable.placeholder' ) +
                              '" id="qui-translator-add-variable-variable"/>' +
                              '</div>' +
                          '</div>';

            var ConfirmWindow = new QUIConfirm({
                title : Locale.get( 'quiqqer/translator', 'add.window.title' ),
                icon  : 'icon-plus-sign-alt',
                autoclose : false,

                maxHeight : 375,
                maxWidth  : 600,

                events :
                {
                    onSubmit : function(Win)
                    {
                        if ( GroupInput.value.trim() === '' )
                        {
                            GroupInput.focus();
                            return false;
                        }

                        if ( SubGroupInput.value.trim() === '' )
                        {
                            SubGroupInput.focus();
                            return false;
                        }

                        if ( VariableInput.value.trim() === '' )
                        {
                            VariableInput.focus();
                            return false;
                        }

                        self.Loader.show();

                        Ajax.post('package_quiqqer_translator_ajax_add', function()
                        {
                            Win.close();

                            self.$loadGroups();
                            self.$Grid.refresh();
                            self.Loader.hide();
                        }, {
                            'package' : 'quiqqer/translator',
                            group     : GroupInput.value.trim() + '/' + SubGroupInput.value.trim(),
                            'var'     : VariableInput.value.trim()
                        });
                    },

                    onOpen : function(Win) {
                        VariableInput.focus();
                    }
                }
            });

            ConfirmWindow.create();
            ConfirmWindow.setContent( content );

            var Content       = ConfirmWindow.getContent(),
                GroupInput    = Content.getElement( 'input[name="qui-translator-add-variable-maingroup"]' ),
                GroupLabel    = Content.getElement( 'label[for="qui-translator-add-variable-maingroup"]'),
                SubGroupInput = Content.getElement( 'input[name="qui-translator-add-variable-subgroup"]' ),
                SubGroupLabel = Content.getElement( 'label[for="qui-translator-add-variable-subgroup"]'),
                VariableInput = Content.getElement( 'input[name="qui-translator-add-variable-variable"]'),
                group         = this.getTranslationGroup().split( '/'),
                GroupSpan     = Content.getElement( 'span' );

            if ( typeof group[ 0 ] !== 'undefined' ) {
                GroupInput.value = group[ 0 ];
            }

            if ( typeof group[ 1 ] !== 'undefined' ) {
                SubGroupInput.value = group[ 1 ];
            }

            GroupInput.setStyle( 'display', 'none' );
            GroupLabel.setStyle( 'display', 'none' );
            SubGroupInput.setStyle( 'display', 'none' );
            SubGroupLabel.setStyle( 'display', 'none' );

            var onEnter = function(event)
            {
                if ( typeof event !== 'undefined' &&
                    event.code === 13 )
                {
                    ConfirmWindow.submit();
                }
            };

            GroupInput.addEvent('keydown', onEnter );
            SubGroupInput.addEvent( 'keydown', onEnter );
            VariableInput.addEvent( 'keydown', onEnter );

            new QUIButton({
                textimage : 'icon-edit',
                text      : Locale.get( 'quiqqer/translator', 'add.window.edit.btn.text' ),
                alt       : Locale.get( 'quiqqer/translator', 'add.window.edit.btn.text' ),
                title     : Locale.get( 'quiqqer/translator', 'add.window.edit.btn.text' ),
                styles    : {
                    float : 'right'
                },
                events :
                {
                    onClick : function(Btn)
                    {
                        GroupInput.setStyle( 'display', '' );
                        GroupLabel.setStyle( 'display', '' );
                        SubGroupInput.setStyle( 'display', '' );
                        SubGroupLabel.setStyle( 'display', '' );

                        GroupInput.select();
                        GroupInput.focus();

                        GroupSpan.setStyle( 'display', 'none' );
                    }
                }
            }).inject( GroupSpan );

            ConfirmWindow.open();
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
                        text      : Locale.get( 'quiqqer/translator', 'btn.search.sheet.text' ),
                        textimage : 'icon-search',
                        events    :
                        {
                            onClick : function()
                            {
                                self.Loader.show();
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

            if ( this.getAttribute( 'search' ) )
            {
                elements.emptyTranslations.checked = this.getAttribute( 'search' ).emptyTranslations;
            } else if ( !this.getAttribute( 'search' ) )
            {
                elements.emptyTranslations.checked = false;
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
