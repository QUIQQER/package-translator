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
 *
 * mysql fix for old dev version
 *
 	UPDATE translate
    SET groups = REPLACE(groups, '\'', '') WHERE 1;
    UPDATE translate
    SET var = REPLACE(var, '\'', '') WHERE 1
 */

class Translator
{
    const TABLE = 'translate';

    /**
     * Add / create a new language
     *
     * @param String $lang
     */
    static function addLang($lang)
    {
        if ( strlen( $lang ) !== 2 )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                	'package/translator',
                	'exception.lang.shortcut.not.allowed'
                )
            );
        }

        \QUI::getDB()->createTableFields(
            \QUI::getDBTableName( self::TABLE ),
            array(
                $lang          => 'text NOT NULL',
                $lang .'_edit' => 'text NULL'
            )
        );
    }

    /**
     * Export a locale group as xml
     *
     * @param String $group - which group should be exported?
     * @param Bool $edit    - Eport edit fields or original? standard=true
     * @return String
     */
    static function export($group, $edit=true)
    {
        $entries = self::get( $group );
        $pool    = array();

        foreach ( $entries as $entry )
        {
            $type   = 'php';
            $define = $group;

            if ( isset( $entry['datatype'] ) && !empty( $entry['datatype'] ) ) {
                $type = $entry['datatype'];
            }

            if ( isset( $entry['datadefine'] ) && !empty( $entry['datadefine'] ) ) {
                $define = $entry['datadefine'];
            }

            $pool[ $type ][ $define ][] = $entry;
        }

        $result = '';


        foreach ( $pool as $type => $groups )
        {
            foreach ( $groups as $define => $entries )
            {
                $result .= '<groups name="'. $group .'" type="'. $type .'"';

                if ( $define != $group ) {
                    $result .= ' define="'. $define .'"';
                }

                $result .= '>'."\n";

                foreach ( $entries as $entry )
                {
                    $result .= "\t".'<locale name="'. $entry['var'] .'">'."\n";

                    foreach ( $entry as $lang => $translation )
                    {
                        if ( strlen( $lang ) == 2 )
                        {
                            if ( $edit &&
                                 isset( $entry[ $lang .'_edit' ] ) &&
                                 !empty( $entry[ $lang .'_edit' ] ))
                            {
                                $result .= "\t\t".'<'. $lang .'>';
                                $result .= '<![CDATA['. $entry[ $lang. '_edit' ] .']]>';
                                $result .= '</'. $lang .'>'."\n";

                                continue;
                            }

                            $result .= "\t\t".'<'. $lang .'>';
                            $result .= '<![CDATA['. $translation .']]>';
                            $result .= '</'. $lang .'>'."\n";
                        }
                    }

                    $result .= "\t".'</locale>'."\n";
                }

                $result .= '</groups>'."\n";
            }
        }

        return $result;
    }

    /**
     * Import a locale xml file
     *
     * @param String $file     - path to the file
     * @param Bool $update_edit_fields - if true, the _edit fields would be updated
     * 									 if false, the original fields would be updated
     *
     * @return Array - List of imported vars
     * @throws QException
     */
    static function import($file, $update_edit_fields=true)
    {
        if ( !file_exists( $file ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'package/translator',
                    'exception.lang.file.not.exist'
                )
            );
        }

        $locales = \Utils_Xml::getLocaleGroupsFromDom(
            \Utils_Xml::getDomFromXml( $file )
        );

        $group  = $locales['group'];
        $result = array();

        foreach ( $locales['locales'] as $locale )
        {
            $var = $locale['name'];
            unset( $locale['name'] );

            try
            {
                self::add( $group, $var );
            } catch ( \QException $e )
            {

            }

            // update only in the _edit fields
            if ( $update_edit_fields )
            {
                $_locale = array();

                foreach ( $locale as $key => $entry ) {
                    $_locale[ $key.'_edit' ] = $entry;
                }

                self::edit( $group, $var, $_locale );
            } else
            {
                // set the original fields
                self::update( $group, $var, $locale );
            }

            $result[] = array(
                'group'  => $group,
            	'var'    => $var,
                'locale' => $locale
            );
        }

        return $result;
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
     * remove all dublicate entres from the translation table
     *
     * because:
     * #1071 - Specified key was too long; max key length is 1000 bytes
     *
     * we cannot use unique keys :/
     */
    static function cleanup()
    {
        $PDO       = \QUI::getDataBase()->getPDO();
        $bad_table = \QUI::getDBTableName( self::TABLE );

        // check if dublicate entries exist
        $Statement = $PDO->prepare(
            'SELECT `groups`, `var`
            FROM '. $bad_table .'
            GROUP BY `groups`, `var`
            HAVING count( * ) > 1'
        );

        $Statement->execute();

        if ( !$Statement->fetch() ) {
            return;
        }

        $Statement = $PDO->prepare(
        	'CREATE TEMPORARY TABLE bad_temp_translation AS
        	SELECT DISTINCT * FROM '. $bad_table
        );
        $Statement->execute();

        $Statement = $PDO->prepare( 'DELETE FROM '. $bad_table );
        $Statement->execute();

        $Statement = $PDO->prepare( 'INSERT INTO '. $bad_table .' SELECT * FROM bad_temp_translation' );
        $Statement->execute();
    }

    /**
     * Create the locale files
     */
    static function create()
    {
        // first step, a cleanup
        // so we get no errors in gettext
        self::cleanup();

        $langs = self::langs();
        $dir   = self::dir();

        // Sprach Ordner erstellen
        $folders    = array();
        $exec_error = array();

        foreach ( $langs as $lang )
        {
            $folders[ $lang ] = $dir .'/'. \Utils_String::toLower($lang) .
            						  '_'. \Utils_String::toUpper($lang) .
            						  '/LC_MESSAGES/';

            \Utils_System_File::unlink( $folders[ $lang ] );
            \Utils_System_File::mkdir( $folders[ $lang ] );
        }


        // Sprachdateien erstellen
        foreach ( $langs as $lang )
        {
            if ( strlen( $lang ) !== 2 ) {
                continue;
            }

            $result = \QUI::getDB()->select(array(
                'select' => array(
                    $lang, $lang .'_edit', 'groups', 'var'
                ),
                'from' => \QUI::getDBTableName( self::TABLE )
            ));

            foreach ( $result as $entry )
            {
                $value = $entry[ $lang ];

                if ( isset( $entry[ $lang.'_edit' ] ) &&
                     !empty( $entry[ $lang.'_edit' ]) )
                {
                    $value = $entry[ $lang.'_edit' ]; // benutzer übersetzung
                }

                $value = str_replace( '\\', '\\\\', $value );
                $value = str_replace( '"', '\"', $value );
                $value = nl2br( $value );
                $value = str_replace( "\n", '', $value );

                if ( $value !== '' && $value !== ' ' ) {
                    $value = trim( $value );
                }

                // ini Datei
                $ini     = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.ini';
                $ini_str = $entry['var'] .'= "'. $value .'"';

                \Utils_System_File::mkfile( $ini );
                \Utils_System_File::putLineToFile( $ini, $ini_str );

                // po (gettext) datei
                $po = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.po';
                $mo = $folders[ $lang ] . str_replace( '/', '_', $entry['groups'] ) .'.mo';

                \Utils_System_File::mkfile($po);

                \Utils_System_File::putLineToFile( $po, 'msgid "'. $entry['var'] .'"' );
                \Utils_System_File::putLineToFile( $po, 'msgstr "'. $value .'"' );
                \Utils_System_File::putLineToFile( $po, '' );
            }

            // alle .po dateien einlesen und in mo umwandeln
            if ( function_exists( 'gettext' ) ) //@todo getText über Config ein und ausschaltbar machen
            {
                $po_files = \Utils_System_File::readDir( $folders[ $lang ] );

                foreach ( $po_files as $po_file )
                {
                    if ( substr( $po_file, -3 ) == '.po' )
                    {
                        $exec = 'msgfmt '. $folders[ $lang ]. $po_file .' -o '. $folders[ $lang ] . substr( $po_file, 0,-3 ).'.mo' ;
                        exec( \Utils_Security_Orthos::clearShell( $exec ) .' 2>&1', $exec_error );
                    }
                }
            }
        }

        if ( !empty( $exec_error ) ) {
            \QUI::getMessagesHandler()->addError( $exec_error );
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
        if ( !$var )
        {
            return \QUI::getDB()->select(array(
                'from' => \QUI::getDBTableName( self::TABLE ),
                'where' => array(
                    'groups' => $group
                )
            ));
        }

        return \QUI::getDB()->select(array(
            'from' => \QUI::getDBTableName( self::TABLE ),
            'where' => array(
                'groups' => $group,
                'var'    => $var
            )
        ));
    }

    /**
     * Daten für die Tabelle bekommen
     *
     * @param String $groups - Gruppe
     * @param Array $params  - optional array(limit => 10, page => 1)
     * @param Array $search  - optional array(search => '%str%', fields => '')
     *
     * @return Array
     */
    static function getData($groups, $params=array(), $search=false)
    {
        $max  = 10;
        $page = 1;

        if ( isset( $params['limit'] ) ) {
            $max = (int)$params['limit'];
        }

        if ( isset( $params['page'] ) ) {
            $page = (int)$params['page'];
        }

        $page  = ($page - 1) ? $page - 1 : 0;
        $limit = ($page * $max) .','. $max;

        if ( $search && isset( $search['search'] ) )
        {
            $where  = array();
            $search = array(
                'type'  => '%LIKE%',
                'value' => $search['search']
            );

            $db_fields = self::langs();

            // default fields
            $default = array(
            	'groups'     => $search,
            	'var'        => $search,
            	'datatype'   => $search,
            	'datadefine' => $search
            );

            foreach ( $db_fields as $lang )
            {
                if ( strlen( $lang ) == 2 ) {
                    $default[ $lang ] = $search;
                }
            }

            // search
            if ( !isset( $search['fields'] ) || empty( $search['fields'] ) ) {
                $fields = array();
            }

            foreach ( $fields as $field )
            {
                if ( isset( $default[ $field ] ) ) {
                    $where[ $field ] = $search;
                }
            }

            if ( empty( $where ) ) {
                $where = $default;
            }

            $data = array(
                'from'     => \QUI::getDBTableName( self::TABLE ),
                'where_or' => $where,
                'limit'    => $limit
            );
        } else
        {
            $data = array(
                'from' => \QUI::getDBTableName( self::TABLE ),
                'where' => array(
                    'groups' => $groups
                ),
                'limit' => $limit
            );
        }

        // result mit limit
        $result = \QUI::getDB()->select( $data );

        // count
        $data['count'] = 'groups';

        if ( isset( $data['limit'] ) ) {
            unset($data['limit']);
        }

        $count = \QUI::getDB()->select( $data );

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
            'from'   => \QUI::getDBTableName( self::TABLE ),
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
        if ( empty( $var ) || empty( $group ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'package/translator',
                    'exception.empty.var.group'
                )
            );
        }

        $result = self::get( $group, $var );

        if ( isset( $result[0] ) )
        {
            throw new \QException(
            	\QUI::getLocale()->get(
            	    'package/translator',
            	    'exception.var.exists'
            	)
            );
        }

        \QUI::getDB()->addData(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'groups' => $group,
                'var'    => $var
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

        foreach ( $langs as $lang )
        {
            if ( !isset( $data[ $lang ] ) ) {
                continue;
            }

            $_data[ $lang ] = $data[ $lang ];
        }

        \QUI::getDB()->updateData(
            \QUI::getDBTableName( self::TABLE ),
            $_data,
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * User Edit
     *
     * @param String $group
     * @param String $var
     * @param String $data
     */
    static function edit($group, $var, $data)
    {
        $langs = self::langs();
        $_data = array();

        $development = \QUI::conf( 'globals', 'development' );

        foreach ( $langs as $lang )
        {
            if ( $development )
            {
                if ( isset( $data[ $lang ] ) ) {
                    $_data[ $lang ] = $data[ $lang ];
                }

                if ( isset( $data[ $lang .'_edit' ] ) ) {
                    $_data[ $lang .'_edit' ] = $data[ $lang .'_edit' ];
                }

                continue;
            }

            if ( !isset( $data[ $lang ] ) ) {
                continue;
            }

            $_data[ $lang .'_edit' ] = $data[ $lang ];
        }

        if ( isset( $data[ 'datatype' ] ) ) {
            $_data[ 'datatype' ] = $data[ 'datatype' ];
        }

        if ( isset( $data[ 'datadefine' ] ) ) {
            $_data[ 'datadefine' ] = $data[ 'datadefine' ];
        }

        \QUI::getDB()->updateData(
            \QUI::getDBTableName( self::TABLE ),
            $_data,
            array(
                'groups' => $group,
                'var'    => $var
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
        \QUI::getDB()->deleteData(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'groups' => $group,
                'var'    => $var
            )
        );
    }

    /**
     * Welche Sprachen existieren
     *
     * @return Array
	 */
    static function langs()
    {
        $fields = \QUI::getDB()->getFields(
            \QUI::getDBTableName( self::TABLE )
        );

        $langs = array();

        foreach ( $fields as $entry )
        {
            if ( $entry == 'groups' ) {
                continue;
            }

            if ( $entry == 'var' ) {
                continue;
            }

            if ( $entry == 'datatype' ) {
                continue;
            }

            if ( $entry == 'datadefine' ) {
                continue;
            }

            if ( strpos($entry, '_edit') !== false ) {
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
        $fields = \QUI::getDB()->getFields(
            \QUI::getDBTableName( self::TABLE )
        );

        $langs = array();

        foreach ( $fields as $entry )
        {
            if ( $entry == 'var' || $entry == 'groups' ) {
                continue;
            }

            $langs[] = $entry;
        }

        $result = \QUI::getDB()->select(array(
            'from'  => \QUI::getDBTableName( self::TABLE ),
            'where' => implode( ' = "" OR ', $langs ) .' = ""'
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
        if ( strpos( $string, '{/t}' ) === false ) {
            return array();
        }

        self::$_tmp = array();

        preg_replace_callback(
			'/{t([^}]*)}([^[{]*){\/t}/im',
			function($params)
			{
                if ( isset( $params[1] ) && !empty( $params[1] ) )
                {
                    $_params = explode( ' ', trim( $params[1] ) );
                    $_params = str_replace( array('"', "'"), '', $_params );

                    $group = '';
                    $var   = '';

                    foreach ( $_params as $param )
                    {
                        $_param = explode( '=', $param );

                        if ( $_param[0] == 'groups' ) {
                            $group = $_param[1];
                        }

                        if ( $_param[0] == 'var' ) {
                            $var = $_param[1];
                        }
                    }

                    \QUI\Translater::$_tmp[] = array(
                        'groups' => $group,
                        'var'    => $var
                    );

                    return;
                }

                $_param = explode( ' ', $params[2] );

                if ( strpos( $_param[0], '/') === false ||
                     strpos( $_param[1], ' ') !== false )
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
    	if ( strpos( $string, '$L->get(' ) === false &&
    		 strpos( $string, '$Locale->get(' ) === false)
		{
    		return array();
    	}

    	self::$_tmp = array();

    	preg_replace_callback(
    		'/\$L(ocale)?->get\s*\(\s*\'([^)]*)\'\s*,\s*\'([^[)]*)\'\s*\)/im',
        	function($params)
        	{
        		if ( isset( $params[2] ) &&
        		     isset( $params[3] ) &&
        			 !empty( $params[2] ) &&
        			 !empty( $params[3] ) &&
        			 strpos( $_param[2], '/' ) === false )
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

    	foreach( $array as $tmp )
    	{
    		if ( !isset( $new_tmp[ $tmp['groups'] . $tmp['var'] ] ) ) {
    			$new_tmp[ $tmp['groups'] . $tmp['var'] ] = $tmp;
    		}
    	}

    	$array = array();

    	foreach ( $new_tmp as $tmp ) {
    		$array[] = $tmp;
    	}

    	return $array;
    }
}

?>