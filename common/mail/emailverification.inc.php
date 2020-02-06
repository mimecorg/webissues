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

class Common_Mail_EmailVerification extends System_Web_Component
{
    private $data;

    protected function __construct( $data )
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Template' );
        $this->view->setSlot( 'subject', $this->t( 'subject.EmailVerification' ) );
        $this->view->setSlot( 'user_name', $this->data[ 'user_name' ] );

        $this->activationUrl = $this->appendQueryString( WI_BASE_URL . '/users/register.php', array( 'key' => $this->data[ 'request_key' ] ) );
    }
}
