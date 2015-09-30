<?php

/**
 * Return the translation list
 *
 * @param String $groups
 * @param String $params
 * @param String $search
 *
 * @return Array
 */
function package_quiqqer_translator_ajax_translations($groups, $params, $search)
{
    $langs = \QUI\Translator::langs();
    $data = \QUI\Translator::getData(
        $groups,
        json_decode($params, true),
        json_decode($search, true)
    );

    $dev = \QUI::conf('globals', 'development');

    if (!$dev) {
        foreach ($data['data'] as $key => $entry) {
            foreach ($langs as $lang) {
                if (isset($entry[$lang])
                    || (isset($entry[$lang.'_edit'])
                    && !empty($entry[$lang.'_edit']))
                ) {
                    $data['data'][$key][$lang] = $entry[$lang.'_edit'];
                }
            }
        }
    }

    $result = array(
        'data'  => $data,
        'langs' => $langs
    );

    return $result;
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_translations',
    array('groups', 'params', 'search'),
    'Permission::checkAdminUser'
);
