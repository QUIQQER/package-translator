<?php

/**
 * This file contains QUI\Translater
 */

namespace QUI;

/**
 * QUIQQER Translater
 *
 * Manage all translations, for the system and the plugins
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.locale
 */

class Translator
{
    const TABLE = 'translate';

    /**
     * Setup - Übersetzungstabellen erstellen
     */
    static function setup()
    {
        \QUI::getDB()->createTableFields(self::TABLE, array(
             'groups'  => 'varchar(128) NOT NULL',
             'var'     => 'varchar(255) NOT NULL',
             'de'      => 'text NOT NULL',
             'de_edit' => 'text NULL'
        ));

        \QUI::getDB()->setIndex( self::TABLE, 'groups' );
    }

    /**
     * Add / create a new language
     *
     * @param String $lang
     */
    static function addLang($lang)
    {
        if (strlen($lang) !== 2) {
            throw new \QException('Sprachkürzel nicht erlaubt');
        }

        \QUI::getDB()->createTableFields(self::TABLE, array(
             $lang          => 'text NOT NULL',
             $lang .'_edit' => 'text NULL'
        ));
    }

    /**
     * Export a locale group as xml
     *
     * @param String $groups - which group should be exported?
     * @return String
     */
    static function export($groups)
    {
        $group   = \Utils_Security_Orthos::clearMySQL($groups);
        $entries = self::get($groups);

        $result = '<groups name="'. $group .'">'."\n";

        foreach ($entries as $entry)
        {
            $result .= "\t".'<locale name="'. $entry['var'] .'">'."\n";

            unset($entry['groups']);
            unset($entry['var']);

            foreach ($entry as $lang => $translation) {
                $result .= "\t\t".'<'. $lang .'><![CDATA['. $translation .']]></'. $lang .'>'."\n";
            }

            $result .= "\t".'</locale>'."\n";
        }

        $result .= '</groups>'."\n";

        return $result;
    }

    /**
     * Import a locale xml file
     *
     * @param String $file - path to the file
     * @throws QException
     */
    static function import($file)
    {
        if (!file_exists($file)) {
            throw new \QException('Übersetzungsdatei existiert nicht.');
        }

        $locales = \Utils_Xml::getLocaleGroupsFromDom(
            \Utils_Xml::getDomFromXml( $file )
        );

        $group = $locales['group'];

        foreach ($locales['locales'] as $locale)
        {
            $var = $locale['name'];
            unset($locale['name']);

            try
            {
                self::add($group, $var);
            } catch ( \QException $e )
            {

            }

            self::update($group, $var, $locale);
        }
    }

    /**
     * Ordner in dem die Übersetzungen liegen
     * @return String
     */
    static function dir()
    {
        return \QUI::getLocale()->dir();
    }

    /**
     * Übersetzungs Datei
     *
     * @param String $lang
     * @param String $group
     *
     * @return String
     */
    static function getTranslationFile($lang, $group)
    {
        return \QUI::getLocale()->getTranslationFile($lang, $group);
    }

    /**
     * Übersetzungsdateien für das Locale Objekt erstellen
     */
    static function create()
    {
        $langs = self::langs();
        $dir   = self::dir();

        // Sprach Ordner erstellen
        $folders    = array();
        $exec_error = array();

        foreach ($langs as $lang)
        {
            $folders[ $lang ] = $dir .'/'. \Utils_String::toLower($lang) .'_'. \Utils_String::toUpper($lang) .'/LC_MESSAGES/';

            \Utils_System_File::unlink($folders[ $lang ]);
            \Utils_System_File::mkdir( $folders[ $lang ] );
        }

        // Sprachdateien erstellen
        foreach ($langs as $lang)
        {
            if (strlen($lang) !== 2) {
                continue;
            }

            $result = \QUI::getDB()->select(array(
                'select' => array(
                    $lang, $lang .'_edit', 'groups', 'var'
                ),
                'from' => self::TABLE
            ));

            foreach ($result as $entry)
            {
                $value = $entry[ $lang ];

                if (isset($entry[ $lang.'_edit' ]) && !empty($entry[ $lang.'_edit' ])) {
                    $value = $entry[ $lang.'_edit' ]; // benutzer übersetzung
                }

                $value = str_replace('\\', '\\\\', $value);
                $value = str_replace('"', '\"', $value);
                $value = nl2br($value);
                $value = str_replace("\n", '', $value);

                if ($value !== '' && $value !== ' ') {
                    $value = trim($value);
                }

                // ini Datei
                $ini     = $folders[ $lang ] . str_replace('/', '_', $entry['groups']) .'.ini';
                $ini_str = $entry['var'] .'= "'. $value .'"';

                \Utils_System_File::mkfile($ini);
                \Utils_System_File::putLineToFile($ini, $ini_str);

                // po (gettext) datei
                $po = $folders[ $lang ] . str_replace('/', '_', $entry['groups']) .'.po';
                $mo = $folders[ $lang ] . str_replace('/', '_', $entry['groups']) .'.mo';

                \Utils_System_File::mkfile($po);

                \Utils_System_File::putLineToFile($po, 'msgid "'. $entry['var'] .'"');
                \Utils_System_File::putLineToFile($po, 'msgstr "'. $value .'"');
                \Utils_System_File::putLineToFile($po, '');
            }

            // alle .po dateien einlesen und in mo umwandeln
            if (function_exists('gettext')) //@todo getText über Config ein ud ausschaltbar machen
            {
                $po_files = \Utils_System_File::readDir($folders[ $lang ]);

                foreach ($po_files as $po_file)
                {
                    if (substr($po_file, -3) == '.po'){
                       exec( \Utils_Security_Orthos::clearShell('msgfmt '. $folders[ $lang ]. $po_file .' -o '. $folders[ $lang ]. substr($po_file, 0,-3).'.mo' ). ' 2>&1',$exec_error);

                    }
                }
            }
        }

        if (!empty($exec_error)) { // @todo nicht in error log schreiben sondern an den Message Handler ins Admin übergeben
            \System_Log::writeRecursive($exec_error);
        }
    }

