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

require_once( WI_ROOT_DIR . '/vendor/autoload.php' );

/**
* OAuth token provider for PHPMailer.
*/
class System_Mail_OAuthTokenProvider implements PHPMailer\PHPMailer\OAuthTokenProvider
{
    private $user;
    private $token;

    public function __construct( $user, $token )
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function getOauth64()
    {
        return base64_encode( 'user=' .  $this->user . "\001auth=Bearer " . $this->token . "\001\001" );
    }
}
