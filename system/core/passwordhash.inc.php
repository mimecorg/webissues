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

/**
* Password hashing functions.
*
* Based on and compatible with the Portable PHP password hashing framework
* (http://www.openwall.com/phpass/).
*
* A custom hash, starting with '$WI0$' prefix, is used for upgrading old-style
* MD5 hashes used in version 0.8.
*/
class System_Core_PasswordHash
{
    /** The log2 number of iterations used for password stretching. */
    const HashCount = 14;

    private $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Calculate a portable hash for the given password.
    * @param $password The plain text password.
    * @return The calculated hash.
    */
    public function hashPassword( $password )
    {
        $setting = '$P$' . $this->itoa64[ self::HashCount ] . $this->generateSalt();

        return $this->calculateHash( $password, $setting );
    }

    /**
    * Update old-style password hash to a custom hash.
    * @param $oldHash The MD5 password hash to update.
    * @return The calculated hash.
    */
    public function updatePasswordHash( $oldHash )
    {
        $setting = '$WI0$' . $this->generateSalt();

        return $this->calculateHash( $oldHash, $setting );
    }

    /**
    * Validate given password against the stored hash.
    * @param $password The plain text password.
    * @param $storedHash The stored hash.
    * @return @c true if the password is valid, @c false otherwise.
    */
    public function checkPassword( $password, $storedHash )
    {
        if ( substr( $storedHash, 0, 5 ) == '$WI0$' )
            $password = md5( $password );

        $hash = $this->calculateHash( $password, $storedHash );

        if ( $hash === false )
            return false;

        return $hash == $storedHash;
    }

    /**
    * Check if a new hash should be calculated.
    * @param $storedHash The stored hash.
    * @return @c true if a new hash should be calculated.
    */
    public function isNewHashNeeeded( $storedHash )
    {
        if ( substr( $storedHash, 0, 3 ) != '$P$' )
            return true;

        if ( strpos( $this->itoa64, $storedHash[ 3 ] ) != self::HashCount )
            return true;

        return false;
    }

    private function calculateHash( $password, $setting )
    {
        if ( substr( $setting, 0, 3 ) == '$P$' ) {
            $setting = substr( $setting, 0, 12 );
            $salt = substr( $setting, 4, 8 );
            $log2 = strpos( $this->itoa64, $setting[ 3 ] );
            if ( $log2 < 7 || $log2 > 30 )
                return false;
            $count = 1 << $log2;
        } else if ( substr( $setting, 0, 5 ) == '$WI0$' ) {
            $setting = substr( $setting, 0, 13 );
            $salt = substr( $setting, 5, 8 );
            $count = 256;
        } else {
            return false;
        }

        if ( strlen( $salt ) != 8 )
            return false;

        $hash = md5( $salt . $password, true );

        do {
            $hash = md5( $hash . $password, true );
        } while ( --$count );

        return $setting . $this->encode64( $hash, 16 );
    }

    private function generateSalt()
    {
        $rand = pack( 'S3', mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );

        return $this->encode64( $rand, 6 );
    }

	private function encode64( $input, $count )
	{
		$output = '';
		$i = 0;

		do {
			$value = ord( $input[ $i++ ] );
			$output .= $this->itoa64[ $value & 0x3f ];
			if ( $i < $count )
				$value |= ord( $input[ $i ] ) << 8;
			$output .= $this->itoa64[ ( $value >> 6 ) & 0x3f ];
			if ( $i++ >= $count )
				break;
			if ( $i < $count )
				$value |= ord( $input[ $i ] ) << 16;
			$output .= $this->itoa64[ ( $value >> 12 ) & 0x3f ];
			if ( $i++ >= $count )
				break;
			$output .= $this->itoa64[ ( $value >> 18 ) & 0x3f ];
		} while ( $i < $count );

		return $output;
	}
}
