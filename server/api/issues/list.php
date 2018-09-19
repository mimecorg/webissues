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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Issues_List
{
    public $access = 'anonymous';

    public $params = array(
        'typeId' => 'int',
        'viewId' => 'int',
        'projectId' => 'int',
        'folderId' => 'int',
        'searchColumn' => array( 'type' => 'int', 'default' => System_Api_Column::Name ),
        'searchValue' => 'string',
        'sortColumn' => 'int',
        'sortAscending' => array( 'type' => 'bool', 'default' => true ),
        'offset' => array( 'type' => 'int', 'default' => 0 ),
        'limit' => array( 'type' => 'int', 'required' => true )
    );

    public function run( $typeId, $viewId, $projectId, $folderId, $searchColumn, $searchValue, $sortColumn, $sortAscending, $offset, $limit )
    {
        if ( $typeId == null && $viewId == null && $folderId == null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $viewId != null && $typeId != null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $folderId != null && ( $typeId != null || $projectId != null ) )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $offset < 0 || $limit < 1 )
            throw new Server_Error( Server_Error::InvalidArguments );

        $typeManager = new System_Api_TypeManager();
        $viewManager = new System_Api_ViewManager();
        $projectManager = new System_Api_ProjectManager();

        if ( $viewId != null ) {
            $view = $viewManager->getView( $viewId );
            $type = $typeManager->getIssueTypeForView( $view );
        } else if ( $typeId != null ) {
            $type = $typeManager->getIssueType( $typeId );
            $view = null;
        } else {
            $type = null;
            $view = null;
        }

        if ( $folderId != null ) {
            $folder = $projectManager->getFolder( $folderId );
            if ( $type != null && $type[ 'type_id' ] != $folder[ 'type_id' ] )
                throw new System_Api_Error( System_Api_Error::UnknownFolder );
            $type = $typeManager->getIssueTypeForFolder( $folder );
            $project = null;
        } else if ( $projectId != null ) {
            $project = $projectManager->getProject( $projectId );
            $folder = null;
        } else {
            $project = null;
            $folder = null;
        }

        $queryGenerator = new System_Api_QueryGenerator();

        if ( $folder != null )
            $queryGenerator->setFolder( $folder );
        else if ( $type != null )
            $queryGenerator->setIssueType( $type );
        if ( $project != null )
            $queryGenerator->setProject( $project );

        if ( $view != null )
            $definition = $view[ 'view_def' ];
        else
            $definition = $viewManager->getViewSetting( $type, 'default_view' );

        if ( $definition != null )
            $queryGenerator->setViewDefinition( $definition );

        if ( $searchValue != null ) {
            $validator = new System_Api_Validator();

            $validator->checkString( $searchValue, System_Const::ValueMaxLength );

            $info = $this->getSearchValueInfo( $searchColumn );
            $definition = $info->toString();

            $validator->checkAttributeValue( $definition, $searchValue );

            $queryGenerator->setSearchValue( $searchColumn, $info->getType(), $searchValue );
        }

        if ( $sortColumn !== null ) {
            $order = $sortAscending ? System_Const::Ascending : System_Const::Descending;
            $orderBy = System_Web_ColumnHelper::makeOrderBy( $queryGenerator->getColumnExpression( $sortColumn ), $order );
        } else {
            $sortColumn = $queryGenerator->getColumnFromName( $queryGenerator->getSortColumn() );
            $sortAscending = ( $queryGenerator->getSortOrder() == System_Const::Ascending );
            $orderBy = $queryGenerator->getOrderBy();
        }

        $result[ 'sortColumn' ] = $sortColumn;
        $result[ 'sortAscending' ] = $sortAscending;

        $columns = $queryGenerator->getColumnNames();

        $helper = new System_Web_ColumnHelper();
        $headers = $helper->getColumnHeaders() + $queryGenerator->getUserColumnHeaders();

        $result[ 'columns' ] = array();

        foreach ( $columns as $column => $name ) {
            $resultColumn = array();

            $resultColumn[ 'id' ] = $column;
            $resultColumn[ 'name' ] = $headers[ $column ];

            $result[ 'columns' ][] = $resultColumn;
        }

        System_Web_Base::setLinkMode( System_Web_Base::RouteLinks );

        $connection = System_Core_Application::getInstance()->getConnection();

        $query = $queryGenerator->generateSelectQuery();
        $page = $connection->queryPageArgs( $query, $orderBy, $limit, $offset, $queryGenerator->getQueryArguments() );

        $result[ 'issues' ] = array();

        foreach( $page as $row ) {
            $resultIssue = array();

            $resultIssue[ 'id' ] = $row[ 'issue_id' ];
            $resultIssue[ 'read' ] = $row[ 'read_id' ];
            $resultIssue[ 'stamp' ] = $row[ 'stamp_id' ];

            $cells = array();

            foreach ( $columns as $column => $name ) {
                $value = $row[ $name ];

                switch ( $column ) {
                    case System_Api_Column::ID:
                        $cells[] = '#' . $value;
                        break;

                    case System_Api_Column::Name:
                        $cells[] = System_Web_LinkLocator::convertToHtml( $value );
                        break;

                    case System_Api_Column::Location:
                        $cells[] = htmlspecialchars( $row[ 'project_name' ] ) . ' &mdash; ' . htmlspecialchars( $value );
                        break;

                    case System_Api_Column::CreatedDate:
                    case System_Api_Column::ModifiedDate:
                        $cells[] = $value;
                        break;

                    default:
                        if ( $column > System_Api_Column::UserDefined )
                            $cells[] = System_Web_LinkLocator::convertToHtml( $value );
                        else
                            $cells[] = htmlspecialchars( $value );
                        break;
                }
            }

            $resultIssue[ 'cells' ] = $cells;

            $result[ 'issues' ][] = $resultIssue;
        }

        if ( count( $page ) < $limit ) {
            $result[ 'totalCount' ] = count( $page ) + $offset;
        } else {
            $query = $queryGenerator->generateCountQuery();
            $result[ 'totalCount' ] = $connection->queryScalarArgs( $query, $queryGenerator->getQueryArguments() );
        }

        return $result;
    }

    private function getSearchValueInfo( $column )
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

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_List' );
