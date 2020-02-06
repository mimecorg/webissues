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

require_once( '../system/bootstrap.inc.php' );

class Cron_Job extends System_Core_Application
{
    private $last = null;
    private $current = null;

    private $sender = null;
    private $receiver = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        set_time_limit( 0 );

        $serverManager = new System_Api_ServerManager();
        $eventLog = new System_Api_EventLog( $this );

        $current = $serverManager->getSetting( 'cron_current' );
        if ( $current != null ) {
            if ( time() - $current < 10800 )
                return;

            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.CronTimeout' ) );
        }

        $this->current = time();
        $serverManager->setSetting( 'cron_current', $this->current );

        $this->last = $serverManager->getSetting( 'cron_last' );

        $currentDate = $this->createDateTime( $this->current );
        $lastDate = $this->createDateTime( $this->last );

        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->t( 'log.CronStarted' ) );

        $this->collectGarbage();

        $email = $serverManager->getSetting( 'email_engine' );

        if ( $email != null ) {
            $this->sender = new Cron_Sender();
            $this->sender->run( $currentDate, $lastDate );
        }

        $inboxManager = new System_Api_InboxManager();
        $inboxes = $inboxManager->getInboxes();

        if ( !empty( $inboxes ) ) {
            $this->receiver = new Cron_Receiver();
            foreach ( $inboxes as $inbox )
                $this->receiver->run( $inbox, $this->sender );
        }

        if ( $this->sender != null )
            $this->sender->addEvent();

        if ( ini_get( 'allow_url_fopen' ) ) {
            $update = new Cron_Update();
            $update->run( $currentDate );
        }
    }

    protected function cleanUp()
    {
        if ( $this->sender != null )
            $this->sender->cleanUp();

        if ( $this->receiver != null )
            $this->receiver->cleanUp();

        if ( $this->current != null ) {
            $serverManager = new System_Api_ServerManager();
            $serverManager->setSetting( 'cron_last', $this->current );
            $serverManager->setSetting( 'cron_current', null );
        }

        if ( $this->isLoggingEnabled() ) {
            $eventLog = new System_Api_EventLog( $this );
            if ( $this->getFatalError() != null )
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Error, $eventLog->t( 'log.CronError' ) );
            else if ( $this->current == null )
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.CronRunning' ) );
            else
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->t( 'log.CronFinished' ) );
        }
    }

    public function impersonateUser( $user )
    {
        $principal = new System_Api_Principal( $user );
        System_Api_Principal::setCurrent( $principal );

        $this->translator->setLanguage( System_Core_Translator::UserLanguage, $principal->getLanguage() );
    }

    public function impersonateExternalUser( $robotUser )
    {
        $robotPrincipal = new System_Api_Principal( $robotUser );
        System_Api_Principal::setCurrent( $robotPrincipal );

        $this->translator->setLanguage( System_Core_Translator::UserLanguage, null );
    }

    public function undoImpersonation()
    {
        System_Api_Principal::setCurrent( null );

        $this->translator->setLanguage( System_Core_Translator::UserLanguage, null );
    }

    public function createDateTime( $stamp )
    {
        if ( $stamp != null ) {
            $dateTime = new DateTime( '@' . $stamp );
            $dateTime->setTimezone( $this->getServerTimeZone() );
            return $dateTime;
        } else {
            return null;
        }
    }

    private function getServerTimeZone()
    {
        $serverManager = new System_Api_ServerManager();
        $timeZone = $serverManager->getSetting( 'time_zone' );

        if ( $timeZone != null )
            return new DateTimeZone( $timeZone );
        else
            return new DateTimeZone( date_default_timezone_get() );
    }
}

System_Bootstrap::run( 'Cron_Job' );
