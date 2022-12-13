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
* Manage OAuth authentication.
*/
class System_Api_OAuthManager extends System_Api_Base
{
    private $provider = null;

    public function __construct()
    {
        parent::__construct();

        $site = System_Core_Application::getInstance()->getSite();
        $oauth = $site->loadNamedConfigFile( 'oauth' );

        $this->provider = new \League\OAuth2\Client\Provider\GenericProvider( $oauth );
    }

    public function getAuthorizationUrl()
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl();

        $serverManager = new System_Api_ServerManager();
        $serverManager->setSetting( 'oauth_state', $this->provider->getState() );

        return $authorizationUrl;
    }

    public function updateAccessToken( $code, $state )
    {
        if ( $code == null )
            throw new System_Core_Exception( 'Invalid authorization code' );

        $serverManager = new System_Api_ServerManager();
        $savedState = $serverManager->getSetting( 'oauth_state' );

        if ( $state == null || $state != $savedState )
            throw new System_Core_Exception( 'Invalid authorization state' );

        $token = $this->provider->getAccessToken( 'authorization_code', [ 'code' => $code ] );

        $data = json_encode( $token->jsonSerialize() );

        $serverManager->setSetting( 'oauth_token', $data );

        return $token->getToken();
    }

    public function getAccessToken( $refresh = false )
    {
        $serverManager = new System_Api_ServerManager();
        $data = $serverManager->getSetting( 'oauth_token' );

        if ( $data == null )
            return null;

        $token = new \League\OAuth2\Client\Token\AccessToken( json_decode( $data, true ) );

        if ( $token->hasExpired() ) {
            if ( $refresh && $token->getRefreshToken() != null ) {
                $newToken = $this->provider->getAccessToken( 'refresh_token', [ 'refresh_token' => $token->getRefreshToken() ] );

                $data = json_encode( $newToken->jsonSerialize() );

                $serverManager->setSetting( 'oauth_token', $data );

                return $newToken->getToken();
            }

            return null;
        }

        return $token->getToken();
    }
}
