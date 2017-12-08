<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2017 WebIssues Team
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
**************************************************************************/

if ( !defined( 'WI_VERSION' ) ) die( -1 );

/**
* Mechanism for translating user visible strings.
*
* This class can load translations created using the linguistphp package
* which is compatible with the Qt Linguist.
*
* Translations can be divided into separate modules, which allows to reduce
* the overhead by loading only those modules which are needed. Also language
* variants are supported. For example if language is set to 'en_US' and
* 'common' and 'client' modules are enabled, translations are searched
* in the following files (in order of precedence):
*  - common_en_US.phm
*  - client_en_US.phm
*  - common_en.phm
*  - client_en.phm
*
* An instance of this class is accessible through the System_Core_Application
* object. In most cases System_Web_Base::tr() should be used instead of
* directly accessing this object.
*/
class System_Core_Translator
{
    private $language = array();
    private $modules = array();

    private $files = array();

    private $data = array();

    /**
    * @name Translator Modes
    */
    /*@{*/
    /** Use system default language. */
    const SystemLanguage = 0;
    /** Use language of the current user. */
    const UserLanguage = 1;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Set the language to be used by the translator in the given mode.
    */
    public function setLanguage( $mode, $language )
    {
        $this->language[ $mode ] = $language;
    }

    /**
    * Return the language used in the given mode or @c null if none was set.
    */
    public function getLanguage( $mode )
    {
        return isset( $this->language[ $mode ] ) ? $this->language[ $mode ] : null;
    }

    /**
    * Add a module to the list of enabled modules.
    * @param $module The name of the module to enable.
    */
    public function addModule( $module )
    {
        if ( !in_array( $module, $this->modules ) ) {
            $this->modules[] = $module;
            $this->files = array();
        }
    }

    /**
    * Return a translated version of the source string.
    * The original string is returned if no translation is available.
    * @param $mode Mode of the translator.
    * @param $context The name of the context, usually the class where the string
    * is used.
    * @param $args Array containing the source string to translate, an optional
    * comment explaining the use of the string and optional arguments which replace
    * placeholders (%%1, %%2, etc.).
    */
    public function translate( $mode, $context, $args )
    {
        if ( !isset( $this->language[ $mode ] ) )
            $mode = self::SystemLanguage;

        if ( isset( $this->language[ $mode ] ) )
            $translated = $this->lookupTranslation( $this->language[ $mode ], $context, $args[ 0 ], isset( $args[ 1 ] ) ? $args[ 1 ] : null );
        else
            $translated = $args[ 0 ];

        if ( count( $args ) > 2 ) {
            // replace %1, %2, %3, etc. with additional function arguments
            $parts = preg_split( '/%(\d+)/', $translated, -1, PREG_SPLIT_DELIM_CAPTURE );

            $result = array( $parts[ 0 ] );

            for ( $i = 1; $i < count( $parts ); $i += 2 ) {
                $index = (int)$parts[ $i ];
                if ( $index > 0 && $index < count( $args ) - 1 )
                    $result[] = $args[ $index + 1 ];
                else
                    $result[] = '%' . $index;

                $result[] = $parts[ $i + 1 ];
            }

            $translated = implode( '', $result );
        }

        return $translated;
    }

    private function lookupTranslation( $language, $context, $source, $comment )
    {
        // rebuild list of message files if a module was added or language was changed
        if ( !isset( $this->files[ $language ] ) )
            $this->loadMessageFiles( $language );

        if ( !empty( $this->files[ $language ] ) ) {

            // use CRC as hash value
            $crc = crc32( $source );
            if ( $context != null )
                $crc ^= crc32( $context );
            if ( $comment != null )
                $crc ^= crc32( $comment );

            // workaround for https://bugs.php.net/bug.php?id=39062
            if ( $crc > System_Const::INT_MAX )
                $crc = $crc - 2 * System_Const::INT_MAX - 2;

            foreach ( $this->files[ $language ] as $file ) {
                $contexts =& $this->data[ $file ][ 'contexts' ];
                $messages =& $this->data[ $file ][ 'messages' ];

                // find a matching string in the bucket for given hash
                // buckets have the following form: { contextId, source, comment, translation, contextId2, ... }
                if ( isset( $messages[ $crc ] ) ) {
                    $bucket =& $messages[ $crc ];
                    for ( $i = 0; $i < count( $bucket ); $i += 4 ) {
                        if ( $bucket[ $i + 1 ] == $source && $bucket[ $i + 2 ] == $comment
                             && ( $bucket[ $i ] === null ? $context == null : $contexts[ $bucket[ $i ] ] == $context ) ) {
                            return $bucket[ $i + 3 ];
                        }
                    }
                }
            }
        }

        return $source;
    }

    private function loadMessageFiles( $language )
    {
        $this->files[ $language ] = array();

        foreach ( $this->modules as $module ) {
            $suffix = '_' . $language;

            while ( $suffix != '' ) {
                $file = $module . $suffix;

                if ( !isset( $this->data[ $file ] ) ) {
                    $path = WI_ROOT_DIR . '/data/translations/' . $file . '.phm';
                    if ( file_exists( $path ) ) {
                        $this->data[ $file ] = unserialize( file_get_contents( $path ) );
                    } else {
                        $path = WI_ROOT_DIR . '/common/data/translations/' . $file . '.phm';
                        if ( file_exists( $path ) )
                            $this->data[ $file ] = unserialize( file_get_contents( $path ) );
                        else
                            $this->data[ $file ] = false;
                    }
                }

                if ( $this->data[ $file ] !== false ) {
                    $this->files[ $language ][] = $file;
                    break;
                }

                // strip last component from the suffix, e.g. '_pt_BR' becomes '_pt'
                $suffix = substr( $suffix, 0, strrpos( $suffix, '_' ) );
            }
        }
    }
}
