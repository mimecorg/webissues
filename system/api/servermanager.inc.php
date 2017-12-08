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
* Manage information and settings of the server.
*
* This class implements caching so that multiple requests to the database
* are not required.
*
* @see System_Api_PreferencesManager
*/
class System_Api_ServerManager extends System_Api_Base
{
    private static $server = null;
    private static $settings = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Return basic information about the server.
    */
    public function getServer()
    {
        if ( !isset( self::$server ) ) {
            $query = 'SELECT server_name, server_uuid, db_version FROM {server}';

            self::$server = $this->connection->queryRow( $query );

            self::$server[ 'server_version' ] = WI_VERSION;
        }

        return self::$server;
    }

    /**
    * Change the name of the server.
    * @param $newName The new name of the server.
    * @return @c true if the server was renamed.
    */
    public function renameServer( $newName )
    {
        $server = $this->getServer();
        $oldName = $server[ 'server_name' ];

        if ( $newName == $oldName )
            return false;

        $query = 'UPDATE {server} SET server_name = %s';
        $this->connection->execute( $query, $newName );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Renamed the server from "%1" to "%2"', null, $oldName, $newName ) );

        self::$server[ 'server_name' ] = $newName;

        return true;
    }

    /**
    * Change the unique identifier of the server.
    * @param $newUuid The new name of the server.
    * @return @c true if the identifier was changed.
    */
    public function setServerUuid( $newUuid )
    {
        $server = $this->getServer();
        $oldUuid = $server[ 'server_uuid' ];

        if ( $newUuid == $oldUuid )
            return false;

        $query = 'UPDATE {server} SET server_uuid = %s';
        $this->connection->execute( $query, $newUuid );

        $eventLog = new System_Api_EventLog( $this );
        $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
            $eventLog->tr( 'Changed the unique identifier of the server' ) );

        self::$server[ 'server_uuid' ] = $newUuid;

        return true;
    }

    /**
    * Generate a random unique identifier.
    */
    public function generateUuid()
    {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
    }

    /**
    * Return all server settings as an associative array.
    */
    public function getSettings()
    {
        if ( !isset( self::$settings ) ) {
            $query = 'SELECT set_key, set_value FROM {settings}';

            $table = $this->connection->queryTable( $query );

            self::$settings = array();
            foreach ( $table as $row )
                self::$settings[ $row[ 'set_key' ] ] = $row[ 'set_value' ];
        }

        return self::$settings;
    }

    /**
    * Return an array of associative arrays representing public settings.
    */
    public function getSettingsAsTable()
    {
        $defaults = array(
            'time_zone' => date_default_timezone_get()
        );

        $allSettings = $this->getSettings();

        $keys = array(
            'language', 'time_zone',
            'number_format', 'date_format', 'time_format', 'first_day_of_week',
            'project_page_size', 'folder_page_size', 'history_page_size',
            'hide_id_column', 'hide_empty_values', 'history_order', 'history_filter',
            'default_format',
            'comment_max_length', 'file_max_size'
        );

        $settings = array();

        foreach ( $keys as $key ) {
            if ( isset( $allSettings[ $key ] ) )
                $settings[ $key ] = $allSettings[ $key ];
            else if ( isset( $defaults[ $key ] ) )
                $settings[ $key ] = $defaults[ $key ];
            else
                $settings[ $key ] = null;
        }

        $settings[ 'email_enabled' ] = ( $this->getSetting( 'email_engine' ) != null ) ? '1' : null;

        $result = array();

        foreach ( $settings as $key => $value )
            $result[] = array( 'set_key' => $key, 'set_value' => $value );

        return $result;
    }

    /**
    * Get the specific setting of the server.
    * @param $key Name of the setting.
    * @return The value of the setting.
    */
    public function getSetting( $key )
    {
        $settings = $this->getSettings();

        return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
    }

    /**
    * Modify the setting of the server.
    * @param $key Name of the setting to modify.
    * @param $newValue The new value of the setting.
    * @return @c true if the setting value was modified.
    */
    public function setSetting( $key, $newValue )
    {
        $oldValue = $this->getSetting( $key );

        if ( $newValue == $oldValue )
            return false;

        if ( $oldValue == '' )
            $query = 'INSERT INTO {settings} ( set_key, set_value ) VALUES ( %1s, %2s )';
        else if ( $newValue == '' )
            $query = 'DELETE FROM {settings} WHERE set_key = %1s';
        else
            $query = 'UPDATE {settings} SET set_value = %2s WHERE set_key = %1s';

        $this->connection->execute( $query, $key, $newValue );

        self::$settings[ $key ] = $newValue;

        return true;
    }
}
