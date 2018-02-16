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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Issue_File_Add
{
    public function run( $arguments, $attachment )
    {
        $principal = System_Api_Principal::getCurrent();
        $principal->checkAuthenticated();

        if ( $attachment == null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'file_max_size' );

        if ( $attachment->getSize() > $maxLength )
            throw new Server_Error( Server_Error::UploadError );

        $issueId = isset( $arguments[ 'issueId' ] ) ? (int)$arguments[ 'issueId' ] : null;
        $name = isset( $arguments[ 'name' ] ) ? $arguments[ 'name' ] : null;
        $description = isset( $arguments[ 'description' ] ) ? $arguments[ 'description' ] : null;

        if ( $issueId == null || $name == null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );

        $parser = new System_Api_Parser();

        $name = $parser->normalizeString( $name, System_Const::FileNameMaxLength );
        $description = $parser->normalizeString( $description, System_Const::DescriptionMaxLength, System_Api_Parser::AllowEmpty );

        $stampId = $issueManager->addFile( $issue, $attachment, $name, $description );

        $result[ 'stampId' ] = $stampId;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issue_File_Add' );
