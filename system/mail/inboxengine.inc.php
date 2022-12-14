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
* Engine for receiving email messages using the IMAP extension.
*/
class System_Mail_InboxEngine
{
    /** @var Webklex\PHPIMAP\ClientManager */
    private $clientManager = null;

    /** @var Webklex\PHPIMAP\Client */
    private $client = null;

    /** @var Webklex\PHPIMAP\Folder */
    private $folder = null;

    /** @var Webklex\PHPIMAP\Message */
    private $message = null;

    private $msgno = null;

    private $parsed = false;

    private $expunge = false;

    /**
    * Constructor.
    */
    public function __construct()
    {
        $debug = System_Core_Application::getInstance()->getDebug();

        $this->clientManager = new Webklex\PHPIMAP\ClientManager( [
            'options' => [ 'debug' => $debug->checkLevel( DEBUG_MAIL ) ]
        ] );
    }

    /**
    * Load inbox settings from the database.
    */
    public function loadSettings()
    {
        $serverManager = new System_Api_ServerManager();
        $this->setSettings( $serverManager->getSettings() );
    }

    /**
    * Initialize the engine with given settings.
    * @param $settings Array of settings to use.
    */
    public function setSettings( $settings )
    {
        $config = [
            'host' => $settings[ 'inbox_server' ],
            'post' => $settings[ 'inbox_port' ],
            'protocol' => $settings[ 'inbox_engine' ],
        ];

        if ( !empty( $settings[ 'inbox_encryption' ] ) )
            $config[ 'encryption' ] = $settings[ 'inbox_encryption' ];

        $config[ 'validate_cert' ] = empty( $settings[ 'inbox_no_validate' ] );

        if ( !empty( $settings[ 'inbox_user' ] ) ) {
            $config[ 'username' ] = $settings[ 'inbox_user' ];
            $config[ 'password' ] = $settings[ 'inbox_password' ];
        }

        $this->client = $this->clientManager->make( $config );

        $this->client->connect();

        if ( !empty( $settings[ 'inbox_mailbox' ] ) )
            $this->folder = $this->client->getFolder( $settings[ 'inbox_mailbox' ] );
        else
            $this->folder = $this->client->getFolder( 'INBOX' );

        if ( $this->folder == null )
            throw new System_Core_Exception( 'Mailbox not found' );
    }

    /**
    * Return the number of all messages in the mailbox.
    */
    public function getMessagesCount()
    {
        $query = $this->folder->messages();

        $query->whereUndeleted();
        $query->whereUnseen();

        return $query->count();
    }

    /**
    * Return an array of unprocessed message numbers.
    */
    public function getMessages()
    {
        $query = $this->folder->messages();

        $query->whereUndeleted();
        $query->whereUnseen();

        $rawQuery = $query->generate_query();

        return $this->client->getConnection()->search( [ $rawQuery ] );
    }

    /**
    * Return an associative array containing headers of message with given number.
    */
    public function getHeaders( $msgno )
    {
        $message = $this->getMessage( $msgno );

        $headers = $message->getHeader();

        $result[ 'from' ] = $this->convertFirstAddress( $headers->from );

        $result[ 'subject' ] = $headers->subject;

        $result[ 'to' ] = $this->convertAddresses( $headers->to );
        $result[ 'cc' ] = $this->convertAddresses( $headers->cc );

        return $result;
    }

    /**
    * Return the raw message headers and body.
    */
    public function getRawMessage( $msgno )
    {
        $message = $this->getMessageWithBody( $msgno );

        $headers = $message->getHeader()->raw;

        $body = $message->getRawBody();

        return $headers . $body;
    }

    /**
    * Return an array representing the structure of the message with given number.
    */
    public function getStructure( $msgno )
    {
        $message = $this->getMessageWithBody( $msgno );

        $result = array();

        $text = $message->getTextBody();
        if ( $text != null )
            $result[] = [ 'type' => 'plain', 'body' => $text ];

        $html = $message->getHTMLBody();
        if ( $html != null )
            $result[] = [ 'type' => 'html', 'body' => $html ];

        if ( $message->hasAttachments() ) {
            foreach ( $message->getAttachments() as $attachment )
                $result[] = [ 'type' => 'attachment', 'body' => $attachment->content, 'name' => $attachment->name != 'undefined' ? $attachment->name : null ];
        }

        return $result;
    }

    /**
    * Return an array representing the plain text parts of the message with given number.
    */
    public function getPlainStructure( $msgno )
    {
        $message = $this->getMessageWithBody( $msgno );

        $result = array();

        $text = $message->getTextBody();
        if ( $text != null )
            $result[] = [ 'type' => 'plain', 'body' => $text ];

        return $result;
    }

    /**
    * Convert plain text part to UTF-8 text.
    */
    public function convertToUtf8( $part )
    {
        return $part[ 'body' ];
    }

    /**
    * Mark the message as processed.
    */
    public function markAsProcessed( $msgno )
    {
        $message = $this->getMessage( $msgno );

        $message->setFlag( 'Seen' );
    }

    /**
    * Mark the message as deleted.
    */
    public function markAsDeleted( $msgno )
    {
        $message = $this->getMessage( $msgno );

        $message->setFlag( 'Deleted' );

        $this->expunge = true;
    }

    /**
    * Close the connection to the server.
    */
    public function close()
    {
        if ( $this->client == null )
            return;

        if ( $this->expunge )
            $this->client->expunge();

        $this->client->disconnect();
        $this->client = null;

        $this->folder = null;
        $this->message = null;
        $this->msgno = null;
        $this->parsed = false;
        $this->expunge = false;
    }

    private function getMessage( $msgno )
    {
        if ( $msgno === $this->msgno )
            return $this->message;

        $query = $this->folder->messages();

        $query->setFetchBody( false );
        $query->markAsRead();

        $message = $query->getMessageByUid( $msgno );

        $this->message = $message;
        $this->msgno = $msgno;
        $this->parsed = false;

        return $message;
    }

    private function getMessageWithBody( $msgno )
    {
        $message = $this->getMessage( $msgno );

        if ( $this->parsed == false ) {
            $message->parseBody();
            $this->parsed = true;
        }

        return $message;
    }

    private function convertAddresses( $attr )
    {
        $result = array();
        if ( $attr != null ) {
            foreach ( $attr->toArray() as $addr ) {
                if ( !empty( $addr->mail ) )
                    $result[] = $this->convertAddress( $addr );
            }
        }
        return $result;
    }

    private function convertFirstAddress( $attr )
    {
        if ( $attr != null ) {
            foreach ( $attr->toArray() as $addr ) {
                if ( !empty( $addr->mail ) )
                    return $this->convertAddress( $addr );
            }
        }
        return [ 'email' => 'unknown' ];
    }

    private function convertAddress( $addr )
    {
        $result[ 'email' ] = $addr->mail;
        if ( !empty( $addr->personal ) )
            $result[ 'name' ] = $addr->personal;
        return $result;
    }
}
