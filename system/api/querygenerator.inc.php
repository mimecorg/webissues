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

/**
* Generator for SQL queries for retrieving issues.
*
* It supports customizing the list of columns and filtering issues using
* various criteria. Queries can be executed using appropriate methods
* of System_Db_Connection with arguments provided by getQueryArguments().
*/
class System_Api_QueryGenerator extends System_Api_Base
{
    const AllColumns = 1;
    const WithState = 2;

    private $folderId = 0;
    private $typeId = 0;
    private $projectId = 0;
    private $attributes = array();

    private $columns = array();
    private $filters = array();
    private $sortColumn = null;
    private $sortOrder = null;

    private $sinceStamp = null;
    private $noRead = false;

    private $arguments = null;

    private $locale = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();

        $this->columns = array(
            System_Api_Column::ID, System_Api_Column::Name, System_Api_Column::Location,
            System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy
        );

        $this->sortColumn = $this->getColumnName( System_Api_Column::ID );
        $this->sortOrder = System_Const::Ascending;

        $this->locale = new System_Api_Locale();
    }

    /**
    * Set the type of the issues to retrieve.
    */
    public function setIssueType( $type )
    {
        $this->typeId = $type[ 'type_id' ];

        $typeManager = new System_Api_TypeManager();
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $viewManager = new System_Api_ViewManager();
        $attributes = $viewManager->sortByAttributeOrder( $type, $attributes );

        foreach ( $attributes as $attribute )
            $this->attributes[ System_Api_Column::UserDefined + $attribute[ 'attr_id' ] ] = $attribute;
    }

    /**
    * Set the view definition to use.
    */
    public function setViewDefinition( $definition )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        $allColumns = $this->getAvailableColumns();

        $validator = new System_Api_Validator();
        $columns = $validator->convertToIntArray( $info->getMetadata( 'columns' ) );

        $this->columns = array_intersect( $columns, $allColumns );

        if ( $this->folderId == 0 )
            $this->columns = array_merge( array_slice( $this->columns, 0, 2 ), array( System_Api_Column::Location ), array_slice( $this->columns, 2 ) );

        $sortColumn = $info->getMetadata( 'sort-column' );

        if ( array_search( $sortColumn, $allColumns ) !== false ) {
            $this->sortColumn = $this->getColumnName( $sortColumn );
            $this->sortOrder = $info->getMetadata( 'sort-desc', 0 ) ? System_Const::Descending : System_Const::Ascending;
        }

        $filters = $info->getMetadata( 'filters' );
        if ( $filters != null ) {
            foreach ( $filters as $filterDefinition ) {
                $filterInfo = System_Api_DefinitionInfo::fromString( $filterDefinition );
                $filterColumn = $filterInfo->getMetadata( 'column' );
                if ( array_search( $filterColumn, $allColumns ) !== false )
                    $this->filters[] = $filterInfo;
            }
        }
    }

    /**
    * Include all available system and user columns in the view.
    */
    public function includeAvailableColumns()
    {
        $this->columns = $this->getAvailableColumns();

        if ( $this->folderId == 0 )
            $this->columns = array_merge( array_slice( $this->columns, 0, 2 ), array( System_Api_Column::Location ), array_slice( $this->columns, 2 ) );
    }

    private function getAvailableColumns()
    {
        $systemColumns = array(
            System_Api_Column::ID, System_Api_Column::Name,
            System_Api_Column::CreatedDate, System_Api_Column::CreatedBy,
            System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy
        );
        return array_merge( $systemColumns, array_keys( $this->attributes ) );
    }

    /**
    * Set the quick search value for the list.
    */
    public function setSearchValue( $column, $type, $value )
    {
        $info = new System_Api_DefinitionInfo();
        if ( $type == 'NUMERIC' || $type == 'DATETIME' )
            $info->setType( 'EQ' );
        else
            $info->setType( 'CON' );
        $info->setMetadata( 'column', $column );
        $info->setMetadata( 'value', $value );

        $this->filters[] = $info;
    }

    /**
    * Only include issues from specified project.
    */
    public function setProject( $project )
    {
        $this->projectId = $project[ 'project_id' ];
    }

    /**
    * Only include issues from specified folder.
    */
    public function setFolder( $folder )
    {
        $this->folderId = $folder[ 'folder_id' ];

        $this->columns = array_diff( $this->columns, array( System_Api_Column::Location ) );
    }

    /**
    * Only include issues with stamp greater than specified value.
    */
    public function setSinceStamp( $stamp )
    {
        $this->sinceStamp = $stamp;
    }

    /**
    * If @c true, only include new and modified issues.
    */
    public function setNoRead( $flag )
    {
        $this->noRead = $flag;
    }

    /**
    * Return the list of columns in the current view.
    */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
    * Return the default sort column in the current view.
    */
    public function getSortColumn()
    {
        return $this->sortColumn;
    }

    /**
    * Return the default sort order in the current view.
    */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
    * Return a query for calculating the number of issues.
    */
    public function generateCountQuery()
    {
        return 'SELECT COUNT(*) FROM ' . $this->generateJoins()
            . ' WHERE ' . $this->generateConditions();
    }

    /**
    * Return a query for extracting issues.
    */
    public function generateSelectQuery()
    {
        return 'SELECT ' . $this->generateSelect( self::AllColumns | self::WithState )
            . ' FROM ' . $this->generateJoins( self::AllColumns | self::WithState )
            . ' WHERE ' . $this->generateConditions();
    }

    /**
    * Return a query for extracting issue IDs.
    */
    public function generateIdsQuery()
    {
        return 'SELECT i.issue_id FROM ' . $this->generateJoins()
            . ' WHERE ' . $this->generateConditions();
    }

    /**
    * Return the arguments to be passed when executing the query.
    */
    public function getQueryArguments()
    {
        return $this->arguments;
    }

    /**
    * Return the sorting order specifier.
    */
    public function getOrderBy()
    {
        $columns = $this->getSortableColumns();

        return System_Web_ColumnHelper::makeOrderBy( $columns[ $this->sortColumn ], $this->sortOrder );
    }

    /**
    * Return the sorting order specifier for a given column.
    * @param $column A System_Api_Column constant identifying the column.
    * @return The sorting order specifier.
    */
    public function getColumnExpression( $column )
    {
        if ( $column == System_Api_Column::Location )
            return 'p.project_name COLLATE LOCALE, f.folder_name COLLATE LOCALE, i.issue_id ASC';

        $expression = $this->makeColumnSelect( $column );

        $pos = strpos( $expression, ' AS ' );
        if ( $pos !== false )
            $expression = substr( $expression, 0, $pos );

        switch ( $column ) {
            case System_Api_Column::Name:
            case System_Api_Column::CreatedBy:
            case System_Api_Column::ModifiedBy:
                $expression .= ' COLLATE LOCALE';
                break;

            default:
                if ( isset( $this->attributes[ $column ] ) ) {
                    $attribute = $this->attributes[ $column ];
                    $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );

                    switch ( $info->getType() ) {
                        case 'TEXT':
                        case 'ENUM':
                        case 'USER':
                            $expression .= ' COLLATE LOCALE';
                            break;

                        case 'NUMERIC':
                            $expression = $this->connection->castExpression( $expression, 'f' );
                            break;

                        case 'DATETIME':
                            $expression = $this->connection->castExpression( $expression, 't' );
                            break;
                    }
                }
                break;
        }

        if ( $column != System_Api_Column::ID )
            $expression .= ', i.issue_id ASC';

        return $expression;
    }

    /**
    * Return the name of a given column.
    * @param $column A System_Api_Column constant identifying the column.
    * @return The name of the column.
    */
    public function getColumnName( $column )
    {
        $name = $this->makeColumnSelect( $column );

        $pos = strrpos( $name, ' ' );
        if ( $pos !== false )
            $name = substr( $name, $pos + 1 );
        $pos = strrpos( $name, '.' );
        if ( $pos !== false )
            $name = substr( $name, $pos + 1 );

        return $name;
    }

    /**
    * Return the names of all extracted columns.
    */
    public function getColumnNames()
    {
        $names = array();
        foreach ( $this->columns as $column )
            $names[ $column ] = $this->getColumnName( $column );
        return $names;
    }

    /**
    * Return the identifier of the column with given name.
    */
    public function getColumnFromName( $name )
    {
        foreach ( $this->columns as $column ) {
            if ( $this->getColumnName( $column ) == $name )
                return $column;
        }
        return null;
    }

    /**
    * Return the list of sortable columns.
    */
    public function getSortableColumns()
    {
        $columns = array();
        foreach ( $this->columns as $column )
            $columns[ $this->getColumnName( $column ) ] = $this->getColumnExpression( $column );
        return $columns;
    }

    /**
    * Return the headers of all user defined columns.
    */
    public function getUserColumnHeaders()
    {
        $headers = array();
        foreach ( $this->attributes as $column => $attribute )
            $headers[ $column ] = $attribute[ 'attr_name' ];
        return $headers;
    }

    /**
    * Return the attribute associated with a user defined column.
    */
    public function getAttributeForColumn( $column )
    {
        return $this->attributes[ $column ];
    }

    private function generateSelect( $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $result = array();

        if ( $flags & self::WithState ) {
            $result[] = 'i.stamp_id';
            if ( $principal->isAuthenticated() ) {
                $result[] = 's.read_id';
                $result[] = 's.subscription_id';
            } else {
                $result[] = 'i.stamp_id AS read_id';
                $result[] = 'NULL AS subscription_id';
            }
        }

        if ( $flags & self::AllColumns ) {
            foreach ( $this->columns as $column )
                $result[] = $this->makeColumnSelect( $column );
        }

        return implode( ', ', $result );
    }

    private function makeColumnSelect( $column )
    {
        switch ( $column ) {
            case System_Api_Column::ID:
                return 'i.issue_id';
            case System_Api_Column::Name:
                return 'i.issue_name';
            case System_Api_Column::CreatedDate:
                return 'sc.stamp_time AS created_date';
            case System_Api_Column::CreatedBy:
                return 'uc.user_name AS created_by';
            case System_Api_Column::ModifiedDate:
                return 'sm.stamp_time AS modified_date';
            case System_Api_Column::ModifiedBy:
                return 'um.user_name AS modified_by';
            case System_Api_Column::Location:
                return 'p.project_name, f.folder_name';
            default:
                if ( isset( $this->attributes[ $column ] ) ) {
                    $attrId = $column - System_Api_Column::UserDefined;
                    return "a$attrId.attr_value AS v$attrId";
                }
                throw new System_Core_Exception( 'Invalid column' );
        }
    }

    private function generateJoins( $flags = 0 )
    {
        $principal = System_Api_Principal::getCurrent();

        $columns = array();
        foreach ( $this->filters as $filter )
            $columns[] = $filter->getMetadata( 'column' );

        if ( $flags & self::AllColumns )
            $columns = array_merge( $columns, $this->columns );

        $joins = array( '{issues} AS i' );

        $this->arguments = array();

        if ( $this->folderId == 0 ) {
            $joins[] = 'JOIN {folders} AS f ON f.folder_id = i.folder_id';
            $joins[] = 'JOIN {projects} AS p ON p.project_id = f.project_id';
        }

        if ( ( ( $flags & self::WithState ) || $this->noRead ) && $principal->isAuthenticated() )
            $this->addJoin( $joins, 'LEFT OUTER JOIN {issue_states} AS s ON s.issue_id = i.issue_id AND s.user_id = %d', $principal->getUserId() );

        foreach ( $columns as $column ) {
            switch ( $column ) {
                case System_Api_Column::CreatedDate:
                    $this->addJoin( $joins, 'JOIN {stamps} AS sc ON sc.stamp_id = i.issue_id' );
                    break;
                case System_Api_Column::CreatedBy:
                    $this->addJoin( $joins, 'JOIN {stamps} AS sc ON sc.stamp_id = i.issue_id' );
                    $this->addJoin( $joins, 'JOIN {users} AS uc ON uc.user_id = sc.user_id' );
                    break;
                case System_Api_Column::ModifiedDate:
                    $this->addJoin( $joins, 'JOIN {stamps} AS sm ON sm.stamp_id = i.stamp_id' );
                    break;
                case System_Api_Column::ModifiedBy:
                    $this->addJoin( $joins, 'JOIN {stamps} AS sm ON sm.stamp_id = i.stamp_id' );
                    $this->addJoin( $joins, 'JOIN {users} AS um ON um.user_id = sm.user_id' );
                    break;
                default:
                    if ( isset( $this->attributes[ $column ] ) ) {
                        $attrId = $column - System_Api_Column::UserDefined;
                        $this->addJoin( $joins, "LEFT OUTER JOIN {attr_values} AS a$attrId ON a$attrId.issue_id = i.issue_id AND a$attrId.attr_id = %d", $attrId );
                    }
                    break;
            }
        }

        return implode( ' ', $joins );
    }

    private function addJoin( &$joins, $join, $argument = null )
    {
        if ( !in_array( $join, $joins ) ) {
            $joins[] = $join;
            if ( $argument !== null )
                $this->arguments[] = $argument;
        }
    }

    private function generateConditions()
    {
        $principal = System_Api_Principal::getCurrent();

        if ( $this->folderId != 0 ) {
            $conditions = array( 'i.folder_id = %d' );
            $this->arguments[] = $this->folderId;
        } else {
            $conditions = array( 'f.type_id = %d' );
            $this->arguments[] = $this->typeId;

            if ( $this->projectId != 0 ) {
                $conditions[] = 'p.project_id = %d';
                $this->arguments[] = $this->projectId;
            } else {
                $conditions[] = 'p.is_archived = 0';

                if ( !$principal->isAuthenticated() ) {
                    $conditions[] = 'p.is_public = 1';
                } else if ( !$principal->isAdministrator() ) {
                    $conditions[] = '( p.project_id IN ( SELECT project_id FROM {rights} WHERE user_id = %d ) OR p.is_public = 1 )';
                    $this->arguments[] = $principal->getUserId();
                }
            }
        }

        if ( $this->sinceStamp != null ) {
            $conditions[] = 'i.stamp_id > %d';
            $this->arguments[] = $this->sinceStamp;
        }

        if ( $this->noRead )
            $conditions[] = 'i.stamp_id > COALESCE( s.read_id, 0 )';

        foreach ( $this->filters as $filter ) {
            $expression = $this->makeColumnSelect( $filter->getMetadata( 'column' ) );
            $pos = strpos( $expression, ' ' );
            if ( $pos !== false )
                $expression = substr( $expression, 0, $pos );

            $conditions[] = $this->makeCondition( $expression, $filter );
        }

        return implode( ' AND ', $conditions );
    }

    private function makeCondition( $expression, $filter )
    {
        $type = $filter->getType();
        $column = $filter->getMetadata( 'column' );
        $value = $filter->getMetadata( 'value' );

        if ( $value == '' )
            return $this->makeNullCondition( $expression, $type );

        switch ( $column ) {
            case System_Api_Column::ID:
                return $this->makeNumericCondition( $expression, $type, (int)$value, '%d' );

            case System_Api_Column::Name:
            case System_Api_Column::CreatedBy:
            case System_Api_Column::ModifiedBy:
                $value = $this->convertUserValue( $value );
                if ( $value == '' )
                    return $this->makeNullCondition( $expression, $type );
                return $this->makeStringCondition( "UPPER( $expression )", $type, mb_strtoupper( $value ) );

            case System_Api_Column::CreatedDate:
            case System_Api_Column::ModifiedDate:
                $value = $this->convertDateTimeValue( $value );
                $date = new DateTime( $value, $this->getLocalTimeZone() );
                $lower = (int)$date->format( 'U' );
                $date->modify( '+1 day' );
                $upper = (int)$date->format( 'U' );
                return $this->makeDateCondition( $expression, $type, $lower, $upper, '%d' );

            default:
                if ( isset( $this->attributes[ $column ] ) ) {
                    $attribute = $this->attributes[ $column ];
                    $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );

                    switch ( $info->getType() ) {
                        case 'TEXT':
                        case 'ENUM':
                        case 'USER':
                            $value = $this->convertUserValue( $value );
                            if ( $value == '' )
                                return $this->makeNullCondition( "COALESCE( $expression, '' )", $type );
                            return $this->makeStringCondition( "UPPER( COALESCE( $expression, '' ) )", $type, mb_strtoupper( $value ) );

                        case 'NUMERIC':
                            return $this->makeNumericCondition( $this->connection->castExpression( $expression, 'f' ), $type, (float)$value, '%f' );

                        case 'DATETIME':
                            $value = $this->convertDateTimeValue( $value );
                            $utcTimeZone = new DateTimeZone( 'UTC' );
                            if ( $info->getMetadata( 'local', 0 ) ) {
                                $localTimeZone = $this->getLocalTimeZone();
                                $date = new DateTime( $value, $localTimeZone );
                                $date->setTimezone( $utcTimeZone );
                                $lower = $date->format( 'Y-m-d H:i' );
                                $date->setTimezone( $localTimeZone );
                                $date->modify( '+1 day' );
                                $date->setTimezone( $utcTimeZone );
                                $upper = $date->format( 'Y-m-d H:i' );
                            } else {
                                $date = new DateTime( $value, $utcTimeZone );
                                $lower = $date->format( 'Y-m-d H:i' );
                                $date->modify( '+1 day' );
                                $upper = $date->format( 'Y-m-d H:i' );
                            }
                            return $this->makeDateCondition( $this->connection->castExpression( $expression, 't' ), $type, $lower, $upper, '%t' );

                        default:
                            throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                    }
                }
                throw new System_Core_Exception( 'Invalid column' );
        }
    }

    private function convertUserValue( $value )
    {
        if ( mb_substr( $value, 0, 4 ) == '[Me]' ) {
            $principal = System_Api_Principal::getCurrent();
            return $principal->getUserName();
        }

        return $value;
    }

    private function convertDateTimeValue( $value )
    {
        if ( mb_substr( $value, 0, 7 ) == '[Today]' ) {
            $date = new DateTime();
            $date->setTimezone( $this->getLocalTimeZone() );

            $offset = mb_substr( $value, 7 );
            if ( $offset != '' )
                $date->modify( $offset . ' days' );

            return $date->format( 'Y-m-d' );
        }

        return $value;
    }

    private function makeNullCondition( $expression, $type )
    {
        switch ( $type ) {
            case 'EQ':
            case 'CON':
            case 'BEG':
            case 'END':
            case 'IN':
                return "$expression IS NULL";
            case 'NEQ':
                return "$expression IS NOT NULL";
            default:
                throw new System_Core_Exception( 'Invalid operator' );
        }
    }

    private function makeStringCondition( $expression, $type, $value )
    {
        switch ( $type ) {
            case 'EQ':
                $this->arguments[] = $value;
                return "$expression = %s";
            case 'NEQ':
                $this->arguments[] = $value;
                return "$expression <> %s";
            case 'CON':
                $this->arguments[] = '%' . $this->escapeLike( $value ) . '%';
                return "$expression LIKE %s ESCAPE '!'";
            case 'BEG':
                $this->arguments[] = $this->escapeLike( $value ) . '%';
                return "$expression LIKE %s ESCAPE '!'";
            case 'END':
                $this->arguments[] = '%' . $this->escapeLike( $value );
                return "$expression LIKE %s ESCAPE '!'";
            case 'IN':
                $items = explode( ', ', $value );
                if ( count( $items ) >= 2 ) {
                    $this->arguments[] = $items;
                    return "$expression IN ( %%s )";
                } else {
                    $this->arguments[] = $value;
                    return "$expression = %s";
                }
            default:
                throw new System_Core_Exception( 'Invalid operator' );
        }
    }

    private function makeNumericCondition( $expression, $type, $value, $placeholder )
    {
        $this->arguments[] = $value;

        switch ( $type ) {
            case 'EQ':
                return "$expression = $placeholder";
            case 'NEQ':
                return "$expression <> $placeholder";
            case 'GT':
                return "$expression > $placeholder";
            case 'LT':
                return "$expression < $placeholder";
            case 'GTE':
                return "$expression >= $placeholder";
            case 'LTE':
                return "$expression <= $placeholder";
            default:
                throw new System_Core_Exception( 'Invalid operator' );
        }
    }

    private function makeDateCondition( $expression, $type, $lower, $upper, $placeholder )
    {
        switch ( $type ) {
            case 'EQ':
                $this->arguments[] = $lower;
                $this->arguments[] = $upper;
                return "$expression >= $placeholder AND $expression < $placeholder";
            case 'NEQ':
                $this->arguments[] = $lower;
                $this->arguments[] = $upper;
                return "( $expression < $placeholder OR $expression >= $placeholder )";
            case 'GT':
                $this->arguments[] = $upper;
                return "$expression >= $placeholder";
            case 'LT':
                $this->arguments[] = $lower;
                return "$expression < $placeholder";
            case 'GTE':
                $this->arguments[] = $lower;
                return "$expression >= $placeholder";
            case 'LTE':
                $this->arguments[] = $upper;
                return "$expression < $placeholder";
            default:
                throw new System_Core_Exception( 'Invalid operator' );
        }
    }

    private function escapeLike( $value )
    {
        return str_replace( array( '!', '%', '_', '[', ']' ), array( '!!', '!%', '!_', '![', '!]' ), $value );
    }

    private function getLocalTimeZone()
    {
        return new DateTimeZone( $this->locale->getSetting( 'time_zone' ) );
    }
}
