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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Settings_Advanced_Edit
{
    public $access = 'admin';

    public $params = array(
        'hideIdColumn' => 'bool',
        'hideEmptyValues' => 'bool',
        'historyOrder' => array( 'type' => 'string', 'required' => true ),
        'defaultFormat' => array( 'type' => 'int', 'required' => true ),
        'commentMaxLength' => array( 'type' => 'int', 'required' => true ),
        'fileMaxSize' => array( 'type' => 'int', 'required' => true ),
        'fileDbMaxSize' => array( 'type' => 'int', 'required' => true ),
        'sessionMaxLifetime' => array( 'type' => 'int', 'required' => true ),
        'registerMaxLifetime' => array( 'type' => 'int', 'required' => true ),
        'logMaxLifetime' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $hideIdColumn, $hideEmptyValues, $historyOrder, $defaultFormat, $commentMaxLength, $fileMaxSize, $fileDbMaxSize,
                         $sessionMaxLifetime, $registerMaxLifetime, $logMaxLifetime )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $historyOrder, System_Const::ValueMaxLength );
        $validator->checkHistoryOrder( $historyOrder );
        $validator->checkIntegerValue( $defaultFormat, 0, 1 );
        $validator->checkIntegerValue( $commentMaxLength, 1000, 100000 );
        $validator->checkIntegerValue( $fileMaxSize, 16 * 1024, 256 * 1024 * 1024 );
        $validator->checkIntegerValue( $fileDbMaxSize, 0 );
        $validator->checkIntegerValue( $sessionMaxLifetime, 5 * 60, 24 * 60 * 60 );
        $validator->checkIntegerValue( $registerMaxLifetime, 5 * 60 );
        $validator->checkIntegerValue( $logMaxLifetime, 5 * 60 );

        $settings = array(
            'hide_id_column' => $hideIdColumn ? 1 : 0,
            'hide_empty_values' => $hideEmptyValues ? 1 : 0,
            'history_order' => $historyOrder,
            'default_format' => $defaultFormat,
            'comment_max_length' => $commentMaxLength,
            'file_max_size' => $fileMaxSize,
            'file_db_max_size' => $fileDbMaxSize,
            'session_max_lifetime' => $sessionMaxLifetime,
            'register_max_lifetime' => $registerMaxLifetime,
            'log_max_lifetime' => $logMaxLifetime
        );

        $serverManager = new System_Api_ServerManager();

        $changed = false;

        foreach ( $settings as $key => $value ) {
            if ( $serverManager->setSetting( $key, $value ) )
                $changed = true;
        }

        $result[ 'changed' ] = $changed;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Advanced_Edit' );
