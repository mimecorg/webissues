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

/**
* Engine for receiving email messages using the IMAP extension.
*/
class System_Mail_InboxEngine
{
    private $mailbox = null;

    private $expunge = false;

    /**
    * Constructor.
    */
    public function __construct()
    {
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
        $address = $settings[ 'inbox_server' ] . ':' . $settings[ 'inbox_port' ] . '/' . $settings[ 'inbox_engine' ];

        if ( !empty( $settings[ 'inbox_encryption' ] ) )
            $address .= '/' . $settings[ 'inbox_encryption' ];

        if ( !empty( $settings[ 'inbox_no_validate' ] ) )
            $address .= '/novalidate-cert';

        $address = '{' . $address . '}';
        
        if ( !empty( $setings[ 'inbox_mailbox' ] ) )
            $address .= imap_utf7_encode( $settings[ 'inbox_mailbox' ] );

        if ( !empty( $settings[ 'inbox_user' ] ) ) {
            $user = $settings[ 'inbox_user' ];
            $password = $settings[ 'inbox_password' ];
        } else {
            $user = '';
            $password = '';
        }

        if ( version_compare( phpversion(), '5.3.2', '>=' ) )
            $this->mailbox = @imap_open( $address, $user, $password, 0, 1, array( 'DISABLE_AUTHENTICATOR' => 'GSSAPI' ) );
        else
            $this->mailbox = @imap_open( $address, $user, $password, 0, 1 );

        if ( $this->mailbox === false )
            $this->handleError();
    }

    /**
    * Return the number of all messages in the mailbox.
    */
    public function getMessagesCount()
    {
        $info = @imap_check( $this->mailbox );

        if ( $info === false )
            $this->handleError();

        return $info->Nmsgs;
    }

    /**
    * Return an array of unprocessed message numbers.
    */
    public function getMessages()
    {
        $count = $this->getMessagesCount();

        $result = array();

        if ( $count > 0 ) {
            $messages = @imap_fetch_overview( $this->mailbox, '1:' . $count, 0 );

            if ( $messages === false )
                $this->handleError();

            foreach ( $messages as $message ) {
                if ( !$message->deleted && !$message->seen )
                    $result[] = $message->msgno;
            }
        }

        return $result;
    }

    /**
    * Return an associative array containing headers of message with given number.
    */
    public function getHeaders( $msgno )
    {
        $raw = @imap_fetchheader( $this->mailbox, $msgno );

        if ( $raw === false )
            $this->handleError();

        $headers = imap_rfc822_parse_headers( $raw );

        $result = array();
        
        $result[ 'from' ] = $this->convertAddress( $headers->from[ 0 ] );

        $result[ 'subject' ] = $this->decodeHeader( $headers->subject );

        $result[ 'to' ] = array();
        if ( !empty( $headers->to ) ) {
            foreach ( $headers->to as $addr )
                $result[ 'to' ][] = $this->convertAddress( $addr );
        }

        $result[ 'cc' ] = array();
        if ( !empty( $headers->cc ) ) {
            foreach ( $headers->cc as $addr )
                $result[ 'cc' ][] = $this->convertAddress( $addr );
        }

        if ( !empty( $headers->reply_to[ 0 ] ) )
            $result[ 'reply_to' ] = $this->convertAddress( $headers->reply_to[ 0 ] );

        return $result;
    }

    private function decodeHeader( $header )
    {
        // NOTE: imap_utf8 returns denormalized UTF-8 in some cases
        // NOTE: mb_decode_mimeheader incorrectly handles '_' in Q encoding

        $parts = imap_mime_header_decode( $header );

        if ( $parts === false )
            return '';

        $result = '';

        foreach ( $parts as $part ) {
            if ( $part->charset == 'default' )
                $result .= $part->text;
            else
                $result .= @mb_convert_encoding( $part->text, 'UTF-8', $part->charset );
        }

        return $result;
    }

