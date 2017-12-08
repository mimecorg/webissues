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
* Information about current locale.
*/
class System_Api_Locale
{
    private static $cache = array();

    private $userId = null;
    private $language = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        $this->userId = System_Api_Principal::getCurrent()->getUserId();
    }

    /**
    * Set specific language for current user's locale.
    */
    public function setLanguage( $language )
    {
        if ( !array_key_exists( $language, $this->getAvailableLanguages() ) )
            $language = 'en_US';

        if ( $this->language == $language )
            return;

        if ( $this->language != null )
            unset( self::$cache[ $this->userId ] );

        $this->language = $language;
    }

    /**
    * Return all locale settings as an assotiative array.
    */
    public function getSettings()
    {
        if ( !isset( self::$cache[ $this->userId ] ) ) {
            $preferencesManager = new System_Api_PreferencesManager();

            if ( $this->language == null )
                $this->language = $preferencesManager->getPreferenceOrSetting( 'language' );

            $locale = System_Core_IniFile::parseExtended( '/common/data/locale.ini', '/data/locale.ini' );

            $settings = $locale[ 'global' ];
            if ( isset( $locale[ $this->language ] ) )
                $settings = array_merge( $settings, $locale[ $this->language ] );

            $settings[ 'time_zone' ] = date_default_timezone_get();

            foreach ( $settings as $key => &$value ) {
                $preference = $preferencesManager->getPreferenceOrSetting( $key );
                if ( $preference != null )
                    $value = $preference;
            }

            $settings[ 'language' ] = $this->language;

            self::$cache[ $this->userId ] = $settings;
        }

        return self::$cache[ $this->userId ];
    }

    /**
    * Return value of the locale setting.
    * @param $key Name of the setting to return.
    * @return The value of the setting.
    */
    public function getSetting( $key )
    {
        $settings = $this->getSettings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    /**
    * Return the current format for the given setting.
    * @param $key Name of the setting to return.
    * @return The format of the setting.
    */
    public function getSettingFormat( $key )
    {
        $settings = $this->getSettings();
        $formats = $this->getAvailableFormats( $key );

        return isset( $settings[ $key ] ) ? $formats[ $settings[ $key ] ] : null;
    }

    /**
    * Return the list of available languages.
    */
    public function getAvailableLanguages()
    {
        $locale = System_Core_IniFile::parseExtended( '/common/data/locale.ini', '/data/locale.ini' );

        $languages = $locale[ 'languages' ];
        ksort( $languages );

        return $languages;
    }

    /**
    * Return an array of associative arrays representing available languages.
    */
    public function getLanguagesAsTable()
    {
        $languages = $this->getAvailableLanguages();

        $result = array();
        foreach ( $languages as $key => $name )
            $result[] = array( 'lang_key' => $key, 'lang_name' => $name );

        return $result;
    }

    /**
    * Return the list of available formats for the given setting.
    */
    public function getAvailableFormats( $key )
    {
        $formats = System_Core_IniFile::parseRaw( '/common/data/formats.ini' );

        return $formats[ $key ];
    }

    /**
    * Return the list of available time zones.
    */
    public function getAvailableTimeZones()
    {
        $timeZones = System_Core_IniFile::parse( '/common/data/timezones.ini', true );

        $zones = array();
        foreach ( DateTimeZone::listIdentifiers() as $zone ) {
            if ( preg_match( '/^(Africa|America|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $zone ) ) {
                if ( !isset( $timeZones[ 'aliases' ][ $zone ] ) )
                    $zones[] = $zone;
            }
        }

        return $zones;
    }

    /**
    * Return an array of associative arrays representing available time zones.
    */
    public function getTimeZonesAsTable()
    {
        $zones = $this->getAvailableTimeZones();

        $date = new DateTime();

        $result = array();
        foreach ( $zones as $zone ) {
            $date->setTimeZone( new DateTimeZone( $zone ) );
            $offset = $date->format( 'Z' );
            $result[] = array( 'zone_name' => $zone, 'zone_offset' => $offset );
        }

        return $result;
    }
}
