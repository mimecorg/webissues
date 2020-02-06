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

class Server_Api_Issues_Preview
{
    public $access = '*';

    public $params = array(
        'text' => 'string'
    );

    public function run( $text )
    {
        $serverManager = new System_Api_ServerManager();
        $validator = new System_Api_Validator();

        $validator->checkString( $text, $serverManager->getSetting( 'comment_max_length' ), System_Api_Validator::MultiLine | System_Api_Validator::AllowEmpty );

        System_Web_Base::setLinkMode( System_Web_Base::RouteLinks );

        return System_Web_MarkupProcessor::convertToHtml( $text, $prettyPrint );
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Preview' );