    private function convertAddress( $addr )
    {
        $result[ 'email' ] = $addr->mailbox . '@' . $addr->host;
        if ( isset( $addr->personal ) )
            $result[ 'name' ] = $this->decodeHeader( $addr->personal );
        return $result;
    }

    /**
    * Return an array representing the structure of the message with given number.
    */
    public function getStructure( $msgno )
    {
        $structure = @imap_fetchstructure( $this->mailbox, $msgno );

        if ( $structure === false )
            $this->handleError();

        $result = array();

        if ( isset( $structure->parts ) ) {
            foreach ( $structure->parts as $key => $part )
                $this->processPart( $part, $msgno, $key + 1, $result );
        } else {
            $result[] = $this->convertPart( $structure, imap_body( $this->mailbox, $msgno ) );
        }

        return $result;
    }

    private function processPart( $structure, $msgno, $section, &$result )
    {
        if ( isset( $structure->parts ) ) {
            foreach ( $structure->parts as $key => $part )
                $this->processPart( $part, $msgno, $section . '.' . ( $key + 1 ) , $result );
        } else {
            $result[] = $this->convertPart( $structure, imap_fetchbody( $this->mailbox, $msgno, $section ) );
        }
    }

    private function convertPart( $structure, $body )
    {
        $result = array();

        if ( $structure->encoding == 3 )
            $body = imap_base64( $body );
        else if ( $structure->encoding == 4 )
            $body = imap_qprint( $body );

        $result[ 'body' ] = $body;

        if ( $structure->ifdisposition && ( strtoupper( $structure->disposition ) == 'ATTACHMENT' || strtoupper( $structure->disposition ) == 'INLINE' ) ) {
            $result[ 'type' ] = 'attachment';
            $result[ 'name' ] = $this->getParameter( $structure, 'NAME' );
        } else if ( $structure->type == 5 && $structure->ifid ) { // inline images without disposition
            $result[ 'type' ] = 'attachment';
            $result[ 'name' ] = $this->getParameter( $structure, 'NAME' );
        } else if ( $structure->type == 0 && $structure->ifsubtype && strtoupper( $structure->subtype ) == 'PLAIN' ) {
            $result[ 'type' ] = 'plain';
            $result[ 'charset' ] = $this->getParameter( $structure, 'CHARSET' );
        } else if ( $structure->type == 0 && $structure->ifsubtype && strtoupper( $structure->subtype ) == 'HTML' ) {
            $result[ 'type' ] = 'html';
        } else {
            $result[ 'type' ] = 'unknown';
        }

        return $result;
    }

    private function getParameter( $structure, $name )
    {
        if ( $structure->ifparameters ) {
            foreach ( $structure->parameters as $param ) {
                if ( strtoupper( $param->attribute ) == $name )
                    return $param->value;
            }
        }
        return null;
    }

    /**
    * Convert plain text part to UTF-8 text.
    */
    public function convertToUtf8( $part )
    {
        return @mb_convert_encoding( $part[ 'body' ], 'UTF-8', $part[ 'charset' ] );
    }

    /**
    * Mark the message as processed.
    */
    public function markAsProcessed( $msgno )
    {
        @imap_setflag_full( $this->mailbox, $msgno, '\Seen' );
    }

    /**
    * Mark the message as deleted.
    */
    public function markAsDeleted( $msgno )
    {
        @imap_delete( $this->mailbox, $msgno );

        $this->expunge = true;
    }

    /**
    * Close the connection to the server.
    */
    public function close()
    {
        if ( $this->mailbox == null )
            return;

        if ( $this->expunge )
            @imap_expunge( $this->mailbox );

        @imap_close( $this->mailbox );

        $this->mailbox = null;
    }

    private function handleError()
    {
        $errors = imap_errors();

        if ( $errors === false )
            throw new System_Core_Exception( 'Unknown inbox error' );

        throw new System_Core_Exception( 'Inbox error: ' . $errors[ 0 ] );
    }
}
