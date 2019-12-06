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

class Cron_Update extends System_Web_Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function run( $current )
    {
        $serverManager = new System_Api_ServerManager();
        $last = $serverManager->getSetting( 'update_last' );

        $currentDate = new DateTime( '@' . $current );

        if ( $last != null )
            $lastDate = new DateTime( '@' . $last );
        else
            $lastDate = null;

        if ( $lastDate == null || $lastDate->format( 'Ymd' ) != $currentDate->format( 'Ymd' ) ) {
            $this->checkUpdate();
            $serverManager->setSetting( 'update_last', $current );
        }
    }

    private function checkUpdate()
    {
        $url = 'http://update.mimec.org/service.php?app=webissues';

        $response = @file_get_contents( $url );

        if ( $response === false )
            return;

        $document = new DOMDocument();

        if ( !$document->loadXML( $response ) )
            return;

        $serverManager = new System_Api_ServerManager();

        $nodes = $document->documentElement->getElementsByTagName( 'version' );

        if ( $nodes->length > 0 ) {
            $node = $nodes->item( 0 );
            $version = $node->getAttribute( 'id' );

            $nodes = $node->getElementsByTagName( 'notesUrl' );
            if ( $nodes->length > 0 )
                $notesUrl = $nodes->item( 0 )->textContent;
            else
                $notesUrl = '';

            $nodes = $node->getElementsByTagName( 'downloadUrl' );
            if ( $nodes->length > 0 )
                $downloadUrl = $nodes->item( 0 )->textContent;
            else
                $downloadUrl = '';

            $serverManager->setSetting( 'update_version', $version );
            $serverManager->setSetting( 'update_notes_url', $notesUrl );
            $serverManager->setSetting( 'update_download_url', $downloadUrl );

            if ( version_compare( $version, WI_VERSION, '>' ) ) {
                $eventLog = new System_Api_EventLog( $this );
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->t( 'log.NewVersionAvailable', array( $version ) ) );
            }
        } else {
            $serverManager->setSetting( 'update_version', '' );
            $serverManager->setSetting( 'update_notes_url', '' );
            $serverManager->setSetting( 'update_download_url', '' );
        }
    }
}
