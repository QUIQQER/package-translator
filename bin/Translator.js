/**
 * Translator Panel
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @module package/quiqqer/translator/bin/Translator
 * @package QUI.pcsg.quiqqer.js.package.translator
 * @namespace QUI
 */

define('package/quiqqer/translator/bin/Translator', [

    "package/quiqqer/translator/bin/Panel"

], function(TranslatorPanel)
{
    return function()
    {
        // opens the panel
        QUI.Controls.get( 'content-panel' )[0].appendChild(
            new TranslatorPanel()
        );
    };
});
