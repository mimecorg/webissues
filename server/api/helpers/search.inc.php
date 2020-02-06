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

class Server_Api_Helpers_Search
{
    public function getSearchValueInfo( $column )
    {
        switch ( $column ) {
            case System_Api_Column::ID:
                $definition = 'NUMERIC';
                break;
            case System_Api_Column::Name:
            case System_Api_Column::Location:
                $definition = 'TEXT';
                break;
            case System_Api_Column::CreatedBy:
            case System_Api_Column::ModifiedBy:
                $definition = 'USER';
                break;
            case System_Api_Column::CreatedDate:
            case System_Api_Column::ModifiedDate:
                $definition = 'DATETIME';
                break;
            default:
                if ( $column > System_Api_Column::UserDefined ) {
                    $typeManager = new System_Api_TypeManager();
                    $attribute = $typeManager->getAttributeType( $column - System_Api_Column::UserDefined );
                    $definition = $attribute[ 'attr_def' ];
                }
                break;
        }

        $attributeInfo = System_Api_DefinitionInfo::fromString( $definition );
        $valueInfo = new System_Api_DefinitionInfo();

        switch ( $attributeInfo->getType() ) {
            case 'TEXT':
            case 'ENUM':
            case 'USER':
                $valueInfo->setType( 'TEXT' );
                break;
            case 'NUMERIC':
                $valueInfo->setType( 'NUMERIC' );
                $valueInfo->setMetadata( 'decimal', $attributeInfo->getMetadata( 'decimal' ) );
                $valueInfo->setMetadata( 'strip', $attributeInfo->getMetadata( 'strip' ) );
                break;
            case 'DATETIME':
                $valueInfo->setType( 'DATETIME' );
                break;
        }

        return $valueInfo;
    }
}