    /**
     * Übersetzung bekommen
     *
     * @param String $group - Gruppe
     * @param String $var   - Übersetzungsvariable, optional
     *
     * @return Array
     */
    static function get($group, $var=false)
    {
        if (!$var)
        {
            return \QUI::getDB()->select(array(
                'from' => self::TABLE,
                'where' => array(
                    'groups' => \Utils_Security_Orthos::clearMySQL( $group )
                )
            ));
        }

        return \QUI::getDB()->select(array(
            'from' => self::TABLE,
            'where' => array(
                'groups' => \Utils_Security_Orthos::clearMySQL( $group ),
                'var'    => \Utils_Security_Orthos::clearMySQL( $var )
            )
        ));
    }

    /**
     * Daten für die Tabelle bekommen
     *
     * @param String $groups - Gruppe
     * @param Array $params  - optional (limit,
     *
     * @return Array
     */
    static function getData($groups, $params=array())
    {
        $max  = 10;
        $page = 1;

        if (isset($params['limit'])) {
            $max = (int)$params['limit'];
        }

        if (isset($params['page'])) {
            $page = (int)$params['page'];
        }

        $page  = ($page - 1) ? $page - 1 : 0;
        $limit = ($page * $max) .','. $max;

        $data = array(
            'from' => self::TABLE,
            'where' => array(
                'groups' => \Utils_Security_Orthos::clearMySQL( $groups )
            ),
            'limit' => $limit
        );

        // result mit limit
        $result = \QUI::getDB()->select($data);

        // count
        $data['count'] = 'groups';

        if (isset($data['limit'])) {
            unset($data['limit']);
        }

        $count = \QUI::getDB()->select($data);

        return array(
            'data'  => $result,
            'page'  => $page + 1,
            'count' => $count[0]['groups'],
            'total' => $count[0]['groups']
        );
    }

