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
* Helper functions for various file system operations.
*/
class System_Core_FileSystem
{
    /**
    * Check if the file name is valid. A file name is invalid when it is empty,
    * begins with a dot or contains special characters like backslash, colon, etc.
    * @param $name The name to check.
    * @return @c true if the name is valid.
    */
    public static function isValidFileName( $name )
    {
        return ( $name != '' && $name[ 0 ] != '.' && strpbrk( $name, '\\/:*?"<>|' ) === false );
    }

    /**
    * Check if the specified directory exists.
    * @param $path The absolute path of the directory (without trailing slash).
    * @param $create @c true if the directory should be created if it doesn't exist.
    * @return @c true if the directory exists or was successfully created.
    */
    public static function isDirectory( $path, $create = false )
    {
        if ( is_dir( $path ) )
            return true;
        if ( $create && @mkdir( $path, 0755, true ) )
            return true;
        return false;
    }

    /**
    * Check if the specified directory is writable.
    * A temporary file is created and deleted to make sure the check works reliably
    * on all platforms and configurations.
    * @param $path The absolute path of the directory (without trailing slash).
    * @return @c true if the directory exists and is writable.
    */
    public static function isDirectoryWritable( $path )
    {
        $tempFile = sprintf( '%s/test_%04x.tmp', $path, mt_rand( 0, 0xffff ) );

        if ( !self::isFileWritable( $tempFile ) )
            return false;

        @unlink( $tempFile );

        return true;
    }

    /**
    * Check if the specified file is writable.
    */
    public static function isFileWritable( $path )
    {
        if ( !self::isDirectory( dirname( $path ), true ) )
            return false;

        if ( !( $fp = @fopen( $path, 'a' ) ) )
            return false;

        fclose( $fp );

        return true;
    }

    /**
    * Convert path to native separators (slash on Unix, backslash on Windows).
    */
    public static function toNativeSeparators( $path )
    {
        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
            return str_replace( '/', '\\', $path );
        return $path;
    }
}
