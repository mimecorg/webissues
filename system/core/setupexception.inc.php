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
* An exception thrown during initialization of the application.
*
* Such exception occurs when the application is not yet configured by the user
* or the database was not updated after updating the application. It does not
* prevent the application from working to make it possible to use the setup
* or update script, but sessions and other database related features are not
* available.
*
* @see System_Core_Application::handleSetupException().
*/
class System_Core_SetupException extends System_Core_Exception
{
    /**
    * The site configuration file was not yet created.
    */
    const SiteConfigNotFound = 1;
    /**
    * The database has an incompatible version which cannot be updated.
    */
    const DatabaseNotCompatible = 2;
    /**
    * The database has an old version which can be updated.
    */
    const DatabaseNotUpdated = 3;

    /**
    * Constructor.
    * @param $message An optional error message.
    * @param $code One of the error code constants.
    * @param $wrappedException An optional exception to be wrapped
    * by this exception.
    */
    public function __construct( $message, $code, $wrappedException = null )
    {
        parent::__construct( $message, $wrappedException );
        $this->code = $code;
    }
}
