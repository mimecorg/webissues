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

class Common_Mail_IssueCreated extends System_Web_Component
{
    private $issue;

    protected function __construct( $issue )
    {
        parent::__construct();

        $this->issue = $issue;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Layout' );
        $this->view->setSlot( 'subject', '[#' . $this->issue[ 'issue_id' ] . '] ' . $this->issue[ 'issue_name' ] );

        $this->issueId = $this->issue[ 'issue_id' ];
        $this->issueName = $this->issue[ 'issue_name' ];

        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'inbox_subscribe' ) == 1 )
            $this->subscribe = true;

        if ( self::getLinkMode() != self::NoInternalLinks )
            $this->hasLinks = true;
    }
}
