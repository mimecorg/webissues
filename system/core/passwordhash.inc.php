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

/**
 * Password hashing functions.
 *
 * Based on and compatible with the Portable PHP password hashing framework
 * (http://www.openwall.com/phpass/).
 *
 * A custom hash, starting with '$WI0$' prefix, is used for upgrading old-style
 * MD5 hashes used in version 0.8.
 *
 * Rehashes if the old hash is using MD5 via the native password_hash function with bcrypt.
 */
class System_Core_PasswordHash
{
    private $itoa64 = './0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    /**
     * Calculate a portable hash for the given password.
     *
     * @param $password string The plain text password.
     *
     * @return false|string
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Validate given password against the stored hash.
     *
     * @param $password   string The plain text password.
     * @param $storedHash string The stored hash.
     *
     * @return bool @c true if the password is valid, @c false otherwise.
     */
    public function checkPassword($password, $storedHash)
    {
        if ($this->checkPasswordLegacy($password, $storedHash)) {
            return true;
        }

        return password_verify($password, $storedHash);
    }

    /**
     * Validate given password against the stored hash with MD5.
     *
     * @param $password   string The plain text password.
     * @param $storedHash string The stored hash.
     *
     * @return bool @c true if the password is valid, @c false otherwise.
     */
    public function checkPasswordLegacy($password, $storedHash)
    {
        if ($storedHash == null)
            return false;

        if (substr($storedHash, 0, 5) == '$WI0$')
            $password = md5($password);

        $hash = $this->calculateHashLegacy($password, $storedHash);

        if ($hash === false)
            return false;

        return $hash == $storedHash;
    }

    /**
     * Check if a new hash should be calculated.
     *
     * @param $storedHash string The stored hash.
     *
     * @return bool @c true if a new hash should be calculated.
     */
    public function isNewHashNeeded($storedHash)
    {
        return password_needs_rehash($storedHash, PASSWORD_BCRYPT);
    }

    private function calculateHashLegacy($password, $setting)
    {
        if (substr($setting, 0, 3) == '$P$') {
            $setting = substr($setting, 0, 12);
            $salt = substr($setting, 4, 8);
            $log2 = strpos($this->itoa64, $setting[3]);
            if ($log2 < 7 || $log2 > 30)
                return false;
            $count = 1 << $log2;
        } else if (substr($setting, 0, 5) == '$WI0$') {
            $setting = substr($setting, 0, 13);
            $salt = substr($setting, 5, 8);
            $count = 256;
        } else {
            return false;
        }

        if (strlen($salt) != 8)
            return false;

        $hash = md5($salt . $password, true);

        do {
            $hash = md5($hash . $password, true);
        } while (--$count);

        return $setting . $this->encode64($hash, 16);
    }

    private function encode64($input, $count)
    {
        $output = '';
        $i = 0;

        do {
            $value = ord($input[$i++]);
            $output .= $this->itoa64[$value & 0x3f];
            if ($i < $count)
                $value |= ord($input[$i]) << 8;
            $output .= $this->itoa64[($value >> 6) & 0x3f];
            if ($i++ >= $count)
                break;
            if ($i < $count)
                $value |= ord($input[$i]) << 16;
            $output .= $this->itoa64[($value >> 12) & 0x3f];
            if ($i++ >= $count)
                break;
            $output .= $this->itoa64[($value >> 18) & 0x3f];
        } while ($i < $count);

        return $output;
    }
}
