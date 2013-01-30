<?php

/**
 * Template des Translaters bekommen
 *
 * @return Array
 */
function ajax_translater_translations($groups, $params)
{
    $langs = QUI_Locale_Translater::langs();
    $data  = QUI_Locale_Translater::getData(
        $groups,
        json_decode($params, true)
    );

    foreach ($data['data'] as $key => $entry)
    {
        foreach ($langs as $lang)
        {
            if (isset($entry[$lang]) &&
                isset($entry[$lang .'_edit']) &&
                !empty($entry[$lang .'_edit']))
            {
                $data['data'][$key][$lang] = $entry[$lang .'_edit'];
            }
        }
    }

    $result = array(
        'data'  => $data,
        'langs' => $langs
    );

    return $result;
}
$ajax->register('ajax_translater_translations', array('groups', 'params'))

?>