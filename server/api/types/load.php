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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Types_Load
{
    public $access = '*';

    public $params = array(
        'typeId' => array( 'type' => 'int', 'required' => true ),
        'attributes' => array( 'type' => 'bool', 'default' => false ),
        'defaultView' => array( 'type' => 'bool', 'default' => false ),
        'publicViews' => array( 'type' => 'bool', 'default' => false ),
        'personalViews' => array( 'type' => 'bool', 'default' => false ),
        'used' => array( 'type' => 'bool', 'default' => false )
    );

    public function run( $typeId, $attributes, $defaultView, $publicViews, $personalViews, $used )
    {
        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );

        $result[ 'id' ] = $type[ 'type_id' ];
        $result[ 'name' ] = $type[ 'type_name' ];

        if ( $attributes ) {
            $attributeRows = $typeManager->getAttributeTypesForIssueType( $type );

            $viewManager = new System_Api_ViewManager();
            $attributeRows = $viewManager->sortByAttributeOrder( $type, $attributeRows );

            $result[ 'attributes' ] = array();

            foreach ( $attributeRows as $attribute ) {
                $resultAttribute = array();
                $resultAttribute[ 'id' ] = $attribute[ 'attr_id' ];
                $resultAttribute[ 'name' ] = $attribute[ 'attr_name' ];

                $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
                $resultAttribute[ 'type' ] = $info->getType();
                foreach ( $info->getAllMetadata() as $key => $value )
                    $resultAttribute[ $key ] = $value;

                $result[ 'attributes' ][] = $resultAttribute;
            }
        }

        if ( $defaultView ) {
            $viewManager = new System_Api_ViewManager();
            $definition = $viewManager->getViewSetting( $type, 'default_view' );

            $resultView = array();

            if ( $definition != null ) {
                $helper = new Server_Api_Helpers_Views();
                $helper->getViewInformation( $resultView, $definition );
            } else {
                $resultView[ 'columns' ] = array( System_Api_Column::ID, System_Api_Column::Name, System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy );
                $resultView[ 'sortColumn' ] = System_Api_Column::ID;
                $resultView[ 'sortAscending' ] = true;
            }

            $result[ 'defaultView' ] = $resultView;
        }

        if ( $publicViews ) {
            $viewManager = new System_Api_ViewManager();
            $viewRows = $viewManager->getPublicViews( $type );

            $result[ 'publicViews' ] = array();

            $helper = new Server_Api_Helpers_Views();

            foreach ( $viewRows as $view ) {
                $resultView = array();
                $resultView[ 'id' ] = $view[ 'view_id' ];
                $resultView[ 'name' ] = $view[ 'view_name' ];

                $helper->getViewInformation( $resultView, $view[ 'view_def' ] );

                $result[ 'publicViews' ][] = $resultView;
            }
        }

        if ( $personalViews ) {
            $viewManager = new System_Api_ViewManager();
            $viewRows = $viewManager->getPersonalViews( $type );

            $result[ 'personalViews' ] = array();

            $helper = new Server_Api_Helpers_Views();

            foreach ( $viewRows as $view ) {
                $resultView = array();
                $resultView[ 'id' ] = $view[ 'view_id' ];
                $resultView[ 'name' ] = $view[ 'view_name' ];

                $helper->getViewInformation( $resultView, $view[ 'view_def' ] );

                $result[ 'personalViews' ][] = $resultView;
            }
        }

        if ( $used )
            $result[ 'used' ] = $typeManager->checkIssueTypeUsed( $type );

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Types_Load' );
