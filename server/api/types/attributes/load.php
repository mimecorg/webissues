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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Types_Attributes_Load
{
    public $access = 'admin';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'attributeId' => array( 'type' => 'int', 'required' => true ),
        'details' => array( 'type' => 'bool', 'default' => false ),
        'used' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $typeId, $attributeId, $details, $used )
    {
        $typeManager = new System_Api_TypeManager();
        $attribute = $typeManager->getAttributeType( $attributeId );

        if ( $attribute[ 'type_id' ] != $typeId )
            throw new System_Api_Error( System_Api_Error::UnknownAttribute );

        $result[ 'name' ] = $attribute[ 'attr_name' ];

        $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
        $result[ 'type' ] = $info->getType();

        if ( $details ) {
            $resultDetails = array();

            foreach ( $info->getAllMetadata() as $key => $value )
                $resultDetails[ $key ] = $value;

            if ( empty( $resultDetails ) )
                $resultDetails = new stdClass();

            $result[ 'details' ] = $resultDetails;
        }

        if ( $used )
            $result[ 'used' ] = $typeManager->checkAttributeTypeUsed( $attribute );

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Types_Attributes_Load' );
