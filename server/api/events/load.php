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
        'eventId' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $eventId )
    {
        $eventLog = new System_Api_EventLog();
        $event = $eventLog->getEvent( $eventId );

        $resultDetails[ 'id' ] = $event[ 'event_id' ];
        $resultDetails[ 'type' ] = $event[ 'event_type' ];
        $resultDetails[ 'severity' ] = $event[ 'event_severity' ];
        $resultDetails[ 'date' ] = $event[ 'event_time' ];
        $resultDetails[ 'message' ] = $event[ 'event_message' ];
        $resultDetails[ 'user' ] = $event[ 'user_name' ];
        $resultDetails[ 'host' ] = $event[ 'host_name' ];

        $result[ 'details' ] = $resultDetails;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Events_List' );