    /**
     * Liste aller vorhandenen Gruppen
     *
     * @return Array
     */
    static function getGroupList()
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'groups',
            'from'   => self::TABLE,
            'group'  => 'groups'
        ));

        $list = array();

        foreach ( $result as $entry ) {
            $list[] = $entry['groups'];
        }

        return $list;
    }

    /**
     * Fügt eine Übersetzungsvariable hinzu
     *
     * @param String $group
     * @param String $var
     */
    static function add($group, $var)
    {
        if (empty($var) || empty($group)) {
            throw new \QException('Übersetzungsvariable konnte nicht angelegt werden.');
        }

        $result = self::get($group, $var);

        if (isset($result[0])) {
            throw new \QException('Übersetzungsvariable konnte nicht angelegt werden, diese Variable existiert bereits.');
        }

        \QUI::getDB()->addData(
            self::TABLE,
            array(
                'groups' => \Utils_Security_Orthos::clearMySQL( $group ),
                'var'    => \Utils_Security_Orthos::clearMySQL( $var )
            )
        );
    }

    /**
     * Eintrag aktualisieren
     *
     * @param unknown_type $group
     * @param unknown_type $var
     * @param unknown_type $data
     */
    static function update($group, $var, $data)
    {
        $langs = self::langs();
        $_data = array();

        foreach ($langs as $lang)
        {
            if (!isset($data[ $lang ])) {
                continue;
            }

            $_data[ $lang ] = \Utils_Security_Orthos::clearMySQL($data[ $lang ], false);
        }

        \QUI::getDB()->updateData(
            self::TABLE,
            $_data,
            array(
                'groups' => \Utils_Security_Orthos::clearMySQL( $group ),
                'var'    => \Utils_Security_Orthos::clearMySQL( $var )
            )
        );
    }

    /**
     * User Edit
     *
     * @param unknown_type $group
     * @param unknown_type $var
     * @param unknown_type $data
     */
    static function edit($group, $var, $data)
    {
        $langs = self::langs();
        $_data = array();

        foreach ($langs as $lang)
        {
            if (!isset($data[ $lang ])) {
                continue;
            }

            if (strlen($lang) === 2) {
                $_data[ $lang .'_edit' ] = \Utils_Security_Orthos::clearMySQL($data[ $lang ], false);
            }
        }

        \QUI::getDB()->updateData(
            self::TABLE,
            $_data,
            array(
                'groups' => \Utils_Security_Orthos::clearMySQL( $group ),
                'var'    => \Utils_Security_Orthos::clearMySQL( $var )
            )
        );
    }

    /**
     * Einen Übersetzungseintrag löschen
     *
     * @param String $group
     * @param String $var
     */
    static function delete($group, $var)
    {
        \QUI::getDB()->deleteData(self::TABLE, array(
            'groups' => \Utils_Security_Orthos::clearMySQL( $group ),
            'var'    => \Utils_Security_Orthos::clearMySQL( $var )
        ));
    }

    /**
     * Welche Sprachen existieren
     *
     * @return Array
	 */
    static function langs()
    {
        $fields = \QUI::getDB()->getFields(self::TABLE);
        $langs  = array();

        foreach ($fields as $entry)
        {
            if ($entry == 'groups') {
                continue;
            }

            if ($entry == 'var') {
                continue;
            }

            $langs[] = $entry;
        }

        return $langs;
    }

    /**
     * Gibt die zu übersetzenden Variablen zurück
     *
     * @return Array
     */
    static function getNeedles()
    {
        $fields = \QUI::getDB()->getFields(self::TABLE);
        $langs  = array();

        foreach ($fields as $entry)
        {
            if ($entry == 'var' || $entry == 'groups') {
                continue;
            }

            $langs[] = $entry;
        }

        $result = \QUI::getDB()->select(array(
            'from'  => self::TABLE,
            'where' => implode(' = "" OR ', $langs) .' = ""'
        ));

        return $result;
    }

    /**
     * Parser Methoden
     */

    static $_tmp = array();

    /**
     * T Blöcke in einem String finden
     *
     * @param String $string
     * @return Array
     */
    static function getTBlocksFromString($string)
    {
        if (strpos($string, '{/t}') === false) {
            return array();
        }

        self::$_tmp = array();

        preg_replace_callback(
			'/{t([^}]*)}([^[{]*){\/t}/im',
			function($params)
			{
                if (isset($params[1]) && !empty($params[1]))
                {
                    $_params = explode(' ', trim($params[1]));
                    $_params = str_replace(array('"', "'"), '', $_params);

                    $group = '';
                    $var   = '';

                    foreach ($_params as $param)
                    {
                        $_param = explode('=', $param);

                        if ($_param[0] == 'groups') {
                            $group = $_param[1];
                        }

                        if ($_param[0] == 'var') {
                            $var = $_param[1];
                        }
                    }

                    \QUI\Translater::$_tmp[] = array(
                        'groups' => $group,
                        'var'    => $var
                    );

                    return;
                }

                $_param = explode(' ', $params[2]);

                if (strpos($_param[0], '/') === false ||
                    strpos($_param[1], ' ') !== false)
                {
                    \QUI\Translater::$_tmp[] = array(
                        'var' => $params[2]
                    );
                }

                \QUI\Translater::$_tmp[] = array(
                    'groups' => $_param[0],
                	'var'    => $_param[1],
                );
			},
			$string
		);

        return self::$_tmp;
    }

    /**
     * PHP Blöcke in einem String finden
     *
     * @param String $string
     * @return Array
     */
    static function getLBlocksFromString($string)
    {
    	if (strpos($string, '$L->get(') === false &&
    		strpos($string, '$Locale->get(') === false) {
    		return array();
    	}

    	self::$_tmp = array();

    	preg_replace_callback(
    		'/\$L(ocale)?->get\s*\(\s*\'([^)]*)\'\s*,\s*\'([^[)]*)\'\s*\)/im',
        	function($params)
        	{
        		if (isset(	$params[2]) && !empty($params[2]) &&
        					isset($params[3]) && !empty($params[3]) &&
        					strpos($_param[2], '/') === false)
        		{
    	    		\QUI\Translater::$_tmp[] = array(
        				'groups' => $params[2],
        				'var'    => $params[3],
    	    		);
        		}
        	},
    	    $string
    	);

    	return self::$_tmp;
    }

    /**
     * Deletes double group-var entries
     *
     * @param Array $array
     * @return Array
     */
    static function deleteDoubleEntries($array)
    {
    	// Doppelte Einträge löschen
    	$new_tmp = array();

    	foreach($array as $tmp)
    	{
    		if (!isset($new_tmp[$tmp['groups'].$tmp['var']])) {
    			$new_tmp[$tmp['groups'].$tmp['var']] = $tmp;
    		}
    	}

    	$array = array();

    	foreach ($new_tmp as $tmp) {
    		$array[] = $tmp;
    	}

    	return $array;
    }
}

?>