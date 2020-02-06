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

class Server_Api_Application extends System_Core_Application
{
    protected $command;

    protected function __construct( $commandClass )
    {
        parent::__construct();

        $this->command = new $commandClass();
    }

    protected function execute()
    {
        if ( $this->request->getRequestMethod() != 'POST' )
            throw new Server_Error( Server_Error::SyntaxError );

        if ( $this->command->access != 'public' ) {
            $principal = System_Api_Principal::getCurrent();
            $serverManager = new System_Api_ServerManager();

            if ( $principal->isAuthenticated() ) {
                $headerCsrfToken = $this->request->getCsrfToken();
                $sessionCsrfToken = $this->session->getValue( 'CSRF_TOKEN' );
                if ( $headerCsrfToken == null || $sessionCsrfToken == null || $headerCsrfToken != $sessionCsrfToken )
                    throw new System_Api_Error( System_Api_Error::InvalidCsrfToken );
            } else {
                if ( $this->session->isDestroyed() || $serverManager->getSetting( 'anonymous_access' ) != 1 )
                    throw new System_Api_Error( System_Api_Error::LoginRequired );
            }

            if ( $this->command->access == 'admin' )
                $principal->checkAdministrator();
            else if ( $this->command->access == '*' )
                $principal->checkAuthenticated();
            else if ( $this->command->access != 'anonymous' )
                throw new System_Core_Exception( 'Invalid access level' );
        }

        if ( $this->request->getContentType() == 'application/json' )
            $body = $this->request->getPostBody();
        else if ( $this->request->getContentType() == 'multipart/form-data' )
            $body = $this->request->getFormField( 'data' );
        else
            throw new Server_Error( Server_Error::SyntaxError );

        if ( !mb_check_encoding( $body ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        if ( preg_match( '/[\x00-\x1f\x7f]/', $body ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        $data = json_decode( $body, true );

        if ( !is_array( $data ) )
            throw new Server_Error( Server_Error::SyntaxError );

        $args = array();

        foreach ( $this->command->params as $key => $param ) {
            if ( is_array( $param ) ) {
                $type = $param[ 'type' ];
                $required = isset( $param[ 'required' ] ) ? $param[ 'required' ] : false;
                $default = isset( $param[ 'default' ] ) ? $param[ 'default' ] : null;
            } else {
                $type = $param;
                $required = false;
                $default = null;
            }
            if ( $type == 'file' ) {
                $attachment = $this->request->getUploadedFile( 'file' );
                if ( $attachment === false )
                    throw new Server_Error( Server_Error::UploadError );
                if ( $attachment == null && $required )
                    throw new Server_Error( Server_Error::InvalidArguments );
                $args[] = $attachment;
            } else if ( isset( $data[ $key ] ) ) {
                $value = $data[ $key ];
                if ( $type == 'int' ) {
                    if ( !is_int( $value ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                } else if ( $type == 'bool' ) {
                    if ( !is_bool( $value ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                } else if ( $type == 'string' ) {
                    if ( !is_string( $value ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                } else if ( $type == 'array' ) {
                    if ( !is_array( $value ) )
                        throw new Server_Error( Server_Error::InvalidArguments );
                } else {
                    throw new System_Core_Exception( 'Invalid argument type' );
                }
                $args[] = $value;
            } else {
                if ( $required )
                    throw new Server_Error( Server_Error::InvalidArguments );
                $args[] = $default;
            }
        }

        $result = call_user_func_array( array( $this->command, 'run' ), $args );

        $response[ 'result' ] = $result;

        $this->response->setContentType( 'application/json' );
        $this->response->setContent( json_encode( $response ) );
    }

    protected function displayErrorPage()
    {
        $exception = $this->getFatalError();
        $error = Server_Error::getErrorFromException( $exception );
        $content = Server_Error::getErrorResponse( $error, $status );

        $this->response->setStatus( $status );
        $this->response->setContentType( 'application/json' );
        $this->response->setContent( $content );

        $this->response->send();
    }
}
