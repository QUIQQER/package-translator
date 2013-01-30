<?php

/**
 * Template des Translaters bekommen
 *
 * @return String
 */
function ajax_translater_template()
{
    $Engine   = QUI_Template::getEngine(true);
    $projects = QUI::getProjects();

    $Plugins  = QUI::getPlugins();
    $_plugins = $Plugins->getAvailablePlugins();

    $list   = QUI_Locale_Translater::getGroupList();
    $groups = array();

    $groups['system'] = '';

    foreach ($list as $entry) {
        $groups[$entry] = '';
    }

    // Plugins aufnehmen
    foreach ($_plugins as $Plugin) {
        $groups[ 'plugin/'. $Plugin->getAttribute('name') ] = '';
    }

    // Projekte aufnehmen
    foreach ($projects as $Project) {
        $groups[ 'project/'. $Plugin->getAttribute('name') ] = '';
    }

    ksort($groups);

    $result = array();

    foreach ($groups as $key => $value)
    {
        $str = '{"'. str_replace('/', '":{"', $key);
        $str = $str .'" : ""'. str_repeat('}', substr_count($str, '{'));

        $result = array_merge_recursive($result, json_decode($str, true));
    }

    $Engine->assign(array(
        'list' => json_encode($result)
    ));

    return Utils_Security_Orthos::removeLineBreaks(
        $Engine->fetch(SYS_DIR .'ajax/translater/template.html')
    );
}
$ajax->register('ajax_translater_template')

?>