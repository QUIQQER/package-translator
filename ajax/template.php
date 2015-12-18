<?php

/**
 * Template des Translaters bekommen
 *
 * @return String
 */
function ajax_translater_template()
{
    $Engine   = QUI::getTemplateManager()->getEngine(true);
    $projects = QUI::getProjectManager()->getProjects(true);

    $Plugins  = QUI::getPlugins();
    $_plugins = $Plugins->getAvailablePlugins();

    $list   = QUI\Translator::getGroupList();
    $groups = array();

    $groups['system'] = '';

    foreach ($list as $entry) {
        $groups[$entry] = '';
    }

    // Plugins aufnehmen
    foreach ($_plugins as $Plugin) {
        $groups['plugin/' . $Plugin->getAttribute('name')] = '';
    }

    // Projekte aufnehmen
    foreach ($projects as $Project) {
        $groups['project/' . $Project->getAttribute('name')] = '';
    }

    ksort($groups);

    $result = array();

    foreach ($groups as $key => $value) {
        $str = '{"' . str_replace('/', '":{"', $key);
        $str = $str . '" : ""' . str_repeat('}', substr_count($str, '{'));

        $result = array_merge_recursive($result, json_decode($str, true));
    }

    $Engine->assign(array(
        'list' => json_encode($result)
    ));

    return QUI\Utils\StringHelper::removeLineBreaks(
        $Engine->fetch(SYS_DIR . 'ajax/translater/template.html')
    );
}

QUI::$Ajax->register(
    'ajax_translater_template',
    false,
    'Permission::checkAdminUser'
);
