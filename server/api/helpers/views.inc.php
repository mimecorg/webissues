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

class Server_Api_Helpers_Views
{
    public function getViewInformation( &$resultView, $definition )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        $validator = new System_Api_Validator();
        $resultView[ 'columns' ] = $validator->convertToIntArray( $info->getMetadata( 'columns' ) );

        $resultView[ 'sortColumn' ] = $info->getMetadata( 'sort-column' );
        $resultView[ 'sortAscending' ] = $info->getMetadata( 'sort-desc', 0 ) == 0;
    }

    public function getViewFilters( &$resultView, $definition )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        $resultFilters = array();

        $filters = $info->getMetadata( 'filters' );

        if ( $filters != null ) {
            foreach ( $filters as $filter ) {
                $condition = System_Api_DefinitionInfo::fromString( $filter );

                $resultFilter = array();
                $resultFilter[ 'column' ] = $condition->getMetadata( 'column' );
                $resultFilter[ 'operator' ] = $condition->getType();
                $resultFilter[ 'value' ] = $condition->getMetadata( 'value' );

                $resultFilters[] = $resultFilter;
            }
        }

        $resultView[ 'filters' ] = $resultFilters;
    }

    public function createViewDefinition( $columns, $sortColumn, $sortAscending, $filters )
    {
        $info = new System_Api_DefinitionInfo();
        $info->setType( 'VIEW' );
        $info->setMetadata( 'columns', $columns );
        $info->setMetadata( 'sort-column', $sortColumn );
        if ( $sortAscending == false )
            $info->setMetadata( 'sort-desc', 1 );

        if ( !empty( $filters ) ) {
            $conditions = array();

            foreach ( $filters as $filter ) {
                if ( !is_array( $filter ) || !isset( $filter[ 'operator' ] ) || !is_string( $filter[ 'operator' ] )
                     || !isset( $filter[ 'column' ] ) || !is_integer( $filter[ 'column' ] ) || !isset( $filter[ 'value' ] ) || !is_string( $filter[ 'value' ] ) )
                    throw new Server_Error( Server_Error::InvalidArguments );

                $condition = new System_Api_DefinitionInfo();
                $condition->setType( $filter[ 'operator' ] );
                $condition->setMetadata( 'column', $filter[ 'column' ] );
                $condition->setMetadata( 'value', $filter[ 'value' ] );

                $conditions[] = $condition->toString();
            }

            $info->setMetadata( 'filters', $conditions );
        }

        return $info->toString();
    }
}
