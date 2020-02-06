<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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
* An instance of this class is accessible through the System_Core_Application
* object. In most cases System_Web_Base::tr() should be used instead of
* directly accessing this object.
*/
class System_Core_Translator
{
    private $language = array();

    private $messages = array();

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
    * Return a translated string with the given key.
    * The key is returned if no translation is available.
    * @param $mode Mode of the translator.
    * @param $key The key of the translation.
    * @param $args Array containing optional arguments which replace placeholders ({0}, {1}, etc.).
    */
    public function translate( $mode, $key, $args )
    {
        $translated = $this->getTranslation( $mode, $key );

        if ( $translated == null ) {
            $debug = System_Core_Application::getInstance()->getDebug();
            if ( $debug->checkLevel( DEBUG_ERRORS ) )
                $debug->write( '*** Warning: Missing translation for key: ', $key, "\n" );

            $translated = $key;
        }

        if ( !empty( $args ) ) {
            $parts = preg_split( '/\{(\d+)\}/', $translated, -1, PREG_SPLIT_DELIM_CAPTURE );

            $result = array( $parts[ 0 ] );

            for ( $i = 1; $i < count( $parts ); $i += 2 ) {
                $index = (int)$parts[ $i ];
                if ( $index < count( $args ) )
                    $result[] = $args[ $index ];
                else
                    $result[] = '{' . $index . '}';

                $result[] = $parts[ $i + 1 ];
            }

            $translated = implode( '', $result );
        }

        return $translated;
    }

    /**
    * Return @c true if a translated string with the given key exists.
    * @param $mode Mode of the translator.
    * @param $key The key of the transloation.
    */
    public function translationExists( $mode, $key )
    {
        $translated = $this->getTranslation( $mode, $key );

        return $translated != null;
    }

    private function getTranslation( $mode, $key )
    {
        if ( !isset( $this->language[ $mode ] ) )
            $mode = self::SystemLanguage;

        if ( isset( $this->language[ $mode ] ) )
            $language = $this->language[ $mode ];
        else
            $language = 'en_US';

        $translated = $this->lookupTranslation( $language, $key );

        if ( $translated == null && $language != 'en_US' )
            $translated = $this->lookupTranslation( 'en_US', $key );

        return $translated;
    }

    private function lookupTranslation( $language, $key )
    {
        if ( !isset( $this->messages[ $language ] ) )
            $this->loadMessages( $language );

        if ( !empty( $this->messages[ $language ] ) ) {
            $current = $this->messages[ $language ];

            $parts = explode( '.', $key );

            foreach ( $parts as $part ) {
                if ( is_array( $current ) && isset( $current[ $part ] ) )
                    $current = $current[ $part ];
                else
                    return null;
            }

            if ( is_string( $current ) )
                return $current;
        }

        return null;
    }

    private function loadMessages( $language )
    {
        $this->messages[ $language ] = array();

        $path = WI_ROOT_DIR . '/common/i18n/' . $language . '.json';

        if ( file_exists( $path ) )
            $this->messages[ $language ] = json_decode( file_get_contents( $path ), true );
    }
}
