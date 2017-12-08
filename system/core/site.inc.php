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
* Loader for site configuration files.
*
* There are three site configuration files:
*  - The global configuration file (/common/data/site.ini) contains default options.
*  - An optional user configuration file (/data/site.ini) can be used to override
*    default options and to configure multiple sites.
*  - The local site configuration file, separate for each site, contains database
*    connection options; it is usually created using the setup script.
*
* Normally there is only one site called 'default', but a single installation
* can host multiple sites. 
*
* Options which should be configurable by the site administrator should be stored in
* the database instead using System_Api_ServerManager.
*
* An instance of this class is accessible through the System_Core_Application
* object.
*/
class System_Core_Site
{
    private $siteName = null;
    private $siteConfig = array();
    private $configLoaded = false;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Return the internal name of the current site.
    */
    public function getSiteName()
    {
        return $this->siteName;
    }

    /**
    * Return the configuration option or @c null if it was not specified.
    */
    public function getConfig( $key )
    {
        return isset( $this->siteConfig[ $key ] ) ? $this->siteConfig[ $key ] : null;
    }

    /**
    * Return the configuration path or @c null if it was not specified.
    * The path converted to an absolute path if necessary.
    */
    public function getPath( $key )
    {
        $path = $this->getConfig( $key );
        if ( $path != null ) {
            $path = str_replace( '\\', '/', $path );
            if ( !self::isAbsolutePath( $path ) )
                $path = WI_ROOT_DIR . '/' . $path;
        }
        return $path;
    }

    private static function isAbsolutePath( $path )
    {
        if ( $path[ 0 ] == '/' )
            return true;
        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' ) {
            if ( strlen( $path ) >= 3 && $path[ 1 ] == ':' )
                return true;
        }
        return false;
    }

    /**
    * Return @c true if the local configuration file for the current site was loaded.
    */
    public function isConfigLoaded()
    {
        return $this->configLoaded;
    }

    /**
    * Load the global site configuration file (and the user configuration file
    * if available). If the internal site name is not given it is automatically
    * detected based on the script URL and matching rules.
    */
    public function initializeSite( $name = null )
    {
        // load debug level contants
        require_once( WI_ROOT_DIR . '/system/core/debug.inc.php' );

        $sites = System_Core_IniFile::parseExtended( '/common/data/site.ini', '/data/site.ini' );

        $global = $sites[ 'global' ];
        unset( $sites[ 'global' ] );

        if ( $name == null )
            $name = $this->resolveSiteByUrl( WI_BASE_URL, $sites );
        else
            $name = $this->resolveSiteByName( $name, $sites );

        $this->siteName = $name;
        $this->siteConfig = array_merge( $global, $sites[ $name ] );

        // replace placeholders with site name
        foreach ( $this->siteConfig as $key => &$value )
            $value = str_replace( '%site%', $name, $value );
    }

    /**
    * Load the local site configuration file.
    * If the file doesn't exist, a System_Core_SetupException is thrown.
    */
    public function loadSiteConfig()
    {
        $siteDir = $this->getPath( 'site_dir' );
        $path = $siteDir . '/config.inc.php';

        if ( $path == null || !file_exists( $path ) ) {
            throw new System_Core_SetupException( "Configuration file for site '" . $this->siteName . "' does not exist",
                System_Core_SetupException::SiteConfigNotFound );
        }

        $config = array();

        include( $path );

        $this->siteConfig = array_merge( $this->siteConfig, $config );

        $this->configLoaded = true;
    }

    private function resolveSiteByName( $name, $sites )
    {
        if ( !isset( $sites[ $name ] ) )
            throw new System_Core_Exception( "Site '$name' was not found" );

        $site = $sites[ $name ];

        if ( !empty( $site[ 'alias' ] ) )
            throw new System_Core_Exception( "Site '$name' in an alias of another site" );

        if ( empty( $site[ 'match' ] ) )
            throw new System_Core_Exception( "Site '$name' has an empty match pattern" );

        return $name;
    }

    private function resolveSiteByUrl( $url, $sites )
    {
        $map = array();

        foreach ( $sites as $name => $site ) {
            if ( !empty( $site[ 'alias' ] ) ) {
                $name = $site[ 'alias' ];
                if ( isset( $sites[ $name ][ 'alias' ] ) )
                    throw new System_Core_Exception( "Alias '$name' refers to another alias" );
            }

            if ( !empty( $site[ 'match' ] ) ) {
                $match = $site[ 'match' ];
                if ( isset( $map[ $match ] ) )
                    throw new System_Core_Exception( "Multiple sites match the '$match' pattern" );
                $map[ $match ] = $name;
            }
        }

        $matches = $this->getMatchesForUrl( $url );

        foreach ( $matches as $match ) {
            if ( isset( $map[ $match ] ) )
                return $map[ $match ];
        }

        throw new System_Core_Exception( "No matching site was found for '$url'" );
    }

    private function getMatchesForUrl( $url )
    {
        $matches = array();

        $parsed = parse_url( $url );
        $hosts = isset( $parsed[ 'host' ] ) ? explode( '.', $parsed[ 'host' ] ) : array();
        $paths = isset( $parsed[ 'path' ] ) ? explode( '/', $parsed[ 'path' ] ) : array( '' );
        $port = isset( $parsed[ 'port' ] ) ? $parsed[ 'port' ] : '';

        for ( $i = count( $paths ); $i >= 1; $i-- ) {
            $path = implode( '/', array_slice( $paths, 0, $i ) );
            for ( $j = count( $hosts ); $j >= 0; $j-- ) {
                $host = implode( '.', array_slice( $hosts, count( $hosts ) - $j ) );
                if ( $port != '' )
                    $matches[] = $host . ':' . $port . $path;
                $match = $host . $path;
                if ( $match == '' )
                    $match = '*';
                $matches[] = $match;
            }
        }

        return $matches;
    }
}
