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

class Common_Mail_Register extends System_Web_Component
{
    private $register;

    protected function __construct( $register )
    {
        parent::__construct();

        $this->register = $register;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Layout' );
        $this->view->setSlot( 'subject', $this->tr( 'WebIssues Server email verification' ) );

        $this->login = $this->register[ 'user_login' ];
        $this->userName = $this->register[ 'user_name' ];
        $this->email = $this->register[ 'user_email' ];

        // Note: use WI_BASE_URL here because this email is always sent from the registration form and the link
        // is valid even if the server URL is not configured.
        $this->activationUrl = $this->appendQueryString( WI_BASE_URL . '/register.php', array( 'key' => $this->register[ 'request_key' ] ) );
    }
}
