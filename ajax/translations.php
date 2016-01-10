<?php

/**
 * Return the translation list
 *
 * @param string $groups
 * @param string $params
 * @param string $search
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_translator_ajax_translations',
    function ($groups, $params, $search) {
        $langs = QUI\Translator::langs();
        $data  = QUI\Translator::getData(
            $groups,
            json_decode($params, true),
            json_decode($search, true)
        );

        $dev = QUI::conf('globals', 'development');

        if (!$dev) {
            foreach ($data['data'] as $key => $entry) {
                foreach ($langs as $lang) {
                    if (isset($entry[$lang])
                        && isset($entry[$lang . '_edit'])
                        && !empty($entry[$lang . '_edit'])
                    ) {
                        $data['data'][$key][$lang] = $entry[$lang . '_edit'];
                    }
                }
            }
        }

        $result = array(
            'data' => $data,
            'langs' => $langs
        );

        return $result;
    },
    array('groups', 'params', 'search'),
    'Permission::checkAdminUser'
);
