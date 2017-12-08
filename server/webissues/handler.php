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

require_once( '../../system/bootstrap.inc.php' );

class Server_WebIssues_Handler implements Server_IHandler
{
    private $request = null;
    private $response = null;
    private $debug = null;

    private $actionName = null;
    private $actionRawName = null;
    private $arguments = array();

    private $commandInfo = null;

    public function __construct()
    {
        $application = System_Core_Application::getInstance();
        $this->request = $application->getRequest();
        $this->response = $application->getResponse();
        $this->debug = $application->getDebug();
    }

    public function parseCommand()
    {
        $command = $this->getCommand();

        $keywords = array();
        $state = 'start';

        $tokens = preg_split( '/( |\'|\\\\.)/', $command, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );

        foreach ( $tokens as $token ) {
            switch ( $state ) {
                case 'start':
                    if ( !empty( $keywords ) && $token == '\'' ) {
                        $string = '';
                        $state = 'quote';
                    } else if ( !empty( $keywords ) && preg_match( '/^-?\d+$/', $token ) ) {
                        $integer = (int)$token;
                        if ( $integer != $token )
                            throw new Server_Error( Server_Error::SyntaxError );
                        $this->arguments[] = $integer;
                        $state = 'end';
                    } else if ( empty( $args ) && preg_match( '/^[A-Z]+$/', $token ) ) {
                        $keywords[] = $token;
                        $state = 'end';
                    } else {
                        throw new Server_Error( Server_Error::SyntaxError );
                    }
                    break;

                case 'quote':
                    if ( $token == '\'' ) {
                        $this->arguments[] = $string;
                        $state = 'end';
                    } else if ( $token[ 0 ] == '\\' ) {
                        if ( $token[ 1 ] == 'n' )
                            $string .= "\n";
                        else if ( $token[ 1 ] == 't' )
                            $string .= "\t";
                        else if ( $token[ 1 ] == '\'' || $token[ 1 ] == '\\' )
                            $string .= $token[ 1 ];
                        else
                            throw new Server_Error( Server_Error::SyntaxError );
                    } else {
                        $string .= $token;
                    }
                    break;

                case 'end':
                    if ( $token == ' ' )
                        $state = 'start';
                    else
                        throw new Server_Error( Server_Error::SyntaxError );
                    break;
            }
        }

        if ( $state != 'end' )
            throw new Server_Error( Server_Error::SyntaxError );

        if ( empty( $keywords ) )
            throw new Server_Error( Server_Error::SyntaxError );

        $this->actionName = strtolower( $keywords[ 0 ] );
        $this->actionRawName = strtolower( $keywords[ 0 ] );
        for ( $i = 1; $i < count( $keywords ); $i++ ) {
            $this->actionName .= ucfirst( strtolower( $keywords[ $i ] ) );
            $this->actionRawName .= '_' . strtolower( $keywords[ $i ] );
        }

        $attachment = $this->request->getUploadedFile( 'file' );
        if ( $attachment === false )
            throw new Server_Error( Server_Error::UploadError );
        if ( $attachment !== null )
            $this->arguments[] = $attachment;

        $this->validateCommand();
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setReply( $reply )
    {
        if ( $this->commandInfo != null && $this->commandInfo->getMetadata( 'attachment' ) && !isset( $reply[ 'error' ] ) )
            throw new System_Core_Exception( "Expected binary response" );

        $protocol = System_Core_IniFile::parseRaw( '/common/data/protocol.ini' );
        $replies = $protocol[ 'replies' ];

        if ( isset( $reply[ 'error' ] ) )
            $groups = array( 'error' );
        else
            $groups = $this->commandInfo->getMetadata( 'replies' );

        $content = array();

        foreach ( $groups as $group ) {
            if ( !isset( $reply[ $group ] ) )
                continue;

            $lines = $reply[ $group ];

            $replyInfo = System_Api_DefinitionInfo::fromString( $replies[ $group ] );

            $keyword = $replyInfo->getType();
            $signature = $replyInfo->getMetadata( 'signature' );
            $args = $replyInfo->getMetadata( 'args' );

            foreach ( $lines as $line ) {
                $result = $keyword;
                foreach ( $args as $i => $name ) {
                    if ( !array_key_exists( $name, $line ) )
                        throw new System_Core_Exception( "Missing argument '$name' in reply group '$group'" );
                    $arg = $line[ $name ];
                    $type = $signature[ $i ];
                    if ( $type == 's' ) {
                        $escaped = addcslashes( $arg, "'\\\n\t" );
                        $result .= " '$escaped'";
                    } else if ( $type == 'i' ) {
                        $integer = (int)$arg;
                        $result .= " $integer";
                    }
                }
                $result .= "\r\n";
                $content[] = $result;
            }
        }

        if ( empty( $content ) )
            $content = "NULL\r\n";
        else
            $content = implode( '', $content );

        if ( $this->debug->checkLevel( DEBUG_COMMANDS ) )
            $this->debug->write( "Reply:\n", str_replace( "\r\n", "\n", $content ) );

        $this->response->setCustomHeader( 'X-WebIssues-Version', WI_PROTOCOL_VERSION );

        $this->response->setContentType( 'text/plain; charset=UTF-8' );
        $this->response->setContent( $content );
    }

    public function setErrorReply( $code, $message )
    {
        $reply[ 'error' ][] = array( 'error_code' => $code, 'error_message' => $message );
        $this->setReply( $reply );
    }

    public function setOutputAttachment( $attachment )
    {
        if ( !$this->commandInfo->getMetadata( 'attachment' ) )
            throw new System_Core_Exception( "Unexpected binary response" );

        $this->response->setCustomHeader( 'X-WebIssues-Version', WI_PROTOCOL_VERSION );

        $this->response->setContentType( 'application/octet-stream; charset=binary' );
        $this->response->setAttachment( $attachment );
    }

    private function getCommand()
    {
        $command = $this->request->getFormField( 'command' );
        if ( $command == null )
            throw new Server_Error( Server_Error::SyntaxError );

        if ( !mb_check_encoding( $command ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        if ( preg_match( '/[\x00-\x1f\x7f]/', $command ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        if ( $this->debug->checkLevel( DEBUG_COMMANDS ) )
            $this->debug->write( 'Executing command: ', $command, "\n" );

        return $command;
    }

    private function validateCommand()
    {
        $protocol = System_Core_IniFile::parseRaw( '/common/data/protocol.ini' );
        $commands = $protocol[ 'commands' ];

        if ( !isset( $commands[ $this->actionRawName ] ) )
            throw new Server_Error( Server_Error::UnknownCommand );

        $this->commandInfo = System_Api_DefinitionInfo::fromString( $commands[ $this->actionRawName ] );

        $signature = $this->commandInfo->getMetadata( 'signature' );
        $count = strlen( $signature );

        if ( count( $this->arguments ) != $count )
            throw new Server_Error( Server_Error::InvalidArguments );

        for ( $i = 0; $i < $count; $i++ ) {
            $arg = $this->arguments[ $i ];
            switch ( $signature[ $i ] ) {
                case 's':
                    if ( !is_string( $arg ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                    break;
                case 'i':
                    if ( !is_int( $arg ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                    break;
                case 'a':
                    if ( !is_object( $arg ) || !is_a( $arg, 'System_Core_Attachment' ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                    break;
                default:
                    throw new Server_Error( Server_Error::InvalidArguments );
            }
        }
    }
}

System_Bootstrap::run( 'Server_Application', 'Server_WebIssues_Handler' );
