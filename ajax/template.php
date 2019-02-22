<?php

/**
 * Template des Translaters bekommen
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_translater_template',
    function () {
        $Engine   = QUI::getTemplateManager()->getEngine(true);
        $projects = QUI::getProjectManager()->getProjects(true);

        $Plugins  = QUI::getPlugins();
        $_plugins = $Plugins->getAvailablePlugins();

        $list   = QUI\Translator::getGroupList();
        $groups = [];

        $groups['system'] = '';

        foreach ($list as $entry) {
            $groups[$entry] = '';
        }

        // Plugins aufnehmen
        foreach ($_plugins as $Plugin) {
            $groups['plugin/'.$Plugin->getAttribute('name')] = '';
        }

        // Projekte aufnehmen
        foreach ($projects as $Project) {
            $groups['project/'.$Project->getAttribute('name')] = '';
        }

        ksort($groups);

        $result = [];

        foreach ($groups as $key => $value) {
            $str = '{"'.str_replace('/', '":{"', $key);
            $str = $str.'" : ""'.str_repeat('}', substr_count($str, '{'));

            $result = array_merge_recursive($result, json_decode($str, true));
        }

        $Engine->assign([
            'list' => json_encode($result)
        ]);

        return QUI\Utils\StringHelper::removeLineBreaks(
            $Engine->fetch(SYS_DIR.'ajax/translater/template.html')
        );
    },
    false,
    'Permission::checkAdminUser'
);
