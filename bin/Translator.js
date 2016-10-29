/**
 * Global Translator Object
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package package/quiqqer/translator/bin/Translator
 *
 * @require package/quiqqer/translator/bin/classes/Translator
 */
define('package/quiqqer/translator/bin/Translator', [
    'package/quiqqer/translator/bin/classes/Translator'
], function (Translator) {
    "use strict";
    return new Translator();
});
