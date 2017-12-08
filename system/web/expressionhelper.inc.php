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
* Class providing support for expressions used in filters and initial values.
*/
class System_Web_ExpressionHelper extends System_Web_Base
{
    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Parse a formatted value or expression to the standardized format.
    * @param $type Type of the attribute.
    * @param $definition Definition of the attribute.
    * @param $value Formatted value to convert.
    * @return The standardized value.
    */
    public function parseExpression( $type, $definition, $value )
    {
        $me = $this->tr( 'Me' );
        $today = $this->tr( 'Today' );

        if ( ( $type == 'TEXT' || $type == 'ENUM' || $type == 'USER' ) && preg_match( "/^\\[$me\\]/i", $value ) ) {
            if ( mb_substr( $value, mb_strlen( $me ) + 2 ) !== '' )
                throw new System_Api_Error( System_Api_Error::InvalidFormat );
            return '[Me]';
        } else if ( $type == 'DATETIME' && preg_match( "/^\\[$today\\]/i", $value ) ) {
            $offset = mb_substr( $value, mb_strlen( $today ) + 2 );
            if ( $offset !== '' ) {
                if ( !preg_match( "/^\\s*([+-])\\s*(\\d+)$/", $offset, $matches ) || $matches[ 2 ] == 0 )
                    throw new System_Api_Error( System_Api_Error::InvalidFormat );
                return '[Today]' . $matches[ 1 ] . $matches[ 2 ];
            } else {
                return '[Today]';
            }
        } else {
            $parser = new System_Api_Parser();
            return $parser->convertAttributeValue( $definition, $value );
        }
    }

    /**
    * Format a value or expression.
    * @param $type Type of the attribute.
    * @param $definition Definition of the attribute.
    * @param $value The standardized value to format.
    * @param $flags If MultiLine is given, new lines and multiple spaces
    * are preserved in the value.
    * @return The formatted value.
    */
    public function formatExpression( $type, $definition, $value, $flags = 0 )
    {
        if ( ( $type == 'TEXT' || $type == 'ENUM' || $type == 'USER' ) && mb_substr( $value, 0, 4 ) == '[Me]' ) {
            return '[' . $this->tr( 'Me' ) . ']';
        } else if ( $type == 'DATETIME' && mb_substr( $value, 0, 7 ) == '[Today]' ) {
            return '[' . $this->tr( 'Today' ) . ']' . mb_substr( $value, 7 );
        } else {
            $formatter = new System_Api_Formatter();
            return $formatter->convertAttributeValue( $definition, $value, $flags );
        }
    }

    /**
    * Return all user names and the [Me] item.
    */
    public function getUserItems()
    {
        $principal = System_Api_Principal::getCurrent();

        $userManager = new System_Api_UserManager();
        if ( $principal->isAdministrator() )
            $users = $userManager->getUsers();
        else
            $users = $userManager->getVisibleUsers();

        $items = array( 0 => '[' .$this->tr( 'Me' ) . ']' );
        foreach ( $users as $user )
            $items[ $user[ 'user_id' ] ] = $user[ 'user_name' ];

        return $items;
    }
}
