/**
 * Comment here
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module
 * @package
 * @namespace
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

        initialize: function(options)
        {
            // defaults
            this.setAttribute( 'title', 'Übersetzer' );
            this.setAttribute( 'icon', URL_BIN_DIR +'16x16/flags/default.png' );

            this.init( options );
            this.addEvent( 'onCreate', this.$onCreate );
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
                    textimage : URL_BIN_DIR +'16x16/trashcan_empty'
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

            }, {
                'package' : 'quiqqer/translator'
            });
        }

    });
});
