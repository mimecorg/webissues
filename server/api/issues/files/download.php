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

class Server_Api_Issues_Files_Download extends Common_Application
{
    protected function __construct()
    {
        parent::__construct( null );
    }

    protected function execute()
    {
        $principal = System_Api_Principal::getCurrent();
        $serverManager = new System_Api_ServerManager();

        if ( !$principal->isAuthenticated() ) {
            if ( $this->session->isDestroyed() || $serverManager->getSetting( 'anonymous_access' ) != 1 )
                throw new System_Api_Error( System_Api_Error::LoginRequired );
        }

        $attachmentId = (int)$this->request->getQueryString( 'id' );

        $issueManager = new System_Api_IssueManager();
        $attachment = $issueManager->getAttachment( $attachmentId );

        $this->response->setContentType( 'application/octet-stream' );
        $this->response->setAttachment( $attachment );
    }

    protected function displayErrorPage()
    {
        $exception = $this->getFatalError();
        $error = Server_Error::getErrorFromException( $exception );
        $content = Server_Error::getErrorResponse( $error, $status );

        $this->response->setStatus( $status == '200 OK' ? '400 Bad Request' : $status );
        $this->response->setContentType( 'application/json' );
        $this->response->setContent( $content );

        $this->response->send();
    }
}

System_Bootstrap::run( 'Server_Api_Issues_Files_Download' );
