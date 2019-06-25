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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Alerts_Edit
{
    public $access = '*';

    public $params = array(
        'alertId' => array( 'type' => 'int', 'required' => true ),
        'alertType' => array( 'type' => 'int', 'required' => true ),
        'alertFrequency' => array( 'type' => 'int' ),
    );

    public function run( $alertId, $alertType, $alertFrequency )
    {
        $alertManager = new System_Api_AlertManager();
        $alert = $alertManager->getAlert( $alertId, System_Api_AlertManager::AllowEdit );

        $validator = new System_Api_Validator();
        $validator->checkAlertType( $alertType );

        if ( $alertType == System_Const::Notification ) {
            if ( $alertFrequency != null )
                throw new Server_Error( Server_Error::InvalidArguments );
        } else {
            $validator->checkAlertFrequency( $alertFrequency );
        }

        $alertManager = new System_Api_AlertManager();

        $changed = $alertManager->modifyAlert( $alert, $alertType, $alertFrequency );

        $result[ 'alertId' ] = $alertId;
        $result[ 'changed' ] = $changed;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Alerts_Edit' );
