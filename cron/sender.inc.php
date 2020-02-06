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

class Cron_Sender extends System_Web_Base
{
    private $mailEngine = null;

    private $count = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function run( $currentDate, $lastDate )
    {
        $this->mailEngine = new System_Mail_Engine();
        $this->mailEngine->loadSettings();

        $flags = 0;

        if ( $lastDate == null || $lastDate->format( 'YmdH' ) != $currentDate->format( 'YmdH' ) ) {
            $serverManager = new System_Api_ServerManager();

            if ( $serverManager->getSetting( 'report_hour' ) == $currentDate->format( 'G' ) ) {
                $flags |= System_Api_AlertManager::WithDaily;

                if ( $serverManager->getSetting( 'report_day' ) == $currentDate->format( 'w' ) )
                    $flags |= System_Api_AlertManager::WithWeekly;
            }
        }

        $this->sendPersonalNotifications( $flags );
        $this->sendPublicNotifications( $flags );

        $this->sendRegistrationNotifications();
    }

    public function addEvent()
    {
        if ( $this->count > 0 ) {
            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->t( 'log.NotificationsSent', array( $this->count ) ) );
        }
    }

    public function cleanUp()
    {
        if ( $this->mailEngine != null )
            $this->mailEngine->close();
    }

    private function sendPersonalNotifications( $flags )
    {
        System_Web_Base::setLinkMode( System_Web_Base::AutoLinks );

        $job = Cron_Job::getInstance();
        $userManager = new System_Api_UserManager();
        $alertManager = new System_Api_AlertManager();
        $subscriptionManager = new System_Api_SubscriptionManager();

        $users = $userManager->getUsersWithEmail();

        foreach ( $users as $user ) {
            $job->impersonateUser( $user );

            $alerts = $alertManager->getAlertsToEmail( $flags );

            foreach ( $alerts as $alert ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_Notification', null, $alert );

                if ( $mail->prepare() )
                    $this->sendMail( $mail, $user[ 'user_email' ], $user[ 'user_name' ], null );

                $alertManager->updateAlertStamp( $alert );
            }

            $subscriptions = $subscriptionManager->getSubscriptionsToEmail();

            foreach ( $subscriptions as $subscription ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_Subscription', null, $subscription );

                if ( $mail->prepare() )
                    $this->sendMail( $mail, $user[ 'user_email' ], $user[ 'user_name' ], $subscription[ 'inbox_email' ] );

                $subscriptionManager->updateSubscriptionStamp( $subscription );
            }

            $job->undoImpersonation();
        }
    }

    private function sendPublicNotifications( $flags )
    {
        System_Web_Base::setLinkMode( System_Web_Base::AutoLinks );

        $job = Cron_Job::getInstance();
        $alertManager = new System_Api_AlertManager();

        $alerts = $alertManager->getPublicAlertsToEmail( $flags );

        foreach ( $alerts as $alert ) {
            $users = $alertManager->getAlertRecipients( $alert );

            foreach ( $users as $user ) {
                $job->impersonateUser( $user );

                $mail = System_Web_Component::createComponent( 'Common_Mail_Notification', null, $alert );

                if ( $mail->prepare() )
                    $this->sendMail( $mail, $user[ 'user_email' ], $user[ 'user_name' ], null );
            }

            $alertManager->updateAlertStamp( $alert );

            $job->undoImpersonation();
        }
    }

    public function sendExternalNotifications( $inbox )
    {
        $serverManager = new System_Api_ServerManager();
        $subscriptionManager = new System_Api_SubscriptionManager();

        $anonymousAccess = $serverManager->getSetting( 'anonymous_access' );

        $subscriptions = $subscriptionManager->getExternalSubscriptionsToEmail( $inbox );

        foreach ( $subscriptions as $subscription ) {
            $mail = System_Web_Component::createComponent( 'Common_Mail_Subscription', null, $subscription );

            if ( $anonymousAccess == 1 && $subscription[ 'is_public' ] == 1 )
                System_Web_Base::setLinkMode( System_Web_Base::AutoLinks );
            else
                System_Web_Base::setLinkMode( System_Web_Base::NoInternalLinks );

            if ( $mail->prepare() )
                $this->sendMail( $mail, $subscription[ 'user_email' ], null, $inbox[ 'inbox_email' ] );

            $subscriptionManager->updateSubscriptionStamp( $subscription );
        }
    }

    private function sendRegistrationNotifications()
    {
        System_Web_Base::setLinkMode( System_Web_Base::AutoLinks );

        $serverManager = new System_Api_ServerManager();

        $selfRegister = $serverManager->getSetting( 'self_register' );
        $autoApprove = $serverManager->getSetting( 'register_auto_approve' );
        $notifyEmail = $serverManager->getSetting( 'register_notify_email' );

        if ( $selfRegister == 1 && $autoApprove != 1 && $notifyEmail != null ) {
            $registrationManager = new System_Api_RegistrationManager();

            $page = $registrationManager->getRequestsToEmail();

            if ( !empty( $page ) ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_RegisterNotification', null, $page );

                $this->sendMail( $mail, $notifyEmail, null, null );

                $registrationManager->setRequestsMailed();
            }
        }
    }

    public function sendMail( $mail, $to, $name, $replyTo )
    {
        $body = $mail->run();
        $subject = $mail->getView()->getSlot( 'subject' );

        $this->setReplyToInbox( $replyTo );

        try {
            $this->mailEngine->send( $to, $name, $subject, $body );
            $this->count++;
        } catch ( Exception $e ) {
            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addErrorEvent( $e );
        }
    }

    private function setReplyToInbox( $inboxEmail )
    {
        if ( $inboxEmail != null ) {
            $this->mailEngine->setReplyTo( $inboxEmail, null );
        } else {
            $serverManager = new System_Api_ServerManager();
            $server = $serverManager->getServer();
            $serverEmail = $serverManager->getSetting( 'email_from' );

            $this->mailEngine->setReplyTo( $serverEmail, $server[ 'server_name' ] );
        }
    }
}
