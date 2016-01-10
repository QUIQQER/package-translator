<?php

/**
 * Return the translations for the JavaScript Locale
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_refreshLocale',
    function () {
        $result       = array();
        $translations = QUI\Translator::get();

        $langs = QUI\Translator::getAvailableLanguages();

        foreach ($translations as $entry) {
            if (strpos($entry['datatype'], 'js') === false) {
                continue;
            }

            $group = $entry['groups'];
            $var   = $entry['var'];

            foreach ($langs as $lang) {
                if (!isset($entry[$lang])) {
                    continue;
                }

                $value = $entry[$lang];

                if (isset($entry[$lang . '_edit'])
                    && !empty($entry[$lang . '_edit'])
                ) {
                    $value = $entry[$lang . '_edit'];
                }

                $result[$lang][$group][$var] = $value;
            }
        }

        return $result;
    },
    false,
    'Permission::checkAdminUser'
);
