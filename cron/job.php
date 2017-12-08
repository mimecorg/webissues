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

require_once( dirname( dirname( __FILE__ ) ) . '/system/bootstrap.inc.php' );

class Cron_Job extends System_Core_Application
{
    private $last = null;
    private $current = null;

    private $mailEngine = null;
    private $inboxEngine = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function processCommandLine( $argc, $argv )
    {
        if ( $argc == 2 )
            $this->setSiteName( $argv[ 1 ] );
        else if ( $argc > 2 )
            throw new System_Core_Exception( 'Invalid command line' );
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

            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Previous cron job timed out' ) );
        }

        $this->last = $serverManager->getSetting( 'cron_last' );

        $this->current = time();
        $serverManager->setSetting( 'cron_current', $this->current );

        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->tr( 'Cron job started' ) );

        $divisor = $serverManager->getSetting( 'gc_divisor' );
        if ( $divisor == 0 )
            $this->collectGarbage();

        $email = $serverManager->getSetting( 'email_engine' );
        if ( $email != null )
            $this->sendNotificationEmails();

        $inbox = $serverManager->getSetting( 'inbox_engine' );
        if ( $inbox != null )
            $this->processInboxEmails();
    }

    protected function cleanUp()
    {
        if ( $this->mailEngine != null )
            $this->mailEngine->close();

        if ( $this->inboxEngine != null )
            $this->inboxEngine->close();

        if ( $this->current != null ) {
            $serverManager = new System_Api_ServerManager();
            $serverManager->setSetting( 'cron_last', $this->current );
            $serverManager->setSetting( 'cron_current', null );
        }

        if ( $this->isLoggingEnabled() ) {
            $eventLog = new System_Api_EventLog( $this );
            if ( $this->getFatalError() != null )
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Error, $eventLog->tr( 'Cron job finished with error' ) );
            else if ( $this->current == null )
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Previous cron job is still running' ) );
            else
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->tr( 'Cron job finished' ) );
        }
    }

    private function sendNotificationEmails()
    {
        $this->mailEngine = new System_Mail_Engine();
        $this->mailEngine->loadSettings();

        $sent = 0;

        $userManager = new System_Api_UserManager();
        $alertManager = new System_Api_AlertManager();
        $subscriptionManager = new System_Api_SubscriptionManager();
        $serverManager = new System_Api_ServerManager();

        $includeSummary = false;

        if ( $this->last ) {
            $lastDate = new DateTime( '@' . $this->last );
            $currentDate = new DateTime( '@' . $this->current );
            if ( $lastDate->format( 'YmdH' ) != $currentDate->format( 'YmdH' ) )
                $includeSummary = true;
        } else {
            $includeSummary = true;
        }

        $users = $userManager->getUsersWithEmail();

        foreach ( $users as $user ) {
            $this->impersonateUser( $user );

            $preferencesManager = new System_Api_PreferencesManager();
            $validator = new System_Api_Validator();

            $day = -1;

            $alerts = $alertManager->getAlertsToEmail( $includeSummary );

            foreach ( $alerts as $alert ) {
                if ( $alert[ 'alert_email' ] == System_Const::SummaryNotificationEmail || $alert[ 'alert_email' ] == System_Const::SummaryReportEmail ) {
                    if ( $alert[ 'summary_days' ] == null || $alert[ 'summary_hours' ] == null )
                        continue;

                    $days = $validator->convertToIntArray( $alert[ 'summary_days' ] );
                    $hours = $validator->convertToIntArray( $alert[ 'summary_hours' ] );

                    if ( $day < 0 ) {
                        $locale = new System_Api_Locale();
                        $timezone = new DateTimeZone( $locale->getSetting( 'time_zone' ) );

                        $currentDate = new DateTime( '@' . $this->current );
                        $currentDate->setTimezone( $timezone );

                        $day = $currentDate->format( 'w' );
                        $hour = $currentDate->format( 'G' );
                    }

                    if ( array_search( $day, $days ) === false || array_search( $hour, $hours ) === false )
                        continue;
                }

                $mail = System_Web_Component::createComponent( 'Common_Mail_Notification', null, $alert );

                if ( $mail->prepare() ) {
                    System_Web_Base::setLinkMode( System_Web_Base::MailLinks );

                    $body = $mail->run();
                    $subject = $mail->getView()->getSlot( 'subject' );

                    $this->setReplyToInbox( false );

                    $this->mailEngine->send( $preferencesManager->getPreference( 'email' ), $user[ 'user_name' ], $subject, $body );
                    $sent++;
                }

                $alertManager->updateAlertStamp( $alert );
            }

            $subscriptions = $subscriptionManager->getSubscriptionsToEmail();

            foreach ( $subscriptions as $subscription ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_Subscription', null, $subscription );

                if ( $mail->prepare() ) {
                    System_Web_Base::setLinkMode( System_Web_Base::MailLinks );

                    $body = $mail->run();
                    $subject = $mail->getView()->getSlot( 'subject' );

                    $this->setReplyToInbox( true );

                    $this->mailEngine->send( $preferencesManager->getPreference( 'email' ), $user[ 'user_name' ], $subject, $body );
                    $sent++;
                }

                $subscriptionManager->updateSubscriptionStamp( $subscription );
            }

            $this->undoImpersonation();
        }

        $day = -1;

        $alerts = $alertManager->getPublicAlertsToEmail( $includeSummary );

        foreach ( $alerts as $alert ) {
            if ( $alert[ 'alert_email' ] == System_Const::SummaryNotificationEmail || $alert[ 'alert_email' ] == System_Const::SummaryReportEmail ) {
                if ( $alert[ 'summary_days' ] == null || $alert[ 'summary_hours' ] == null )
                    continue;

                $days = $validator->convertToIntArray( $alert[ 'summary_days' ] );
                $hours = $validator->convertToIntArray( $alert[ 'summary_hours' ] );

                if ( $day < 0 ) {
                    $currentDate = new DateTime( '@' . $this->current );

                    $timezone = $serverManager->getSetting( 'time_zone' );
                    if ( $timezone != null )
                        $currentDate->setTimezone( new DateTimeZone( $timezone ) );
                    else
                        $currentDate->setTimezone( new DateTimeZone( date_default_timezone_get() ) );

                    $day = $currentDate->format( 'w' );
                    $hour = $currentDate->format( 'G' );
                }

                if ( array_search( $day, $days ) === false || array_search( $hour, $hours ) === false )
                    continue;
            }

            $users = $alertManager->getAlertRecipients( $alert );

            foreach ( $users as $user ) {
                $this->impersonateUser( $user );

                $preferencesManager = new System_Api_PreferencesManager();

                $mail = System_Web_Component::createComponent( 'Common_Mail_Notification', null, $alert );

                if ( $mail->prepare() ) {
                    System_Web_Base::setLinkMode( System_Web_Base::MailLinks );

                    $body = $mail->run();
                    $subject = $mail->getView()->getSlot( 'subject' );

                    $this->setReplyToInbox( false );

                    $this->mailEngine->send( $preferencesManager->getPreference( 'email' ), $user[ 'user_name' ], $subject, $body );
                    $sent++;
                }
            }

            $alertManager->updateAlertStamp( $alert );

            $this->undoImpersonation();
        }

        $allowExternal = $serverManager->getSetting( 'inbox_allow_external' );

        if ( $allowExternal == 1 ) {
            $robotUserId = $serverManager->getSetting( 'inbox_robot' );
            $robotUser = $userManager->getUser( $robotUserId );
            $anonymousAccess = $serverManager->getSetting( 'anonymous_access' );

            $this->impersonateExternalUser( $robotUser );

            $subscriptions = $subscriptionManager->getExternalSubscriptionsToEmail();

            foreach ( $subscriptions as $subscription ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_Subscription', null, $subscription );

                if ( $mail->prepare() ) {
                    if ( $anonymousAccess == 1 && $subscription[ 'is_public' ] == 1 )
                        System_Web_Base::setLinkMode( System_Web_Base::MailLinks );
                    else
                        System_Web_Base::setLinkMode( System_Web_Base::NoInternalLinks );

                    $body = $mail->run();
                    $subject = $mail->getView()->getSlot( 'subject' );

                    $this->setReplyToInbox( true );

                    $this->mailEngine->send( $subscription[ 'user_email' ], null, $subject, $body );
                    $sent++;
                }

                $subscriptionManager->updateSubscriptionStamp( $subscription );
            }

            $this->undoImpersonation();
        }

        $selfRegister = $serverManager->getSetting( 'self_register' );
        $autoApprove = $serverManager->getSetting( 'register_auto_approve' );
        $notifyEmail = $serverManager->getSetting( 'register_notify_email' );

        if ( $selfRegister == 1 && $autoApprove != 1 && $notifyEmail != null ) {
            $registrationManager = new System_Api_RegistrationManager();

            $page = $registrationManager->getRequestsToEmail();

            if ( !empty( $page ) ) {
                $mail = System_Web_Component::createComponent( 'Common_Mail_RegisterNotification', null, $page );

                System_Web_Base::setLinkMode( System_Web_Base::MailLinks );

                $body = $mail->run();
                $subject = $mail->getView()->getSlot( 'subject' );

                $this->setReplyToInbox( false );

                $this->mailEngine->send( $notifyEmail, null, $subject, $body );
                $sent++;

                $registrationManager->setRequestsMailed();
            }
        }

        if ( $sent > 0 ) {
            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->tr( 'Sent %1 notification emails', null, $sent ) );
        }
    }

    public function processInboxEmails()
    {
        $this->inboxEngine = new System_Mail_InboxEngine();
        $this->inboxEngine->loadSettings();

        $received = 0;

        $messages = $this->inboxEngine->getMessages();

        if ( empty( $messages ) )
            return;

        $serverManager = new System_Api_ServerManager();
        $userManager = new System_Api_UserManager();
        $projectManager = new System_Api_ProjectManager();
        $issueManager = new System_Api_IssueManager();
        $typeManager = new System_Api_TypeManager();
        $subscriptionManager = new System_Api_SubscriptionManager();
        $parser = new System_Api_Parser();
        $eventLog = new System_Api_EventLog( $this );

        $inboxEmail = $serverManager->getSetting( 'inbox_email' );

        $allowExternal = $serverManager->getSetting( 'inbox_allow_external' );

        if ( $allowExternal == 1 ) {
            $robotUserId = $serverManager->getSetting( 'inbox_robot' );
            $robotUser = $userManager->getUser( $robotUserId );
        }

        $mapFolder = $serverManager->getSetting( 'inbox_map_folder' );

        if ( $mapFolder == 1 ) {
            $parts = explode( '@', $inboxEmail );
            $mapPattern = '/^' . preg_quote( $parts[ 0 ] ) . '[+-](\w+)-(\w+)@' . preg_quote( $parts[ 1 ] ) . '$/ui';

            $allFolders = $projectManager->getFoldersMap();
        }

        $defaultFolderId = $serverManager->getSetting( 'inbox_default_folder' );

        $leaveMessages = $serverManager->getSetting( 'inbox_leave_messages' );
        $respond = $serverManager->getSetting( 'inbox_respond' );
        $subscribe = $serverManager->getSetting( 'inbox_subscribe' );

        $anonymousAccess = $serverManager->getSetting( 'anonymous_access' );

        if ( $this->mailEngine != null )
            $serverEmail = $serverManager->getSetting( 'email_from' );

        foreach ( $messages as $msgno ) {
            $processed = false;
            $ignore = false;

            $headers = $this->inboxEngine->getHeaders( $msgno );

            $fromEmail = $headers[ 'from' ][ 'email' ];

            if ( $this->mailEngine != null && mb_strtoupper( $fromEmail ) == mb_strtoupper( $serverEmail ) ) {
                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from "%1"', null, $fromEmail ) );
                $ignore = true;
            }

            if ( !$ignore ) {
                try {
                    $user = $userManager->getUserByEmail( $fromEmail );
                } catch ( System_Api_Error $e ) {
                    $user = null;
                }

                if ( $user == null && $allowExternal != 1 ) {
                    $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from unknown address "%1"', null, $fromEmail ) );
                    $ignore = true;
                }
            }

            if ( !$ignore ) {
                if ( $user != null )
                    $this->impersonateUser( $user );
                else
                    $this->impersonateExternalUser( $robotUser );

                $folder = null;
                $issue = null;

                if ( preg_match( '/\[#(\d+)\]/', $headers[ 'subject' ], $matches ) ) {
                    $issueId = $matches[ 1 ];
                    try {
                        $issue = $issueManager->getIssue( $issueId );
                    } catch ( System_Api_Error $e ) {
                        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from "%1" because issue %2 is inaccessible', null, $fromEmail, '#' . $issueId ) );
                    }
                } else {
                    $folderId = null;

                    if ( $mapFolder == 1 ) {
                        $toEmail = $this->matchRecipient( $mapPattern, $headers, $matches );

                        if ( $toEmail != null ) {
                            $matching = array();

                            foreach ( $allFolders as $row ) {
                                if ( $this->matchPart( $matches[ 1 ], $row[ 'project_name' ] ) && $this->matchPart( $matches[ 2 ], $row[ 'folder_name' ] ) )
                                    $matching[] = $row[ 'folder_id' ];
                            }

                            if ( count( $matching ) == 1 ) {
                                $folderId = $matching[ 0 ];
                            } else if ( count( $matching ) > 1 ) {
                                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ambiguous folder for inbox email address "%1"', null, $toEmail ) );
                            } else {
                                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'No matching folder for inbox email address "%1"', null, $toEmail ) );
                            }
                        }
                    }

                    if ( $folderId != null ) {
                        try {
                            $folder = $projectManager->getFolder( $folderId );
                        } catch ( System_Api_Error $e ) {
                            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from "%1" to "%2" because folder is inaccessible', null, $fromEmail, $toEmail ) );
                        }
                    } else if ( $defaultFolderId != null ) {
                        try {
                            $folder = $projectManager->getFolder( $defaultFolderId );
                        } catch ( System_Api_Error $e ) {
                            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from "%1" because default folder is inaccessible', null, $fromEmail ) );
                        }
                    } else {
                        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Ignored inbox email from "%1" because folder cannot be mapped', null, $fromEmail ) );
                    }
                }

                $issueId = null;

                if ( $issue != null || $folder != null ) {
                    $parts = $this->inboxEngine->getStructure( $msgno );
                    
                    try {
                        $text = $this->formatHeaders( $headers );

                        foreach ( $parts as $part ) {
                            if ( $part[ 'type' ] == 'plain' ) {
                                $text .= $this->inboxEngine->convertToUtf8( $part );
                                break;
                            }
                        }

                        // normalize illegal line breaks like CR or CRCRLF
                        $text = preg_replace( '/\r(?:\r?\n)?/', "\n", $text );

                        $text = $parser->normalizeString( $text, null, System_Api_Parser::MultiLine );
                        $text = preg_replace( '/\n(?:[ \t]*\n)+/', "\n\n", $text );

                        // two separators in the same line or in two consecutive lines indicate where the text should be cut off
                        $separator = str_repeat( '-', 11 );
                        $text = preg_replace( '/[ \n\t]*\n[^-\n]*' . $separator . '[^-\n]*[ \n\t][^-\n]*' . $separator . '(?!-).*/s', '', $text );

                        $maxLength = $serverManager->getSetting( 'comment_max_length' );
                        if ( mb_strlen( $text ) > $maxLength )
                            $text = mb_substr( $text, 0, $maxLength - 3 ) . '...';

                        $subscriptionId = null;

                        if ( $issue == null ) {
                            $name = $headers[ 'subject' ];
                            $name = $parser->normalizeString( $name, null, System_Api_Parser::AllowEmpty );
                            if ( mb_strlen( $name ) > System_Const::ValueMaxLength )
                                $name = mb_substr( $name, 0, System_Const::ValueMaxLength - 3 ) . '...';
                            if ( $name == '' )
                                $name = $this->tr( 'No subject' );

                            $values = $typeManager->getDefaultAttributeValuesForFolder( $folder );

                            $issueId = $issueManager->addIssue( $folder, $name, $values );
                            $issue = $issueManager->getIssue( $issueId );

                            $stampId = $issueManager->addDescription( $issue, $text, System_Const::PlainText );
                            $issue[ 'stamp_id' ] = $stampId;

                            $emailId = '#' . $issueId;

                            if ( $subscribe == 1 && $this->mailEngine != null ) {
                                if ( $user != null )
                                    $subscriptionManager->addSubscription( $issue );
                                else
                                    $subscriptionId = $subscriptionManager->addExternalSubscription( $issue, $fromEmail );
                            }
                        } else {
                            if ( $subscribe == 1 && $this->mailEngine != null && $user == null ) {
                                $subscription = $subscriptionManager->findExternalSubscription( $issue, $fromEmail );
                                if ( $subscription != null )
                                    $subscriptionId = $subscription[ 'subscription_id' ];
                            }

                            $commentId = $issueManager->addComment( $issue, $text, System_Const::PlainText );
                            $emailId = '#' . $commentId;

                            if ( $subscriptionId != null )
                                $subscriptionManager->setSubscriptionForChange( $commentId, $subscriptionId );
                        }

                        $received++;

                        foreach ( $parts as $part ) {
                            if ( $part[ 'type' ] == 'html' || $part[ 'type' ] == 'attachment' && $part[ 'name' ] != null ) {
                                $size = strlen( $part[ 'body' ] );

                                if ( $size > $serverManager->getSetting( 'file_max_size' ) ) {
                                    $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->tr( 'Attachment for message %1 from "%2" exceeded maximum size', null, $emailId, $fromEmail ) );
                                    continue;
                                }

                                if ( $part[ 'type' ] == 'html' ) {
                                    $name = 'message.html';
                                    $description = $this->tr( 'HTML message for email %1', null, $emailId );
                                } else {
                                    $name = $part[ 'name' ];
                                    $parser->checkString( $name, System_Const::FileNameMaxLength );
                                    $description = $this->tr( 'Attachment for email %1', null, $emailId );
                                }

                                $attachment = new System_Core_Attachment( $part[ 'body' ], $size, $name );
                                $fileId = $issueManager->addFile( $issue, $attachment, $name, $description );

                                if ( $subscriptionId != null )
                                    $subscriptionManager->setSubscriptionForChange( $fileId, $subscriptionId );
                            }
                        }
                    } catch ( System_Api_Error $e ) {
                        $eventLog->addErrorEvent( $e );
                    }

                    $processed = true;
                }

                if ( $respond == 1 && $this->mailEngine != null && $issueId != null ) {
                    $mail = System_Web_Component::createComponent( 'Common_Mail_IssueCreated', null, $issue );

                    if ( $user != null || $anonymousAccess == 1 && $issue[ 'is_public' ] == 1 )
                        System_Web_Base::setLinkMode( System_Web_Base::MailLinks );
                    else
                        System_Web_Base::setLinkMode( System_Web_Base::NoInternalLinks );

                    $body = $mail->run();
                    $subject = $mail->getView()->getSlot( 'subject' );

                    $this->setReplyToInbox( true );

                    $this->mailEngine->send( $fromEmail, $user != null ? $user[ 'user_name' ] : null, $subject, $body );
                }

                $this->undoImpersonation();
            }

            if ( $leaveMessages != 1 )
                $this->inboxEngine->markAsDeleted( $msgno );
            else if ( !$processed )
                $this->inboxEngine->markAsProcessed( $msgno );
        }

        if ( $received > 0 )
            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->tr( 'Processed %1 inbox emails', null, $received ) );
    }

    private function matchRecipient( $mapPattern, $headers, &$matches )
    {
        foreach ( $headers[ 'to' ] as $recipient ) {
            if ( preg_match( $mapPattern, $recipient[ 'email' ], $matches ) )
                return $recipient[ 'email' ];
        }

        foreach ( $headers[ 'cc' ] as $recipient ) {
            if ( preg_match( $mapPattern, $recipient[ 'email' ], $matches ) )
                return $recipient[ 'email' ];
        }

        return null;
    }

    private function matchPart( $part, $name )
    {
        $name = preg_replace( '/\W+/ui', '', $name );

        if ( $name != '' && mb_stripos( $name, $part ) !== false )
            return true;

        return false;
    }

    private function formatHeaders( $headers )
    {
        $text = $this->tr( 'From:' ) . ' ' . $this->formatAddress( $headers[ 'from' ] ) . "\n";

        if ( !empty( $headers[ 'to' ] ) ) {
            $to = array();
            foreach ( $headers[ 'to' ] as $addr )
                $to[] = $this->formatAddress( $addr );
            $text .= $this->tr( 'To:' ) . ' ' . implode( '; ', $to ) . "\n";
        }

        if ( !empty( $headers[ 'cc' ] ) ) {
            $cc = array();
            foreach ( $headers[ 'cc' ] as $addr )
                $cc[] = $this->formatAddress( $addr );
            $text .= $this->tr( 'CC:' ) . ' ' . implode( '; ', $cc ) . "\n";
        }

        $text .= $this->tr( 'Subject:' ) . ' ' . $headers[ 'subject' ] . "\n\n";

        return $text;
    }

    private function formatAddress( $addr )
    {
        $text = '';
        if ( isset( $addr[ 'name' ] ) )
            $text = $addr[ 'name' ] . ' ';
        $text .= '<' . $addr[ 'email' ] . '>';
        return $text;
    }

    private function impersonateUser( $user )
    {
        $principal = new System_Api_Principal( $user );
        System_Api_Principal::setCurrent( $principal );

        $locale = new System_Api_Locale();
        $this->translator->setLanguage( System_Core_Translator::UserLanguage, $locale->getSetting( 'language' ) );
    }

    private function impersonateExternalUser( $robotUser )
    {
        $robotPrincipal = new System_Api_Principal( $robotUser );
        System_Api_Principal::setCurrent( $robotPrincipal );

        $this->translator->setLanguage( System_Core_Translator::UserLanguage, null );
    }

    private function undoImpersonation()
    {
        System_Api_Principal::setCurrent( null );
    }

    private function setReplyToInbox( $enabled )
    {
        $serverManager = new System_Api_ServerManager();
        $inbox = $serverManager->getSetting( 'inbox_engine' );

        if ( $inbox != null && $enabled ) {
            $inboxEmail = $serverManager->getSetting( 'inbox_email' );

            $this->mailEngine->setReplyTo( $inboxEmail, null );
        } else {
            $server = $serverManager->getServer();
            $serverEmail = $serverManager->getSetting( 'email_from' );

            $this->mailEngine->setReplyTo( $serverEmail, $server[ 'server_name' ] );
        }
    }

    private function tr( $source, $comment = null )
    {
        $args = func_get_args();
        return $this->translator->translate( System_Core_Translator::SystemLanguage, get_class( $this ), $args );
    }
}

System_Bootstrap::run( 'Cron_Job' );
