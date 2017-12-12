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

class Server_Api_Issue_List
{
    public function run( $arguments )
    {
        $typeId = isset( $arguments[ 'typeId' ] ) ? (int)$arguments[ 'typeId' ] : null;
        $viewId = isset( $arguments[ 'viewId' ] ) ? (int)$arguments[ 'viewId' ] : null;
        $projectId = isset( $arguments[ 'projectId' ] ) ? (int)$arguments[ 'projectId' ] : null;
        $folderId = isset( $arguments[ 'folderId' ] ) ? (int)$arguments[ 'folderId' ] : null;

        $searchColumn = isset( $arguments[ 'searchColumn' ] ) ? (int)$arguments[ 'searchColumn' ] : System_Const::Column_Name;
        $searchText = isset( $arguments[ 'searchText' ] ) ? $arguments[ 'searchText' ] : '';

        $sortColumn = isset( $arguments[ 'sortColumn' ] ) ? (int)$arguments[ 'sortColumn' ] : null;
        $sortAscending = isset( $arguments[ 'sortAscending' ] ) ? (bool)$arguments[ 'sortAscending' ] : null;

        $offset = isset( $arguments[ 'offset' ] ) ? (int)$arguments[ 'offset' ] : 0;
        $limit = isset( $arguments[ 'limit' ] ) ? (int)$arguments[ 'limit' ] : 50;

        if ( $typeId == null && $viewId == null && $folderId == null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $viewId != null && $typeId != null )
            throw new Server_Error( Server_Error::InvalidArguments );
        if ( $folderId != null && ( $typeId != null || $projectId != null ) )
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
                throw new System_Api_Error( System_Api_Error::InvalidArguments );
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

        $searchError = false;

        $parser = new System_Api_Parser();
        $formatter = new System_Api_Formatter();

        try {
            $searchText = $parser->normalizeString( $searchText, System_Const::ValueMaxLength, System_Api_Parser::AllowEmpty );

            if ( $searchText != '' ) {
                $info = $this->getSearchValueInfo( $searchColumn );
                $definition = $info->toString();

                $value = $parser->convertAttributeValue( $definition, $searchText );
                $searchText = $formatter->convertAttributeValue( $definition, $value );

                $queryGenerator->setSearchValue( $searchColumn, $info->getType(), $value );
            }
        } catch ( System_Api_Error $exception ) {
            $searchError = true;
        }

        if ( $sortColumn !== null ) {
            $order = $sortAscending ? System_Web_Grid::Ascending : System_Web_Grid::Descending;
            $orderBy = System_Web_Grid::makeOrderBy( $queryGenerator->getColumnExpression( $sortColumn ), $order );
        } else {
            $sortColumn = $queryGenerator->getColumnFromName( $queryGenerator->getSortColumn() );
            $sortAscending = ( $queryGenerator->getSortOrder() == System_Web_Grid::Ascending );
            $orderBy = $queryGenerator->getOrderBy();
        }

        $result[ 'searchText' ] = $searchText;
        $result[ 'searchError' ] = $searchError;

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

        $result[ 'issues' ] = array();

        if ( $searchError )
            return $result;

        $connection = System_Core_Application::getInstance()->getConnection();

        $query = $queryGenerator->generateSelectQuery();
        $page = $connection->queryPageArgs( $query, $orderBy, $limit, $offset, $queryGenerator->getQueryArguments() );

        foreach( $page as $row ) {
            $resultIssue = array();

            $resultIssue[ 'id' ] = $row[ 'issue_id' ];
            $resultIssue[ 'read' ] = $row[ 'read_id' ];
            $resultIssue[ 'stamp' ] = $row[ 'stamp_id' ];
            $resultIssue[ 'name' ] = $row[ 'issue_name' ];

            foreach ( $columns as $column => $name ) {
                $value = $row[ $name ];

                switch ( $column ) {
                    case System_Api_Column::Location:
                        $resultIssue[ 'project' ] = $row[ 'project_name' ];
                        $resultIssue[ 'folder' ] = $value;
                        break;

                    case System_Api_Column::CreatedDate:
                        $resultIssue[ 'createdDate' ] = $formatter->formatDateTime( $value, System_Api_Formatter::ToLocalTimeZone );
                        break;

                    case System_Api_Column::CreatedBy:
                        $resultIssue[ 'createdBy' ] = $value;
                        break;

                    case System_Api_Column::ModifiedDate:
                        $resultIssue[ 'modifiedDate' ] = $formatter->formatDateTime( $value, System_Api_Formatter::ToLocalTimeZone );
                        break;

                    case System_Api_Column::ModifiedBy:
                        $resultIssue[ 'modifiedBy' ] = $value;
                        break;

                    default:
                        if ( $column > System_Api_Column::UserDefined ) {
                            $attribute = $queryGenerator->getAttributeForColumn( $column );
                            $value = $formatter->convertAttributeValue( $attribute[ 'attr_def' ], $value );
                            if ( $value != '' ) {
                                $key = 'a' . ( $column - System_Api_Column::UserDefined );
                                $resultIssue[ $key ] = System_Web_LinkLocator::convertToHtml( $value );
                            }
                        }
                        break;
                }
            }

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

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issue_List' );
