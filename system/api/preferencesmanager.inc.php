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
* Manage preferences for a given user.
*
* This class implements caching for preferences so that multiple requests
* to the database are not required.
*
* @see System_Api_ServerManager
*/
class System_Api_PreferencesManager extends System_Api_Base
{
    private static $cache = array( 0 => array() );

    private $userId = null;

    /**
    * Constructor.
    * @param $user The user whose preferences are accessed, if @c null then
    * the current user is assumed.
    */
    public function __construct( $user = null )
    {
        parent::__construct();

        $this->userId = ( $user != null ) ? $user[ 'user_id' ] : System_Api_Principal::getCurrent()->getUserId();
    }

    /**
    * Return all preferences as an associative array.
    */
    public function getPreferences()
    {
        if ( !isset( self::$cache[ $this->userId ] ) ) {
            $query = 'SELECT pref_key, pref_value FROM {preferences} WHERE user_id = %d';

            $table = $this->connection->queryTable( $query, $this->userId );

            $preferences = array();
            foreach ( $table as $row )
                $preferences[ $row[ 'pref_key' ] ] = $row[ 'pref_value' ];

            self::$cache[ $this->userId ] = $preferences;
        }

        return self::$cache[ $this->userId ];
    }

    /**
    * Return an array of associative arrays representing preferences.
    */
    public function getPreferencesAsTable()
    {
        $preferences = $this->getPreferences();

        $result = array();
        foreach ( $preferences as $key => $value )
            $result[] = array( 'pref_key' => $key, 'pref_value' => $value );

        return $result;
    }

    /**
    * Get the specific preference of the user.
    * @param $key Name of the preference.
    * @return The value of the preference.
    */
    public function getPreference( $key )
    {
        $preferences = $this->getPreferences();

        return isset( $preferences[ $key ] ) ? $preferences[ $key ] : null;
    }

    /**
    * Modify the preference for the user.
    * @param $key Name of the preference to modify.
    * @param $newValue The new value of the preference.
    * @return @c true if the preference value was modified.
    */
    public function setPreference( $key, $newValue )
    {
        $oldValue = $this->getPreference( $key );

        if ( $newValue == $oldValue )
            return false;

        if ( System_Core_Application::getInstance()->getSite()->getConfig( 'demo_mode' ) )
            System_Api_Principal::getCurrent()->checkAdministrator();

        if ( $key == 'email' && $newValue != '' ) {
            $query = 'SELECT user_id FROM {preferences} WHERE pref_key = %s AND UPPER( pref_value ) = %s';
            if ( $this->connection->queryScalar( $query, 'email', mb_strtoupper( $newValue ) ) !== false )
                throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );
        }

        if ( $oldValue == '' )
            $query = 'INSERT INTO {preferences} ( user_id, pref_key, pref_value ) VALUES ( %1d, %2s, %3s )';
        else if ( $newValue == '' )
            $query = 'DELETE FROM {preferences} WHERE user_id = %1d AND pref_key = %2s';
        else
            $query = 'UPDATE {preferences} SET pref_value = %3s WHERE user_id = %1d AND pref_key = %2s';

        $this->connection->execute( $query, $this->userId, $key, $newValue );

        self::$cache[ $this->userId ][ $key ] = $newValue;

        return true;
    }

    /**
    * Get the specific preference of the user, or server setting if preference
    * is not available.
    * @param $key Name of the preference.
    * @return The value of the preference or setting.
    */
    public function getPreferenceOrSetting( $key )
    {
        $preferences = $this->getPreferences();

        if ( isset( $preferences[ $key ] ) )
            return $preferences[ $key ];

        $serverManager = new System_Api_ServerManager();
        return $serverManager->getSetting( $key );
	}
}
