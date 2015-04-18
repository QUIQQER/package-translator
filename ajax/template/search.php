<?php

/**
 * Template des Translaters bekommen
 *
 * @return String
 */
function package_quiqqer_translator_ajax_template_search()
{
    $Engine = \QUI::getTemplateManager()->getEngine(true);

    $languages = array();
    $result = \QUI\Translator::langs();

    foreach ($result as $lang) {
        if (strlen($lang) == 2) {
            $languages[] = $lang;
        }
    }

    $Engine->assign(array(
        'languages' => $languages
    ));

    return $Engine->fetch(
        str_replace('/ajax/template', '', dirname(__FILE__))
        .'/template/search.html'
    );
}

\QUI::$Ajax->register(
    'package_quiqqer_translator_ajax_template_search',
    false,
    'Permission::checkAdminUser'
);
