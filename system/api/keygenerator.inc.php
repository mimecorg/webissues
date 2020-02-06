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
* Generate keys for registration requests and resetting passwords.
*/
class System_Api_KeyGenerator extends System_Api_Base
{
    /**
    * @name Key Lengths
    */
    /*@{*/
    /** Registration request key length. */
    const RegistrationRequest = 8;
    /** Password reset key length. */
    const PasswordReset = 12;
    /*@}*/

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Generate a random key.
    * @param $length The length of the key to generate.
    */
    public function generateKey( $length )
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $len = strlen( $chars );

        $result = '';

        for ( $i = 0; $i < $length; $i ++ )
            $result .= $chars[ mt_rand( 0, $len - 1 ) ];

        return $result;
    }
}
