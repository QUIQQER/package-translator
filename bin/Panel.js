/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/Panel
 * @package
 * @namespace QUI
 */

define('package/quiqqer/translator/bin/Panel', [

    "controls/desktop/Panel",
    "controls/buttons/Seperator",
    "controls/buttons/Select"

], function(QUI_Panel)
{
    return new Class({

        Implements : [ QUI_Panel ],
        Type       : 'QUI.controls.packages.Translator',

        Binds : [
            '$loadSubGroups'
        ],

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', 'Übersetzer' );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/flags/default.png' );

            this.init( options );
            this.addEvent( 'onCreate', this.$onCreate );

            this.$groups = {};
        },

        /**
         * Internal creation
         */
        $onCreate : function()
        {
            this.addButton(
                new QUI.controls.buttons.Select({
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
                new QUI.controls.buttons.Select({
                    name : 'translater/group/end',
                    styles : {
                        width: 100
                    }
                })
            );

            this.addButton(
                new QUI.controls.buttons.Seperator()
            );

            this.addButton(
                new QUI.controls.buttons.Button({
                    name : 'add',
                    text : 'Variable(n) hinzufügen',
                    textimage : URL_BIN_DIR +'16x16/add.png'
                })
            );

            this.addButton(
                new QUI.controls.buttons.Button({
                    name : 'del',
                    text : 'Variable(n) löschen',
                    textimage : URL_BIN_DIR +'16x16/trashcan_empty.png'
                })
            );

            this.addButton(
                new QUI.controls.buttons.Seperator()
            );

            this.addButton(
                new QUI.controls.buttons.Button({
                    name : 'import',
                    text : 'Import'
                })
            );

            this.addButton(
                new QUI.controls.buttons.Button({
                    name : 'export',
                    text : 'Export'
                })
            );

            this.$loadGroups();
        },

        /**
         * Load the groups in the DropDown
         */
        $loadGroups : function()
        {
            QUI.Ajax.get('package_quiqqer_translator_ajax_groups', function(result, Request)
            {
                var i, g, len, group;

                var Panel     = Request.getAttribute( 'Panel' ),
                    ButtonBar = Panel.getButtonBar(),
                    Sel1      = ButtonBar.getChildren( 'translater/group/begin' ),
                    Sel2      = ButtonBar.getChildren( 'translater/group/end' ),
                    groups    = Panel.$groups;

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

                Panel.$groups = groups;

                for ( g in groups ) {
                    Sel1.appendChild( g, g, URL_BIN_DIR +'16x16/flags/default.png' );
                }


            }, {
                'package' : 'quiqqer/translator',
                Panel     : this
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
                //Sel2.disable();
            } else
            {
                Sel2.open();
            }
        }
    });
});
