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

class Cron_Receiver extends System_Web_Base
{
    private $inboxEngine = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function run( $inbox, $sender )
    {
        $job = Cron_Job::getInstance();
        $userManager = new System_Api_UserManager();

        $inboxEmail = $inbox[ 'inbox_email' ];
        $allowExternal = $inbox[ 'inbox_allow_external' ];

        if ( $allowExternal == 1 ) {
            $robotUserId = $inbox[ 'inbox_robot' ];
            $robotUser = $userManager->getUser( $robotUserId );

            if ( $sender != null ) {
                $job->impersonateExternalUser( $robotUser );

                $sender->sendExternalNotifications( $inbox );

                $job->undoImpersonation();
            }
        }

        $eventLog = new System_Api_EventLog( $this );

        try {
            $this->inboxEngine = new System_Mail_InboxEngine();
            $this->inboxEngine->setSettings( $inbox );

            $messages = $this->inboxEngine->getMessages();
        } catch ( Exception $e ) {
            $eventLog->addErrorEvent( $e );
        }

        if ( empty( $messages ) )
            return;

        $received = 0;

        $serverManager = new System_Api_ServerManager();
        $projectManager = new System_Api_ProjectManager();
        $issueManager = new System_Api_IssueManager();
        $typeManager = new System_Api_TypeManager();
        $subscriptionManager = new System_Api_SubscriptionManager();
        $parser = new System_Api_Parser();

        $mapFolder = $inbox[ 'inbox_map_folder' ];
        $defaultFolderId = $inbox[ 'inbox_default_folder' ];

        if ( $mapFolder == 1 ) {
            $parts = explode( '@', $inboxEmail );
            $mapPattern = '/^' . preg_quote( $parts[ 0 ] ) . '[+-](\w+)-(\w+)@' . preg_quote( $parts[ 1 ] ) . '$/ui';

            $allFolders = $projectManager->getFoldersMap();
        }

        $leaveMessages = $inbox[ 'inbox_leave_messages' ];
        $respond = $inbox[ 'inbox_respond' ];
        $subscribe = $inbox[ 'inbox_subscribe' ];

        $anonymousAccess = $serverManager->getSetting( 'anonymous_access' );

        if ( $sender != null )
            $serverEmail = $serverManager->getSetting( 'email_from' );

        foreach ( $messages as $msgno ) {
            $processed = false;
            $ignore = false;

            try {
                $headers = $this->inboxEngine->getHeaders( $msgno );
            } catch ( Exception $e ) {
                $eventLog->addErrorEvent( $e );
                $ignore = true;
            }

            if ( !$ignore ) {
                $fromEmail = $headers[ 'from' ][ 'email' ];

                if ( $sender != null && mb_strtoupper( $fromEmail ) == mb_strtoupper( $serverEmail ) ) {
                    $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailIgnored', array( $fromEmail ) ) );
                    $ignore = true;
                }
            }

            if ( !$ignore ) {
                try {
                    $user = $userManager->getUserByEmail( $fromEmail );
                } catch ( System_Api_Error $e ) {
                    $user = null;
                }

                if ( $user == null && $allowExternal != 1 ) {
                    $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailUnknownAddress', array( $fromEmail ) ) );
                    $ignore = true;
                }
            }

            if ( !$ignore ) {
                if ( $user != null )
                    $job->impersonateUser( $user );
                else
                    $job->impersonateExternalUser( $robotUser );

                $folder = null;
                $issue = null;

                if ( preg_match( '/\[#(\d+)\]/', $headers[ 'subject' ], $matches ) ) {
                    $issueId = $matches[ 1 ];
                    try {
                        $issue = $issueManager->getIssue( $issueId );
                    } catch ( System_Api_Error $e ) {
                        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailIssueIncaccessible', array( $fromEmail, '#' . $issueId ) ) );
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
                                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailAmbiguousFolder', array( $toEmail ) ) );
                            } else {
                                $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailNoMatchigFolder', array( $toEmail ) ) );
                            }
                        }
                    }

                    if ( $folderId != null ) {
                        try {
                            $folder = $projectManager->getFolder( $folderId );
                        } catch ( System_Api_Error $e ) {
                            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailFolderInaccessible', array( $fromEmail, $toEmail ) ) );
                        }
                    } else if ( $defaultFolderId != null ) {
                        try {
                            $folder = $projectManager->getFolder( $defaultFolderId );
                        } catch ( System_Api_Error $e ) {
                            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailDefaultFolderInaccessible', array( $fromEmail ) ) );
                        }
                    } else {
                        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailCannotMapFolder', array( $fromEmail ) ) );
                    }
                }

                $issueId = null;

                if ( $issue != null || $folder != null ) {
                    try {
                        $parts = $this->inboxEngine->getStructure( $msgno );

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
                                $name = $this->t( 'text.NoSubject' );

                            $values = $typeManager->getDefaultAttributeValuesForFolder( $folder );

                            $issueId = $issueManager->addIssue( $folder, $name, $values );
                            $issue = $issueManager->getIssue( $issueId );

                            $stampId = $issueManager->addDescription( $issue, $text, System_Const::PlainText );
                            $issue[ 'stamp_id' ] = $stampId;

                            $emailId = '#' . $issueId;

                            if ( $subscribe == 1 && $sender != null ) {
                                if ( $user != null )
                                    $subscriptionManager->addSubscription( $issue, $inbox );
                                else
                                    $subscriptionId = $subscriptionManager->addExternalSubscription( $issue, $fromEmail, $inbox );
                            }
                        } else {
                            if ( $subscribe == 1 && $sender != null && $user == null ) {
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
                                    $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailAttachmentTooLarge', array( $emailId, $fromEmail ) ) );
                                    continue;
                                }

                                if ( $part[ 'type' ] == 'html' ) {
                                    $name = 'message.html';
                                    $description = $this->t( 'text.HTMLMessageForEmail', array( $emailId ) );
                                } else {
                                    $name = $part[ 'name' ];
                                    try {
                                        $parser->checkString( $name, System_Const::FileNameMaxLength );
                                        $parser->checkFileName( $name );
                                    } catch ( System_Api_Error $e ) {
                                        $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Warning, $eventLog->t( 'log.EmailAttachmentInvalidName', array( $emailId, $fromEmail ) ) );
                                        continue;
                                    }
                                    $description = $this->t( 'text.AttachmentForEmail', array( $emailId ) );
                                }

                                $attachment = new System_Core_Attachment( $part[ 'body' ], $size, $name );
                                $fileId = $issueManager->addFile( $issue, $attachment, $name, $description );

                                if ( $subscriptionId != null )
                                    $subscriptionManager->setSubscriptionForChange( $fileId, $subscriptionId );
                            }
                        }
                    } catch ( Exception $e ) {
                        $eventLog->addErrorEvent( $e );
                    }

                    $processed = true;
                }

                if ( $respond == 1 && $sender != null && $issueId != null ) {
                    $data[ 'issue' ] = $issue;
                    $data[ 'user_name' ] = $user != null ? $user[ 'user_name' ] : null;
                    $data[ 'subscribe' ] = $subscribe;

                    $mail = System_Web_Component::createComponent( 'Common_Mail_IssueCreated', null, $data );

                    if ( $user != null || $anonymousAccess == 1 && $issue[ 'is_public' ] == 1 )
                        System_Web_Base::setLinkMode( System_Web_Base::AutoLinks );
                    else
                        System_Web_Base::setLinkMode( System_Web_Base::NoInternalLinks );

                    $sender->sendMail( $mail, $fromEmail, $user != null ? $user[ 'user_name' ] : null, $inboxEmail );
                }

                $job->undoImpersonation();
            }

            try {
                if ( $leaveMessages != 1 )
                    $this->inboxEngine->markAsDeleted( $msgno );
                else if ( !$processed )
                    $this->inboxEngine->markAsProcessed( $msgno );
            } catch ( Exception $e ) {
                $eventLog->addErrorEvent( $e );
            }
        }

        try {
            $this->inboxEngine->close();
        } catch ( Exception $e ) {
            $eventLog->addErrorEvent( $e );
        }

        if ( $received > 0 )
            $eventLog->addEvent( System_Api_EventLog::Cron, System_Api_EventLog::Information, $eventLog->t( 'log.InboxEmailsProcessed', array( $received ) ) );
    }

    public function cleanUp()
    {
        if ( $this->inboxEngine != null )
            $this->inboxEngine->close();
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
        $text = $this->t( 'label.From' ) . ' ' . $this->formatAddress( $headers[ 'from' ] ) . "\n";

        if ( !empty( $headers[ 'to' ] ) ) {
            $to = array();
            foreach ( $headers[ 'to' ] as $addr )
                $to[] = $this->formatAddress( $addr );
            $text .= $this->t( 'label.To' ) . ' ' . implode( '; ', $to ) . "\n";
        }

        if ( !empty( $headers[ 'cc' ] ) ) {
            $cc = array();
            foreach ( $headers[ 'cc' ] as $addr )
                $cc[] = $this->formatAddress( $addr );
            $text .= $this->t( 'label.CC' ) . ' ' . implode( '; ', $cc ) . "\n";
        }

        $text .= $this->t( 'label.Subject' ) . ' ' . $headers[ 'subject' ] . "\n\n";

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
}
