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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Events_List
{
    public $access = 'admin';

    public $params = array(
        'type' => 'string',
        'offset' => array( 'type' => 'int', 'default' => 0 ),
        'limit' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $type, $offset, $limit )
    {
        if ( $offset < 0 || $limit < 1 )
            throw new Server_Error( Server_Error::InvalidArguments );

        if ( $type != null ) {
            $validator = new System_Api_Validator();
            $validator->checkString( $type, System_Const::NameMaxLength );
        }

        $eventLog = new System_Api_EventLog();
        $events = $eventLog->getEvents( $type, 'event_id DESC', $limit, $offset );

        $result[ 'events' ] = array();

        foreach ( $events as $event ) {
            $resultEvent = array();

            $resultEvent[ 'id' ] = $event[ 'event_id' ];
            $resultEvent[ 'type' ] = $event[ 'event_type' ];
            $resultEvent[ 'severity' ] = $event[ 'event_severity' ];
            $resultEvent[ 'date' ] = $event[ 'event_time' ];
            $resultEvent[ 'message' ] = $event[ 'event_message' ];

            $result[ 'events' ][] = $resultEvent;
        }

        $result[ 'totalCount' ] = $eventLog->getEventsCount( $type );

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Events_List' );
