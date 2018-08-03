<?php

/**
 * This file contains QUI\Translator
 */

namespace QUI;

use QUI;
use QUI\Utils\Text\XML;
use QUI\Utils\StringHelper;
use QUI\Utils\System\File as QUIFile;
use QUI\Cache\Manager as CacheManager;

/**
 * QUIQQER Translator
 *
 * Manage all translations, for the system and the plugins
 *
 * @author  www.pcsg.de (Henning Leutz)
 *
 * mysql fix for old dev version
 *
 * UPDATE translate
 * SET groups = REPLACE(groups, '\'', '') WHERE 1;
 * UPDATE translate
 * SET var = REPLACE(var, '\'', '') WHERE 1
 */
class Translator
{
    const ERROR_CODE_VAR_EXISTS = 601;

    /**
     * @var string
     */
    const EXPORT_DIR = 'translator_exports/';

    /**
     * @var string
     */
    protected static $cacheName = 'translator';

    /**
     * @var null
     */
    protected static $localeModifyTimes = null;

    /**
     * Return the real table name
     *
     * @return String
     */
    public static function table()
    {
        return QUI::getDBTableName('translate');
    }

    /**
     * Translator setup
     * it looks, which languages are exist and create it
     */
    public static function setup()
    {
    }

    /**
     * Add / create a new language
     *
     * @param String $lang - lang code, length must be 2 signs
     *
     * @throws QUI\Exception
     * @throws \Exception
     */
    public static function addLang($lang)
    {
        if (strlen($lang) !== 2) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.lang.shortcut.not.allowed'
                )
            );
        }

        // if column already exists, don't refresh locale
        $exists = QUI::getDataBase()->table()->existColumnInTable(self::table(), $lang);

        if ($exists) {
            return;
        }

        QUI::getDataBase()->table()->addColumn(
            self::table(),
            [
                $lang         => 'text NULL',
                $lang.'_edit' => 'text NULL'
            ]
        );

        if (file_exists(VAR_DIR.'locale/localefiles')) {
            unlink(VAR_DIR.'locale/localefiles');
        }
    }

    /**
     * Export locale groups as xml
     *
     * @param String $group - which group should be exported? ("all" = Alle)
     * @param array $langs - Sprachen
     * @param string $type - "original" oder "edit"
     * @param bool $external (optional) - export translations of external groups
     * that are overwritten by the selected groups ($group) [default: false]
     *
     * @return String
     */
    public static function export($group, $langs, $type, $external = false)
    {
        $exportFolder = VAR_DIR.self::EXPORT_DIR;

        // Var-Folder für Export Dateien erstellen, falls nicht vorhanden
        QUI\Utils\System\File::mkdir($exportFolder);

        $fileName = $exportFolder.'translator_export';

        // Alle Gruppen
        if ($group === 'all') {
            $groups   = self::getGroupList();
            $fileName .= '_all';
        } else {
            $groups   = [$group];
            $fileName .= '_'.str_replace('/', '_', $group);
        }

        $fileName .= '_'.mb_substr(md5(microtime()), 0, 6).'.xml';

        $result = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $result .= '<locales>'.PHP_EOL;

        foreach ($groups as $grp) {
            $result .= self::createXMLContent($grp, $langs, $type, $external);
        }

        $result .= '</locales>';

        // Temp-Datei erzeugen
        file_put_contents($fileName, $result);

        return $fileName;
    }

    /**
     * Erstellt den Inhalt für eine locale.xml für eine oder mehrere Gruppen/Sprachen
     *
     * @param array $group
     * @param array $langs
     * @param string $editType - original, edit, edit_overwrite
     * @param bool $external (optional) - include translations of external groups
     * that are overwritten by the selected groups ($group) [default: false]
     *
     * @return string
     */
    protected static function createXMLContent($group, $langs, $editType, $external = false)
    {
        if ($external) {
            $entries = self::get(false, false, $group);
        } else {
            $entries = self::get($group);
        }

        $pool = [];

        foreach ($entries as $entry) {
            // Undefinierte Gruppen ausschließen
            if (!isset($entry['groups'])) {
                continue;
            }

            if (!$external && mb_strpos($entry['groups'], $group) === false) {
                QUI\System\Log::addError(
                    'Translator Export: xml-Gruppe ('.$entry['groups'].')'.
                    ' passt nicht zur Translator-Gruppe ('.$group.')'
                );

                continue;
            }

            $group = $entry['groups'];
            $type  = 'php';

            if (isset($entry['datatype']) && !empty($entry['datatype'])) {
                $type = $entry['datatype'];
            }

            if (!isset($pool[$type])) {
                $pool[$type] = [];
            }

            if (!isset($pool[$type][$group])) {
                $pool[$type][$group] = [];
            }

            $pool[$type][$group][] = $entry;
        }

        $result = '';

        foreach ($pool as $type => $groups) {
            foreach ($groups as $group => $entries) {
                $result .= '<groups name="'.$group.'" datatype="'.$type.'">'.PHP_EOL;

                foreach ($entries as $entry) {
                    $result .= "\t".'<locale name="'.$entry['var'].'"';

                    if (isset($entry['html']) && $entry['html'] == 1) {
                        $result .= ' html="true"';
                    }

                    if (!empty($entry['priority'])) {
                        $result .= ' priority="'.(int)$entry['priority'].'"';
                    }

                    if (!empty($entry['package'])) {
                        $result .= ' package="'.$entry['package'].'"';
                    }

                    $result .= '>'.PHP_EOL;

                    foreach ($langs as $lang) {
                        $var = '';

                        switch ($editType) {
                            case 'edit':
                                if (isset($entry[$lang.'_edit'])
                                    && !empty($entry[$lang.'_edit'])
                                ) {
                                    $var = $entry[$lang.'_edit'];
                                }
                                break;

                            case 'edit_overwrite':
                                if (isset($entry[$lang.'_edit'])
                                    && !empty($entry[$lang.'_edit'])
                                ) {
                                    $var = $entry[$lang.'_edit'];
                                } else {
                                    if (isset($entry[$lang])
                                        && !empty($entry[$lang])
                                    ) {
                                        $var = $entry[$lang];
                                    }
                                }
                                break;

                            default:
                                if (isset($entry[$lang]) && !empty($entry[$lang])) {
                                    $var = $entry[$lang];
                                }
                        }

                        $result .= "\t\t".'<'.$lang.'>';
                        $result .= '<![CDATA['.$var.']]>';
                        $result .= '</'.$lang.'>'.PHP_EOL;
                    }

                    $result .= "\t".'</locale>'.PHP_EOL;
                }

                $result .= '</groups>'.PHP_EOL;
            }
        }

        return $result;
    }

    /**
     * Import a locale xml file
     *
     * @param string $file - path to the file
     * @param bool|integer $overwriteOriginal - if true, the _edit fields would be updated
     *                                     if false, the original fields would be updated
     * @param bool $devModeIgnore
     * @param string $packageName - name of the package
     * @param bool $force - The translation should really be executed, the $filemtimes is ignored
     *
     * @return array - List of imported vars
     * @throws QUI\Exception
     */
    public static function import(
        $file,
        $overwriteOriginal = 0,
        $devModeIgnore = false,
        $packageName = '',
        $force = false
    ) {
        if (!file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.lang.file.not.exist'
                )
            );
        }

        $filemtimes = self::getLocaleModifyTimes();

        // nothing has changed
        if ($force === false && isset($filemtimes[$file]) && filemtime($file) <= $filemtimes[$file]) {
            return [];
        }

        $result  = [];
        $devMode = QUI::conf('globals', 'development');

        if ($devModeIgnore) {
            $devMode = true;
        }

        // Format-Prüfung
        try {
            $groups = XML::getLocaleGroupsFromDom(
                XML::getDomFromXml($file)
            );
        } catch (\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.import.wrong.format',
                    ['file' => $file]
                )
            );
        }

        if (empty($groups)) {
            self::setLocaleFileModifyTime($file);

            return [];
        }

        set_time_limit(ini_get('max_execution_time'));

        foreach ($groups as $locales) {
            $group    = $locales['group'];
            $datatype = '';

            if (isset($locales['datatype'])) {
                $datatype = $locales['datatype'];
            }

            foreach ($locales['locales'] as $locale) {
                $var = $locale['name'];

                unset($locale['name']);

                if (!isset($locale['html'])) {
                    $locale['html'] = 0;
                }

                if ($locale['html']) {
                    $locale['html'] = 1;
                } else {
                    $locale['html'] = 0;
                }

                if (empty($locale['priority'])) {
                    $locale['priority'] = 0;
                } else {
                    $locale['priority'] = (int)$locale['priority'];
                }

                $localePackageName = $packageName;

                if (empty($localePackageName) && !empty($locale['package'])) {
                    $localePackageName = $locale['package'];
                }

                try {
                    self::add($group, $var, $localePackageName);
                } catch (QUI\Exception $Exception) {
                    if ($Exception->getCode() !== self::ERROR_CODE_VAR_EXISTS) {
                        QUI\System\Log::writeException($Exception);
                    }
                }

                // test if group exists
                $groupContent = self::get($group, $var, $localePackageName);

                if (empty($groupContent)) {
                    continue;
                }

                if ($overwriteOriginal && $devMode) {
                    // set the original fields
                    $locale['datatype'] = $datatype;
                    self::update($group, $var, $localePackageName, $locale);
                } else {
                    // update only _edit fields
                    $_locale = [
                        'datatype' => $datatype,
                        'html'     => $locale['html'],
                        'priority' => $locale['priority'],
                        'package'  => $localePackageName
                    ];

                    unset($locale['html']);
                    unset($locale['priority']);
                    unset($locale['id']);

                    foreach ($locale as $key => $entry) {
                        $_locale[$key.'_edit'] = $entry;
                    }

                    self::edit($group, $var, $localePackageName, $_locale);
                }

                $result[] = [
                    'group'  => $group,
                    'var'    => $var,
                    'locale' => $locale,
                ];
            }
        }

        self::setLocaleFileModifyTime($file);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/translator',
                'import.success'
            )
        );

        return $result;
    }

    /**
     * Imports all locale.xml files within the given package as batch
     *
     * @param Package\Package $Package
     *
     * @throws Exception
     */
    public static function batchImportFromPackage(QUI\Package\Package $Package)
    {
        $file = $Package->getXMLFile('locale.xml');

        if (!file_exists($file)) {
            return;
        }

        self::batchImport($file, $Package->getName());

        try {
            $Dom      = XML::getDomFromXml($file);
            $fileList = $Dom->getElementsByTagName('file');

            /** @var \DOMElement $File */
            foreach ($fileList as $File) {
                $filePath    = $Package->getDir().ltrim($File->getAttribute('file'), '/');
                $packageName = $Package->getName();

                if ($File->hasAttribute('package')) {
                    $packageName = $File->getAttribute('package');
                }

                if (!file_exists($filePath)) {
                    continue;
                }

                self::batchImport($filePath, $packageName);
            }
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Starts a mass import of the whole locale.xml file.
     * The locale.xml will be inserted in one query of multiple INSERT IGNORE statements.
     *
     * Note:
     * This does not recurse into locale.xml files defined by <file> tags
     *
     * @param $file - Full system filepath to the locale.xml
     * @param string $packageName - The package name of the locale.xml
     *
     * @return bool - Returns true on success
     * @throws Exception
     *
     * @todo prepared statements
     */
    public static function batchImport($file, $packageName = '')
    {
        if (!file_exists($file)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.lang.file.not.exist'
                )
            );
        }

        // Check xml format
        try {
            $groups = XML::getLocaleGroupsFromDom(
                XML::getDomFromXml($file)
            );
        } catch (\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.import.wrong.format',
                    ['file' => $file]
                )
            );
        }

        if (empty($groups)) {
            self::setLocaleFileModifyTime($file);

            return 0;
        }

        // *********************************** //
        //         Database Operations
        // *********************************** //

        $PDO = QUI::getDataBase()->getPDO();
        set_time_limit(ini_get('max_execution_time'));

        $localeVariables = [];

        foreach ($groups as $locales) {
            $group    = $locales['group'];
            $datatype = '';

            if (isset($locales['datatype'])) {
                $datatype = $locales['datatype'];
            }

            foreach ($locales['locales'] as $locale) {
                $var = $locale['name'];

                unset($locale['name']);

                if (!isset($locale['html'])) {
                    $locale['html'] = 0;
                }

                if ($locale['html']) {
                    $locale['html'] = 1;
                } else {
                    $locale['html'] = 0;
                }

                if (empty($locale['priority'])) {
                    $locale['priority'] = 0;
                } else {
                    $locale['priority'] = (int)$locale['priority'];
                }

                $localePackageName = $packageName;

                if (empty($localePackageName) && !empty($locale['package'])) {
                    $localePackageName = $locale['package'];
                }

                // Add locale variable to the batch
                $localeVariable = [
                    'group'    => $group,
                    'var'      => $var,
                    'datatype' => $datatype,
                    'html'     => $locale['html'],
                    'priority' => $locale['priority'],
                    'package'  => $localePackageName
                ];

                foreach (self::langs() as $lang) {
                    if (isset($locale[$lang])) {
                        $localeVariable[$lang] = $locale[$lang];
                    }
                }

                // Add the data into the array using the key: group/variable
                $localeVariables[trim($group)."/".trim($var)] = $localeVariable;
            }
        }

        $sql = "";
        // ************************** //
        //           Update
        // ************************** //
        $currentRows = QUI::getDataBase()->fetch([
            "select" => [
                "id",
                "groups",
                "var",
            ],
            "from"   => self::table(),
            "where"  => [
                "package" => $packageName
            ]
        ]);

        foreach ($currentRows as $currentRow) {
            $varGroup = trim($currentRow['groups']);
            $varName  = trim($currentRow['var']);

            // Check if this xml contains the locale variable.
            // if it does not contain it, skip it
            if (!isset($localeVariables[$varGroup."/".$varName])) {
                continue;
            }

            $var = $localeVariables[$varGroup."/".$varName];

            // Build a string containing all languages
            $updateFieldString = "";

            foreach (self::langs() as $langCode) {
                if (isset($var[$langCode])) {
                    $updateFieldString .= $langCode."=".$PDO->quote($var[$langCode]).", ";
                }
            }

            $updateFieldString .= "datatype=".$PDO->quote($var['datatype']).", ";
            $updateFieldString = trim($updateFieldString, ", ");

            if (empty($updateFieldString)) {
                continue;
            }

            $sql .= "UPDATE ".self::table();
            $sql .= " SET ";
            $sql .= $updateFieldString;
            $sql .= " WHERE id=".$PDO->quote($currentRow['id']).";";
            $sql .= PHP_EOL;

            unset($localeVariables[$varGroup."/".$varName]);
        }

        // ************************** //
        //           Insert
        // ************************** //

        $langColumns = implode(",", self::langs());

        foreach ($localeVariables as $var) {
            //Check if at least one active language will be inserted
            // @TODO check if this part can be improved
            $containsActiveLanguage = false;

            foreach (self::langs() as $langCode) {
                if (isset($var[$langCode])) {
                    $containsActiveLanguage = true;
                }
            }

            if (!$containsActiveLanguage) {
                continue;
            }

            // Insert the locale variable
            $langValues = "";

            foreach (self::langs() as $langCode) {
                if (!isset($var[$langCode])) {
                    $langValues .= "null".",";
                    continue;
                }

                $langValues .= $PDO->quote($var[$langCode]).",";
            }

            $langValues = trim($langValues, ", ");

            $sql .= "INSERT INTO `".self::table()."` ";
            $sql .= " (groups, var, datatype, html, priority, package, ".$langColumns.")";

            // Build the value clause VALUES('','',[...])
            $sql .= " VALUES (";
            $sql .= $PDO->quote($var['group']).",";
            $sql .= $PDO->quote($var['var']).",";
            $sql .= $PDO->quote($var['datatype']).",";
            $sql .= $PDO->quote($var['html']).",";
            $sql .= $PDO->quote($var['priority']).",";
            $sql .= $PDO->quote($var['package']).",";
            $sql .= $langValues;
            $sql .= ");";
            $sql .= PHP_EOL;
        }

        if (empty($sql)) {
            return true;
        }

        $result = $PDO->exec($sql);

        if ($result === false) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/translator', 'exception.batch.query.error', [
                    'file'  => $file,
                    'error' => $PDO->errorInfo()[2]
                ])
            );
        }

        self::setLocaleFileModifyTime($file);

        return true;
    }

    /**
     * @param Package\Package $Package
     * @param int $overwriteOriginal
     * @param bool $devModeIgnore
     * @param bool $force - The translation should really be executed, the $filemtimes is ignored
     *
     * @throws QUI\Exception
     */
    public static function importFromPackage(
        QUI\Package\Package $Package,
        $overwriteOriginal = 0,
        $devModeIgnore = false,
        $force = false
    ) {
        $file = $Package->getXMLFile('locale.xml');

        if (!file_exists($file)) {
            return;
        }

        self::import(
            $file,
            $overwriteOriginal,
            $devModeIgnore,
            $Package->getName(),
            $force
        );

        try {
            $Dom      = XML::getDomFromXml($file);
            $fileList = $Dom->getElementsByTagName('file');

            /** @var \DOMElement $File */
            foreach ($fileList as $File) {
                $filePath    = $Package->getDir().ltrim($File->getAttribute('file'), '/');
                $packageName = $Package->getName();

                if ($File->hasAttribute('package')) {
                    $packageName = $File->getAttribute('package');
                }

                if (!file_exists($filePath)) {
                    continue;
                }

                self::import(
                    $filePath,
                    $overwriteOriginal,
                    $devModeIgnore,
                    $packageName,
                    $force
                );
            }
        } catch (QUI\Exception $Exception) {
        }
    }

    /**
     * Add the file to the modify time list
     *
     * @param string $file - path to the locale file
     */
    protected static function setLocaleFileModifyTime($file)
    {
        if (!file_exists($file)) {
            return;
        }

        self::$localeModifyTimes[$file] = filemtime($file);

        file_put_contents(
            VAR_DIR.'locale/localefiles',
            json_encode(self::$localeModifyTimes)
        );
    }

    /**
     * Return the modify times of all imported locale xml files
     *
     * @return array
     */
    protected static function getLocaleModifyTimes()
    {
        if (!is_null(self::$localeModifyTimes)) {
            return self::$localeModifyTimes;
        }

        $cacheFile = VAR_DIR.'locale/localefiles';

        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, '');
        }

        $list = json_decode(file_get_contents($cacheFile), true);

        self::$localeModifyTimes = $list;

        return $list;
    }

    /**
     * Ordner in dem die Übersetzungen liegen
     *
     * @return String
     */
    public static function dir()
    {
        return QUI::getLocale()->dir();
    }

    /**
     * Übersetzungs Datei
     *
     * @param String $lang
     * @param String $group
     *
     * @return String
     */
    public static function getTranslationFile($lang, $group)
    {
        return QUI::getLocale()->getTranslationFile($lang, $group);
    }

    /**
     * Return the list of the translation files for a language
     * it combines the language files in none development mode
     *
     * @param String $lang - Language -> eq: "de" or "en" ... and so on
     *
     * @return array
     */
    public static function getJSTranslationFiles($lang)
    {
        if (strlen($lang) !== 2) {
            return [];
        }

        $require = [];
        $result  = [];

        $jsdir       = self::dir().'bin/';
        $cacheFile   = $jsdir.'_cache/'.$lang.'.js';
        $development = QUI::conf('globals', 'development');

        QUIFile::mkdir($jsdir.'_cache/');

        if (file_exists($cacheFile) && !$development) {
            return ['locale/_cache' => $cacheFile];
        }

        $dirs      = QUIFile::readDir($jsdir);
        $cacheData = '';

        foreach ($dirs as $dir) {
            $package_dir  = $jsdir.$dir;
            $package_list = QUIFile::readDir($package_dir);

            foreach ($package_list as $package) {
                if ($package == '_cache') {
                    continue;
                }

                if (!file_exists($package_dir.'/'.$package)) {
                    continue;
                }

                if (!is_dir($package_dir.'/'.$package)) {
                    continue;
                }

                $lang_file = $package_dir.'/'.$package.'/'.$lang.'.js';

                if (file_exists($lang_file)) {
                    $result['locale/'.$dir.'/'.$package] = $lang_file;

                    $cacheData .= PHP_EOL.file_get_contents($lang_file);
                    $require[] = 'locale/'.$dir.'/'.$package.'/'.$lang;
                }
            }
        }

        if ($development && false) {
            return $result;
        }

        $requireEncode = json_encode($require);

        $cacheData .= "\n\n
            define('locale/_cache/{$lang}', {$requireEncode}, function(Locale) {return Locale})
        ";

        file_put_contents($cacheFile, $cacheData);

        return ['locale/_cache' => $cacheFile];
    }

    /**
     * Return all available languages
     *
     * @return array
     *
     * @throws QUi\Exception
     */
    public static function getAvailableLanguages()
    {
        $cacheName = 'quiqqer/translator/availableLanguages';

        try {
            return CacheManager::get($cacheName);
        } catch (\Exception $Exception) {
            // nothing, retrieve languages normally
        }

        $langs    = [];
        $projects = QUI::getProjectManager()->getProjects(true);

        /* @var $Project QUI\Projects\Project */
        foreach ($projects as $Project) {
            $langs = array_merge($langs, $Project->getAttribute('langs'));
        }

        $langs = array_unique($langs);
        $langs = array_unique(array_merge($langs, self::langs()));
        $langs = array_values($langs);

        CacheManager::set($cacheName, $langs);

        return $langs;
    }

    /**
     * remove all dublicate entres from the translation table
     *
     * because:
     * #1071 - Specified key was too long; max key length is 1000 bytes
     *
     * we cannot use unique keys :/
     */
    public static function cleanup()
    {
        $PDO       = QUI::getDataBase()->getPDO();
        $bad_table = self::table();

        // check if dublicate entries exist
        $Statement = $PDO->prepare(
            'SELECT `groups`, `var`
            FROM '.$bad_table.'
            GROUP BY `groups`, `var`
            HAVING count( * ) > 1'
        );

        $Statement->execute();

        if (!$Statement->fetch()) {
            return;
        }

        $PDO->prepare('DROP TABLE IF EXISTS bad_temp_translation')->execute();
        $PDO->prepare('CREATE TEMPORARY TABLE bad_temp_translation AS SELECT DISTINCT * FROM '.$bad_table)->execute();
        $PDO->prepare('DELETE FROM '.$bad_table)->execute();
        $PDO->prepare('INSERT INTO '.$bad_table.' SELECT * FROM bad_temp_translation')->execute();
    }

    /**
     * Create the locale files
     *
     * @throws QUI\Exception
     */
    public static function create()
    {
        // first step, a cleanup
        // so we get no errors in gettext
        self::cleanup();

        $langs = self::langs();
        $dir   = self::dir();

        // Sprach Ordner erstellen
        $folders = [];

        foreach ($langs as $lang) {
            $lcMessagePath = $dir.'/'.StringHelper::toLower($lang);
            $lcMessagePath .= '_'.StringHelper::toUpper($lang);
            $lcMessagePath .= '/LC_MESSAGES/';

            $folders[$lang] = $lcMessagePath;

            QUIFile::unlink($folders[$lang]);
            QUIFile::mkdir($folders[$lang]);
        }

        $js_langs = [];
        $Output   = false;

        if (class_exists('QUI\Output')) {
            $Output = new Output();
        }

        // Sprachdateien erstellen
        foreach ($langs as $lang) {
            set_time_limit(ini_get('max_execution_time'));

            if (strlen($lang) !== 2) {
                continue;
            }

            $result = QUI::getDataBase()->fetch([
                'select' => [
                    $lang,
                    $lang.'_edit',
                    'groups',
                    'var',
                    'datatype',
                    'datadefine',
                    'html',
                    'priority'
                ],
                'from'   => self::table(),
                'order'  => 'priority ASC'
            ]);

            // priority ASC Erklärung:
            // Wir müssen den kleinsten zuerst nehmen,
            // damit die höchste Priorität zu letzt kommt und die davor überschreibt
            // Ist verwirrend, aber somit sparen wir ein Query

            foreach ($result as $entry) {
                if ($entry['datatype'] == 'js') {
                    $js_langs[$entry['groups']][$lang][] = $entry;
                    continue;
                }

                // if php,js
                if (strpos($entry['datatype'], 'js') !== false || empty($entry['datatype'])) {
                    $js_langs[$entry['groups']][$lang][] = $entry;
                }

                $value = $entry[$lang];

                if (isset($entry[$lang.'_edit']) && !empty($entry[$lang.'_edit'])) {
                    $value = $entry[$lang.'_edit']; // benutzer übersetzung
                }

                if ($Output) {
                    $value = $Output->parse($value);
                }

                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\"', $value);
                $value = str_replace("\n", '{\n}', $value);

                if ($value !== '' && $value !== ' ') {
                    $value = trim($value);
                }

                // ini Datei
                $iniVar = $entry['var'];

                // in php some keywords are not allowed, so we rewrite the key in `
                // its better than destroy the ini file
                switch ($iniVar) {
                    case 'null':
                    case 'yes':
                    case 'no':
                    case 'true':
                    case 'false':
                    case 'on':
                    case 'off':
                    case 'none':
                        $iniVar = '`'.$iniVar.'`';
                        break;
                }

                $ini     = $folders[$lang].str_replace('/', '_', $entry['groups']).'.ini.php';
                $ini_str = $iniVar.'= "'.$value.'"';

                QUIFile::mkfile($ini);
                QUIFile::putLineToFile($ini, $ini_str);

                // po (gettext) datei
                $po = $folders[$lang].str_replace('/', '_', $entry['groups']).'.po';

                QUIFile::mkfile($po);

                QUIFile::putLineToFile($po, 'msgid "'.$entry['var'].'"');
                QUIFile::putLineToFile($po, 'msgstr "'.$value.'"');
                QUIFile::putLineToFile($po, '');
            }

            // create javascript lang files
            $jsdir = $dir.'/bin/';

            QUIFile::mkdir($jsdir);

            foreach ($js_langs as $group => $groupentry) {
                foreach ($groupentry as $lang => $entries) {
                    $vars = [];

                    foreach ($entries as $entry) {
                        $value = $entry[$lang];

                        if (isset($entry[$lang.'_edit']) && !empty($entry[$lang.'_edit'])) {
                            $value = $entry[$lang.'_edit']; // benutzer übersetzung
                        }

                        $vars[$entry['var']] = $value;
                    }

                    $js = '';
                    $js .= "define('locale/".$group."/".$lang."', ['Locale'], function(Locale)";
                    $js .= '{';
                    $js .= 'Locale.set("'.$lang.'", "'.$group.'", ';
                    $js .= json_encode($vars);
                    $js .= ')';
                    $js .= '});';

                    // create package dir
                    QUIFile::mkdir($jsdir.$group);

                    if (file_exists($jsdir.$group.'/'.$lang.'.js')) {
                        unlink($jsdir.$group.'/'.$lang.'.js');
                    }

                    file_put_contents($jsdir.$group.'/'.$lang.'.js', $js);
                }
            }

            // alle .po dateien einlesen und in mo umwandeln
            $po_files = QUIFile::readDir($folders[$lang]);

            foreach ($po_files as $po_file) {
                if (substr($po_file, -3) == '.po') {
                    self::phpmoConvert($folders[$lang].$po_file);
                }
            }
        }

        // clean cache dir of js files
        QUI::getTemp()->moveToTemp($dir.'/bin/_cache/');

        if (method_exists(QUI::getLocale(), 'refresh')) {
            QUI::getLocale()->refresh();
        }

        Cache\Manager::clearAll();
    }

    /**
     * Publish a language group
     *
     * @param string $group
     *
     * @throws QUI\Exception
     */
    public static function publish($group)
    {
        $langs  = self::langs();
        $dir    = self::dir();
        $Output = false;

        if (class_exists('QUI\Output')) {
            $Output = new Output();
        }

        foreach ($langs as $lang) {
            if (strlen($lang) !== 2) {
                continue;
            }

            $folder = $dir.'/';
            $folder .= StringHelper::toLower($lang).'_'.StringHelper::toUpper($lang);
            $folder .= '/LC_MESSAGES/';

            QUIFile::mkdir($folder);

            $result = QUI::getDataBase()->fetch([
                'select' => [
                    $lang,
                    $lang.'_edit',
                    'groups',
                    'var',
                    'datatype',
                    'datadefine',
                    'html'
                ],
                'from'   => self::table(),
                'where'  => [
                    'groups' => $group
                ],
                'order'  => 'priority ASC'
            ]);

            // priority ASC Erklärung:
            // Wir müssen den kleinsten zuerst nehmen,
            // damit die höchste Priorität zu letzt kommt und die davor überschreibt
            // Ist verwirrend, aber somit sparen wir ein Query

            $javaScriptValues = [];

            $iniContent = '';
            $poContent  = '';

            foreach ($result as $data) {
                // value select
                $value = $data[$lang];

                if (isset($data[$lang.'_edit']) && !empty($data[$lang.'_edit'])) {
                    $value = $data[$lang.'_edit'];
                }

                if ($Output) {
                    $value = $Output->parse($value);
                }

                if ($data['datatype'] == 'js') {
                    $javaScriptValues[$data['var']] = $value;
                    continue;
                }

                // php und js beachten
                if (strpos($data['datatype'], 'js') !== false || empty($data['datatype'])) {
                    $javaScriptValues[$data['var']] = $value;
                }

                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\"', $value);
                $value = str_replace("\n", '{\n}', $value);

                if ($value !== '' && $value !== ' ') {
                    $value = trim($value);
                }

                // ini Content
                $iniVar = $data['var'];

                // in php some keywords are not allowed, so we rewrite the key in `
                // its better than destroy the ini file
                switch ($iniVar) {
                    case 'null':
                    case 'yes':
                    case 'no':
                    case 'true':
                    case 'false':
                    case 'on':
                    case 'off':
                    case 'none':
                        $iniVar = '`'.$iniVar.'`';
                        break;
                }

                // content
                $iniContent .= $iniVar.'= "'.$value.'"'.PHP_EOL;

                $poContent .= 'msgid "'.$data['var'].'"'.PHP_EOL;
                $poContent .= 'msgstr "'.$value.'"'.PHP_EOL.PHP_EOL;
            }

            // set data
            $iniFile = $folder.str_replace('/', '_', $group).'.ini.php';
            $poFile  = $folder.str_replace('/', '_', $group).'.po';

            QUIFile::unlink($iniFile);
            QUIFile::mkfile($iniFile);

            QUIFile::unlink($poFile);
            QUIFile::mkfile($poFile);

            file_put_contents($iniFile, $iniContent);
            file_put_contents($poFile, $poContent);

            self::phpmoConvert($poFile);

            // javascript
            $jsFile = $dir.'/bin/'.$group.'/'.$lang.'.js';

            QUIFile::unlink($jsFile);
            QUIFile::mkfile($jsFile);

            $jsContent = '';
            $jsContent .= "define('locale/".$group."/".$lang."', ['Locale'], function(Locale)";
            $jsContent .= '{';
            $jsContent .= 'Locale.set("'.$lang.'", "'.$group.'", ';
            $jsContent .= json_encode($javaScriptValues);
            $jsContent .= ')';
            $jsContent .= '});';

            file_put_contents($jsFile, $jsContent);
        }

        // clean cache dir of js files
        QUI::getTemp()->moveToTemp($dir.'/bin/_cache/');
        Cache\Manager::clearAll();

        if (method_exists(QUI::getLocale(), 'refresh')) {
            QUI::getLocale()->refresh();
        }
    }

    /**
     * Übersetzung bekommen
     *
     * @param string|boolean $group - Gruppe
     * @param string|boolean $var - Übersetzungsvariable, optional
     * @param string|boolean $package - optional, package name
     *
     * @return array
     */
    public static function get($group = false, $var = false, $package = false)
    {
        $where = [];

        if ($group) {
            $where['groups'] = $group;
        }

        if ($var) {
            $where['var'] = $var;
        }

        if ($package) {
            $where['package'] = $package;
        }

        return QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => $where
        ]);
    }

    /**
     * Daten für die Tabelle bekommen
     *
     * @param String $groups - Gruppe
     * @param array $params - optional array(limit => 10, page => 1)
     * @param array|Bool $search - optional array(search => '%str%', fields => '')
     *
     * @return array
     */
    public static function getData($groups, $params = [], $search = false)
    {
        $table     = self::table();
        $db_fields = self::langs();

        $max  = 10;
        $page = 1;

        if (isset($params['limit'])) {
            $max = (int)$params['limit'];
        }

        if (isset($params['page'])) {
            $page = (int)$params['page'];
        }

        $page  = ($page - 1) ? $page - 1 : 0;
        $limit = ($page * $max).','.$max;

        // PDO search emptyTranslations
        if ($search && isset($search['emptyTranslations']) && $search['emptyTranslations']) {
            $PDO    = QUI::getPDO();
            $fields = [];

            // search empty translations
            if (isset($search['fields']) && !empty($search['fields'])) {
                $fields = array_flip($search['fields']);
            }

            $whereParts = [];

            foreach ($db_fields as $field) {
                if (!empty($fields) && !isset($fields[$field])) {
                    continue;
                }

                $whereParts[] = "(
                    ({$field} = '' OR {$field} IS NULL) AND
                    ({$field}_edit = '' OR {$field}_edit IS NULL)
                )";
            }

            $where = implode(' OR ', $whereParts);

            $querySelect = "
                SELECT *
                FROM {$table}
                WHERE {$where}
                LIMIT {$limit}
            ";

            $queryCount = "
                SELECT COUNT(*) as count
                FROM {$table}
                WHERE {$where}
            ";

            $Statement = $PDO->prepare($querySelect);
            $Statement->execute();
            $result = $Statement->fetchAll(\PDO::FETCH_ASSOC);

            $Statement = $PDO->prepare($queryCount);
            $Statement->execute();
            $count = $Statement->fetchAll(\PDO::FETCH_ASSOC);

            return [
                'data'  => $result,
                'page'  => $page + 1,
                'count' => $count[0]['count'],
                'total' => $count[0]['count']
            ];
        }

        if ($search && isset($search['search'])) {
            // search translations
            $where  = [];
            $search = [
                'type'  => '%LIKE%',
                'value' => trim($search['search'])
            ];

            // default fields
            $default = [
                'groups'     => $search,
                'var'        => $search,
                'datatype'   => $search,
                'datadefine' => $search
            ];

            foreach ($db_fields as $lang) {
                if (strlen($lang) == 2) {
                    $default[$lang]         = $search;
                    $default[$lang.'_edit'] = $search;
                }
            }

            // search
            $fields = [];

            if (isset($search['fields']) && !empty($search['fields'])) {
                $fields = $search['fields'];
            }

            foreach ($fields as $field) {
                if (isset($default[$field])) {
                    $where[$field] = $search;
                }
            }

            if (empty($where)) {
                $where = $default;
            }

            $data = [
                'from'     => $table,
                'where_or' => $where,
                'limit'    => $limit
            ];
        } else {
            // search complete group
            $data = [
                'from'  => $table,
                'where' => [
                    'groups' => $groups
                ],
                'limit' => $limit
            ];
        }

        // result mit limit
        $result = QUI::getDataBase()->fetch($data);

        // count
        $data['count'] = 'groups';

        if (isset($data['limit'])) {
            unset($data['limit']);
        }

        $count = QUI::getDataBase()->fetch($data);

        return [
            'data'  => $result,
            'page'  => $page + 1,
            'count' => $count[0]['groups'],
            'total' => $count[0]['groups']
        ];
    }

    /**
     * Return the data from a translation variable
     *
     * @param string $group
     * @param string $var
     * @param string|bool $package
     *
     * @return array
     */
    public static function getVarData($group, $var, $package = false)
    {
        $where = [
            'groups' => $group,
            'var'    => $var
        ];

        if (!empty($package)) {
            $where['package'] = $package;
        }

        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => $where,
            'limit' => 1
        ]);

        if (!isset($result[0])) {
            return [];
        }

        return $result[0];
    }

    /**
     * Liste aller vorhandenen Gruppen
     *
     * @return array
     */
    public static function getGroupList()
    {
        $result = QUI::getDataBase()->fetch([
            'select' => 'groups',
            'from'   => self::table(),
            'group'  => 'groups'
        ]);

        $list = [];

        foreach ($result as $entry) {
            $list[] = $entry['groups'];
        }

        return $list;
    }

    /**
     * Add a translation variable
     *
     * @param string $group
     * @param string $var
     * @param string|bool $package = default = false
     * @param string $dataType - default = php,js
     * @param integer|bool $html - default = false
     *
     * @throws QUI\Exception
     */
    public static function add($group, $var, $package = false, $dataType = 'php,js', $html = false)
    {
        if (empty($var) || empty($group)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/translator',
                    'exception.empty.var.group'
                )
            );
        }

        $result = self::get($group, $var, $package);

        if (isset($result[0])) {
            throw new QUI\Exception(
                [
                    'quiqqer/translator',
                    'exception.var.exists',
                    [
                        'group'   => $group,
                        'var'     => $var,
                        'package' => $package
                    ]
                ],
                self::ERROR_CODE_VAR_EXISTS
            );
        }

        // cleanup datatype
        $types    = [];
        $dataType = explode(',', $dataType);

        foreach ($dataType as $type) {
            switch ($type) {
                case 'php':
                case 'js':
                    $types[] = $type;
                    break;
            }
        }

        if (empty($types)) {
            $types = ['php', 'js'];
        }

        // insert data
        QUI::getDataBase()->insert(
            self::table(),
            [
                'groups'   => $group,
                'var'      => $var,
                'package'  => !empty($package) ? $package : '',
                'datatype' => implode(',', $types),
                'html'     => $html ? 1 : 0
            ]
        );
    }

    /**
     * Add a translation like from an user
     *
     * @param string $group
     * @param string $var
     * @param array $data - [de='', en=>'', datatype=>'', html=>1]
     *
     * @throws QUI\Exception
     */
    public static function addUserVar($group, $var, $data)
    {
        $package     = false;
        $development = QUI::conf('globals', 'development');

        if (isset($data['package'])) {
            $package = $data['package'];
        }

        if ($development) {
            $langs = self::langs();

            foreach ($langs as $lang) {
                if (!isset($data[$lang.'_edit']) && isset($data[$lang])) {
                    $data[$lang.'_edit'] = $data[$lang];
                }
            }
        }

        QUI\Translator::add($group, $var, $package);
        QUI\Translator::edit($group, $var, $package, $data);
    }

    /**
     * Eintrag aktualisieren
     *
     * @param string $group
     * @param string $var
     * @param string $packageName
     * @param array $data
     */
    public static function update($group, $var, $packageName, $data)
    {
        $langs = self::langs();
        $_data = [];

        foreach ($langs as $lang) {
            if (!isset($data[$lang])) {
                continue;
            }

            $content = trim($data[$lang]);

            // Leere Werte ignorieren
            if (empty($content)) {
                continue;
            }

            $_data[$lang] = $content;
        }

        $_data['html']     = 0;
        $_data['priority'] = 0;
        $_data['datatype'] = 'php,js';

        if (isset($data['datatype'])) {
            $_data['datatype'] = $data['datatype'];
        }

        if (!empty($data['html'])) {
            $_data['html'] = 1;
        }

        if (!empty($data['priority'])) {
            $_data['priority'] = (int)$data['priority'];
        }

        QUI::getDataBase()->update(self::table(), $_data, [
            'groups'  => $group,
            'var'     => $var,
            'package' => $packageName ?: $group
        ]);
    }

    /**
     * User Edit
     *
     * @param string $group
     * @param string $var
     * @param string $packageName
     * @param array $data
     */
    public static function edit($group, $var, $packageName, $data)
    {
        QUI::getDataBase()->update(self::table(), self::getEditData($data), [
            'groups'  => $group,
            'var'     => $var,
            'package' => $packageName ?: $group
        ]);
    }

    /**
     * User Edit with an entry id
     *
     * @param integer $id
     * @param array $data
     */
    public static function editById($id, $data)
    {
        QUI::getDataBase()->update(self::table(), self::getEditData($data), [
            'id' => $id
        ]);
    }

    /**
     * Prepares the data for a translation entry
     *
     * @param array $data
     *
     * @return array
     */
    protected static function getEditData($data)
    {
        $langs = self::langs();
        $_data = [];

        $development = QUI::conf('globals', 'development');

        foreach ($langs as $lang) {
            if ($development) {
                if (isset($data[$lang])) {
                    $_data[$lang] = trim($data[$lang]);
                }

                if (isset($data[$lang.'_edit'])) {
                    $_data[$lang.'_edit'] = trim($data[$lang.'_edit']);
                }

                continue;
            }

            if (!isset($data[$lang]) && !isset($data[$lang.'_edit'])) {
                continue;
            }

            if (isset($data[$lang])) {
                $_data[$lang.'_edit'] = trim($data[$lang]);
                continue;
            }

            $_data[$lang.'_edit'] = trim($data[$lang.'_edit']);
        }

        $_data['html']     = 0;
        $_data['priority'] = 0;
        $_data['datatype'] = 'php,js';

        if (isset($data['datatype'])) {
            $_data['datatype'] = $data['datatype'];
        }

        if (!empty($data['html'])) {
            $_data['html'] = 1;
        }

        if (!empty($data['priority'])) {
            $_data['priority'] = (int)$data['priority'];
        }

        return $_data;
    }

    /**
     * Einen Übersetzungseintrag löschen
     *
     * @param String $group
     * @param String $var
     */
    public static function delete($group, $var)
    {
        if (file_exists(VAR_DIR.'locale/localefiles')) {
            unlink(VAR_DIR.'locale/localefiles');
        }

        QUI::getDataBase()->delete(
            self::table(),
            [
                'groups' => $group,
                'var'    => $var
            ]
        );
    }

    /**
     * Einen Übersetzungseintrag löschen
     *
     * @param integer $id
     */
    public static function deleteById($id)
    {
        if (file_exists(VAR_DIR.'locale/localefiles')) {
            unlink(VAR_DIR.'locale/localefiles');
        }

        QUI::getDataBase()->delete(
            self::table(),
            ['id' => $id]
        );
    }

    /**
     * Welche Sprachen existieren
     *
     * @return array
     */
    public static function langs()
    {
        $fields = QUI::getDataBase()->table()->getColumns(
            self::table()
        );

        $langs = [];

        foreach ($fields as $entry) {
            if ($entry == 'groups'
                || $entry == 'id'
                || $entry == 'var'
                || $entry == 'html'
                || $entry == 'datatype'
                || $entry == 'datadefine'
                || $entry == 'package'
                || $entry == 'priority'
            ) {
                continue;
            }

            if (strpos($entry, '_edit') !== false) {
                continue;
            }

            $langs[] = $entry;
        }

        return $langs;
    }

    /**
     * Gibt die zu übersetzenden Variablen zurück
     *
     * @return array
     */
    public static function getNeedles()
    {
        $result = QUI::getDataBase()->fetch([
            'from'  => self::table(),
            'where' => implode(' = "" OR ', self::langs()).' = ""'
        ]);

        return $result;
    }

    /**
     * Parser Methoden
     */

    protected static $tmp = [];

    /**
     * T Blöcke in einem String finden
     *
     * @param String $string
     *
     * @return array
     */
    public static function getTBlocksFromString($string)
    {
        if (strpos($string, '{/t}') === false) {
            return [];
        }

        self::$tmp = [];

        preg_replace_callback(
            '/{t([^}]*)}([^[{]*){\/t}/im',
            function ($params) {
                if (isset($params[1]) && !empty($params[1])) {
                    $_params = explode(' ', trim($params[1]));
                    $_params = str_replace(['"', "'"], '', $_params);

                    $group = '';
                    $var   = '';

                    foreach ($_params as $param) {
                        $_param = explode('=', $param);

                        if ($_param[0] == 'groups') {
                            $group = $_param[1];
                        }

                        if ($_param[0] == 'var') {
                            $var = $_param[1];
                        }
                    }

                    self::$tmp[] = [
                        'groups' => $group,
                        'var'    => $var
                    ];

                    return;
                }

                $_param = explode(' ', $params[2]);

                if (strpos($_param[0], '/') === false
                    || strpos($_param[1], ' ') !== false
                ) {
                    self::$tmp[] = [
                        'var' => $params[2]
                    ];
                }

                self::$tmp[] = [
                    'groups' => $_param[0],
                    'var'    => $_param[1],
                ];
            },
            $string
        );

        return self::$tmp;
    }

    /**
     * PHP Blöcke in einem String finden
     *
     * @param String $string
     *
     * @return array
     */
    public static function getLBlocksFromString($string)
    {
        if (strpos($string, '$L->get(') === false
            && strpos($string, '$Locale->get(') === false
        ) {
            return [];
        }

        self::$tmp = [];

        preg_replace_callback(
            '/\$L(ocale)?->get\s*\(\s*\'([^)]*)\'\s*,\s*\'([^[)]*)\'\s*\)/im',
            function ($params) {
                if (isset($params[2]) && isset($params[3]) && !empty($params[2])
                    && !empty($params[3])
                    && strpos($params[2], '/') === false
                ) {
                    self::$tmp[] = [
                        'groups' => $params[2],
                        'var'    => $params[3],
                    ];
                }
            },
            $string
        );

        return self::$tmp;
    }

    /**
     * Deletes double group-var entries
     *
     * @param array $array
     *
     * @return array
     */
    public static function deleteDoubleEntries($array)
    {
        // Doppelte Einträge löschen
        $new_tmp = [];

        foreach ($array as $tmp) {
            if (!isset($new_tmp[$tmp['groups'].$tmp['var']])) {
                $new_tmp[$tmp['groups'].$tmp['var']] = $tmp;
            }
        }

        $array = [];

        foreach ($new_tmp as $tmp) {
            $array[] = $tmp;
        }

        return $array;
    }


    /**
     * First fallback for gettext
     * based on php.mo 0.1 by Joss Crowcroft (http://www.josscrowcroft.com)
     */

    /**
     * The main .po to .mo function
     *
     * @param String $input
     * @param String|Bool $output
     *
     * @return boolean
     */
    public static function phpmoConvert($input, $output = false)
    {
        if (!$output) {
            $output = str_replace('.po', '.mo', $input);
        }

        $hash = self::phpmoParsePoFile($input);

        if ($hash === false) {
            return false;
        }

        self::phpmoWriteMoFile($hash, $output);

        return true;
    }

    /**
     * Clean helper
     *
     * @param array|String $x
     *
     * @return mixed
     */
    public static function phpmoCleanHelper($x)
    {
        if (is_array($x)) {
            foreach ($x as $k => $v) {
                $x[$k] = self::phpmoCleanHelper($v);
            }
        } else {
            if ($x[0] == '"') {
                $x = substr($x, 1, -1);
            }

            $x = str_replace("\"\n\"", '', $x);
            $x = str_replace('$', '\\$', $x);
        }

        return $x;
    }

    /**
     * Parse gettext .po files.
     *
     * @link  http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files
     *
     * @param string $in
     *
     * @return bool|array
     */
    public static function phpmoParsePoFile($in)
    {
        // read .po file
        $fh = fopen($in, 'r');

        if ($fh === false) {
            // Could not open file resource
            return false;
        }

        // results array
        $hash = [];

        // temporary array
        $temp = [];

        // state
        $state = null;
        $fuzzy = false;

        set_time_limit(0);

        // iterate over lines
        while (($line = fgets($fh, 65536)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            list ($key, $data) = preg_split('/\s/', $line, 2);

            switch ($key) {
                case '#,':
                    // flag...
                    $fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));
                // flag...
                case '#': // translator-comments
                case '#.': // extracted-comments
                case '#:': // reference...
                case '#|': // msgid previous-untranslated-string
                    // start a new entry
                    if (sizeof($temp) && array_key_exists('msgid', $temp)
                        && array_key_exists('msgstr', $temp)
                    ) {
                        if (!$fuzzy) {
                            $hash[] = $temp;
                        }

                        $temp  = [];
                        $state = null;
                        $fuzzy = false;
                    }
                    break;

                case 'msgctxt':
                    // context
                case 'msgid':
                    // untranslated-string
                case 'msgid_plural':
                    // untranslated-string-plural
                    $state        = $key;
                    $temp[$state] = $data;
                    break;
                case 'msgstr':
                    // translated-string
                    $state          = 'msgstr';
                    $temp[$state][] = $data;
                    break;

                default:
                    if (strpos($key, 'msgstr[') !== false) {
                        // translated-string-case-n
                        $state          = 'msgstr';
                        $temp[$state][] = $data;
                    } else {
                        // continued lines
                        switch ($state) {
                            case 'msgctxt':
                            case 'msgid':
                            case 'msgid_plural':
                                $temp[$state] .= PHP_EOL.$line;
                                break;
                            case 'msgstr':
                                $temp[$state][sizeof($temp[$state]) - 1] .= PHP_EOL.$line;
                                break;
                            default:
                                // parse error
                                fclose($fh);

                                return false;
                        }
                    }
                    break;
            }
        }

        fclose($fh);

        // add final entry
        if ($state == 'msgstr') {
            $hash[] = $temp;
        }

        // Cleanup data, merge multiline entries, reindex hash for ksort
        $temp = $hash;
        $hash = [];

        foreach ($temp as $entry) {
            foreach ($entry as $v) {
                $v = self::phpmoCleanHelper($v);

                // parse error
                if ($v === false) {
                    return false;
                }
            }

            $hash[$entry['msgid']] = $entry;
        }

        return $hash;
    }

    /**
     * Write a GNU gettext style machine object.
     *
     * @link http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files
     *
     * @param array $hash
     * @param string $out - file path
     */
    public static function phpmoWriteMoFile($hash, $out)
    {
        // sort by msgid
        ksort($hash, SORT_STRING);

        // our mo file data
        $mo = '';

        // header data
        $offsets = [];
        $ids     = '';
        $strings = '';

        foreach ($hash as $entry) {
            $id = $entry['msgid'];

            if (isset($entry['msgid_plural'])) {
                $id .= "\x00".$entry['msgid_plural'];
            }

            // context is merged into id, separated by EOT (\x04)
            if (array_key_exists('msgctxt', $entry)) {
                $id = $entry['msgctxt']."\x04".$id;
            }

            // plural msgstrs are NUL-separated
            $str = implode("\x00", $entry['msgstr']);

            // keep track of offsets
            $offsets[] = [
                strlen($ids),
                strlen($id),
                strlen($strings),
                strlen($str)
            ];
            // plural msgids are not stored (?)
            $ids     .= $id."\x00";
            $strings .= $str."\x00";
        }

        // keys start after the header (7 words) + index tables ($#hash * 4 words)
        $key_start = 7 * 4 + sizeof($hash) * 4 * 4;

        // values start right after the keys
        $value_start = $key_start + strlen($ids);

        // first all key offsets, then all value offsets
        $key_offsets   = [];
        $value_offsets = [];

        // calculate
        foreach ($offsets as $v) {
            list ($o1, $l1, $o2, $l2) = $v;

            $key_offsets[]   = $l1;
            $key_offsets[]   = $o1 + $key_start;
            $value_offsets[] = $l2;
            $value_offsets[] = $o2 + $value_start;
        }

        $offsets = array_merge($key_offsets, $value_offsets);

        // write header
        $mo .= pack(
            'Iiiiiii',
            0x950412de, // magic number
            0, // version
            sizeof($hash), // number of entries in the catalog
            7 * 4, // key index offset
            7 * 4 + sizeof($hash) * 8, // value index offset,
            0, // hashtable size (unused, thus 0)
            $key_start // hashtable offset
        );

        // offsets
        foreach ($offsets as $offset) {
            $mo .= pack('i', $offset);
        }

        // ids
        $mo .= $ids;

        // strings
        $mo .= $strings;

        file_put_contents($out, $mo);
    }
}
