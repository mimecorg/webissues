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

/**
* Class providing user friendly error messages.
*/
class System_Web_ErrorHelper extends System_Web_Base
{
    private $form = null;

    /**
    * Constructor.
    * @param $form The System_Web_Form associated with error messages.
    */
    public function __construct( $form = null )
    {
        parent::__construct();

        $this->form = $form;
    }

    /**
    * Display user friendly error message.
    * @param $key The name of the error.
    * @param $error A System_Api_Error exception or an error code.
    */
    public function handleError( $key, $error )
    {
        if ( is_a( $error, 'System_Api_Error' ) )
            $error = $error->getMessage();

        $message = $this->getErrorMessage( $error );

        if ( $message == '' )
            $this->form->setError( $key, $this->t( 'error.IncorrectValues' ) );
        else
            $this->form->setError( $key, $message );
    }

    /**
    * Return the user friendly error message.
    * @param $error An error code.
    */
    public function getErrorMessage( $error )
    {
        $parts = explode( ' ', $error, 2 );
        $code = str_replace( ' ', '', $parts[ 1 ] );

        if ( $this->te( 'ErrorCode.' . $code ) )
            return $this->t( 'ErrorCode.' . $code );
        else
            return '';
    }
}
