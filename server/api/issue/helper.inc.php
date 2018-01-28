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

class Server_Api_Issue_Helper
{
    public function getValues( $arguments )
    {
        $values = array();

        if ( isset( $arguments[ 'values' ] ) ) {
            if ( !is_array( $arguments[ 'values' ] ) )
                throw new Server_Error( Server_Error::InvalidArguments );

            foreach ( $arguments[ 'values' ] as $item ) {
                $id = isset( $item[ 'id' ] ) ? (int)$item[ 'id' ] : null;
                $value = isset( $item[ 'value' ] ) ? $item[ 'value' ] : null;

                if ( $id == null )
                    throw new Server_Error( Server_Error::InvalidArguments );

                $values[ $id ] = $value;
            }
        }

        return $values;
    }

    public function getInitialValues( $attributes, $typeManager )
    {
        $initialValues = array();

        foreach ( $attributes as $id => $attribute ) {
            $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
            $initialValue = $info->getMetadata( 'default', '' );
            $initialValues[ $id ] = $typeManager->convertInitialValue( $info, $initialValue );
        }

        return $initialValues;
    }

    public function convertValues( $values, $attributes, $parser )
    {
        $convertedValues = array();

        foreach ( $values as $id => $value ) {
            if ( !isset( $attributes[ $id ] ) )
                throw new System_Api_Error( System_Api_Error::UnknownAttribute );

            $attribute = $attributes[ $id ];
            $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );

            $flags = System_Api_Parser::AllowEmpty;
            if ( $info->getType() == 'TEXT' && $info->getMetadata( 'multi-line', 0 ) )
                $flags |= System_Api_Parser::MultiLine;
            $value = $parser->normalizeString( $value, System_Const::ValueMaxLength, $flags );

            $convertedValues[ $id ] = $parser->convertAttributeValue( $attribute[ 'attr_def' ], $value );
        }

        return $convertedValues;
    }

    public function getOrderedValues( $values, $type )
    {
        $orderedValues = array();

        foreach ( $values as $id => $newValue ) {
            $row = array();
            $row[ 'attr_id' ] = $id;
            $row[ 'attr_value' ] = $newValue;
            $orderedValues[] = $row;
        }

        if ( !empty( $orderedValues ) ) {
            $viewManager = new System_Api_ViewManager();
            $orderedValues = $viewManager->sortByAttributeOrder( $type, $orderedValues );
        }

        return $orderedValues;
    }
}
