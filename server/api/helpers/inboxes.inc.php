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

class Server_Api_Helpers_Inboxes
{
    public function validateBasic( $engine, $email, $server, $port, $encryption, $user, $password, $mailbox )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $engine, System_Const::ValueMaxLength );
        $validator->checkInboxEngine( $engine );
        $validator->checkString( $email, System_Const::ValueMaxLength );
        $validator->checkEmailAddress( $email );
        $validator->checkString( $server, System_Const::ValueMaxLength );
        $validator->checkIntegerValue( $port, 1, 65536 );
        $validator->checkString( $encryption, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkEncryption( $encryption );
        $validator->checkString( $user, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkString( $password, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkString( $mailbox, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
    }

    public function validateExtended( $engine, $leaveMessages, $allowExternal, $robot, $mapFolder, $defaultFolder )
    {
        if ( $engine == 'pop3' && $leaveMessages )
            throw new System_Api_Error( System_Api_Error::InvalidSetting );

        if ( $allowExternal && $robot == null )
            throw new System_Api_Error( System_Api_Error::EmptyValue );

        if ( !$mapFolder && $defaultFolder == null )
            throw new System_Api_Error( System_Api_Error::EmptyValue );

        if ( $robot != null ) {
            $userManager = new System_Api_UserManager();
            $userManager->getUser( $robot );
        }

        if ( $defaultFolder != null ) {
            $projectManager = new System_Api_ProjectManager();
            $projectManager->getFolder( $defaultFolder );
        }
    }
}
