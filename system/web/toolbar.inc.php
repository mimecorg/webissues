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
* Helper class for rendering a toolbar.
*
* A toolbar constists of a number of commands which can be shown or hidden
* depending on the current selection (usually in a System_Web_Grid). Additional
* query parameters can be passed based on selection. Visibility of commands can
* be controlled using row classes. The toolbar can be dynamically controlled using
* System_Web_JavaScript::registerSelection().
*/
class System_Web_ToolBar extends System_Web_Base
{
    private $rowParam = 'id';
    private $parentParam = null;
    private $rowId = null;
    private $parentId = null;
    private $classes = array();
    private $filterParams = null;
    private $commands = array();

    /**
    * Constructor
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Set the names of query parameters used to pass identifiers of the selection.
    * The default is name is 'id' for row identifier and no parent identifier.
    * @param $row Parameter containing the row identifier.
    * @param $parent Optional paramter containing the parent row identifier.
    */
    public function setParameters( $row, $parent = null )
    {
        $this->rowParam = $row;
        $this->parentParam = $parent;
    }

    /**
    * Return the name of the parameter containing the row identifier.
    */
    public function getRowParam()
    {
        return $this->rowParam;
    }

    /**
    * Return the name of the parameter containing the parent row identifier.
    */
    public function getParentParam()
    {
        return $this->parentParam;
    }

    /**
    * Set the identifiers and custom classes of the currently selected row.
    * @param $rowId Identifier of the row.
    * @param $parentId Optional identifier of the parent row.
    * @param $classes Optional custom classes of the row.
    */
    public function setSelection( $rowId, $parentId = null, $classes = array() )
    {
        $this->rowId = $rowId;
        $this->parentId = $parentId;

        if ( $parentId != 0 ) {
            $classes[] = 'selected';
            if ( $rowId != 0 )
                $classes[] = 'child';
            else
                $classes[] = 'parent';
        } else if ( $rowId != 0 ) {
            $classes[] = 'selected';
        }

        $this->classes = $classes;
    }

    /**
    * Set the array of query parameters to pass from current page to the command.
    * By default all parameters are passed.
    */
    public function setFilterParameters( $params )
    {
        $this->filterParams = $params;
    }

    /**
    * Add a command which does not depend on the selection.
    * @param $url The path part of the URL.
    * @param $image The source of the image.
    * @param $text Text of the label.
    * @param $params Optional additional parameters to be merged with the URL.
    */
    public function addFixedCommand( $url, $image, $text, $params = array() )
    {
        $this->addCommand( $url, $image, $text, array(), false, false, $params );
    }

    /**
    * Add a command visible when any item is selected. The row identifier is passed
    * as a parameter to the URL (or in case of a tree, the parent row identifier).
    * @param $url The path part of the URL.
    * @param $image The source of the image.
    * @param $text Text of the label.
    * @param $conditions Optional additional custom classes that control visibility
    * of the command.
    * @param $params Optional additional parameters to be merged with the URL.
    */
    public function addItemCommand( $url, $image, $text, $conditions = array(), $params = array() )
    {
        $conditions[] = 'selected';
        if ( $this->parentParam != null )
            $this->addCommand( $url, $image, $text, $conditions, false, true, $params );
        else
            $this->addCommand( $url, $image, $text, $conditions, true, false, $params );
    }

    /**
    * Add a command visible when a parent item is selected in a tree. The parent
    * row identifier is passed as a parameter to the URL.
    * @param $url The path part of the URL.
    * @param $image The source of the image.
    * @param $text Text of the label.
    * @param $conditions Optional additional custom classes that control visibility
    * of the command.
    * @param $params Optional additional parameters to be merged with the URL.
    */
    public function addParentCommand( $url, $image, $text, $conditions = array(), $params = array() )
    {
        $conditions[] = 'parent';
        $this->addCommand( $url, $image, $text, $conditions, false, true, $params );
    }

    /**
    * Add a command visible when a child item is selected in a tree. The child row
    * identifier is passed as a parameter to the URL.
    * @param $url The path part of the URL.
    * @param $image The source of the image.
    * @param $text Text of the label.
    * @param $conditions Optional additional custom classes that control visibility
    * of the command.
    * @param $params Optional additional parameters to be merged with the URL.
    */
    public function addChildCommand( $url, $image, $text, $conditions = array(), $params = array() )
    {
        $conditions[] = 'child';
        $this->addCommand( $url, $image, $text, $conditions, true, false, $params );
    }

    private function addCommand( $url, $image, $text, $conditions, $row, $parent, $params )
    {
        $command[ 'url' ] = $url;
        $command[ 'image' ] = $image;
        $command[ 'text' ] = $text;
        $command[ 'conditions' ] = $conditions;
        $command[ 'row' ] = $row;
        $command[ 'parent' ] = $parent;
        $command[ 'params' ] = $params;
        $this->commands[] = $command;
    }

    /**
    * Return the array of commands.
    */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
    * Return @c true if the toolbar contains no commands.
    */
    public function isEmpty()
    {
        return empty( $this->commands );
    }

    /**
    * Render the toolbar as HTML.
    */
    public function render()
    {
        $separator = false;
        $id = 0;

        foreach ( $this->commands as $command ) {
            $params = array();
            if ( $command[ 'row' ] && $this->rowId != 0 )
                $params[ $this->rowParam ] = $this->rowId;
            else
                $params[ $this->rowParam ] = null;
            if ( $this->parentParam != null ) {
                if ( $command[ 'parent' ] && $this->parentId != 0 )
                    $params[ $this->parentParam ] = $this->parentId;
                else 
                    $params[ $this->parentParam ] = null;
            }

            if ( $this->filterParams !== null )
                $url = $this->filterQueryString( $command[ 'url' ], $this->filterParams, array_merge( $params, $command[ 'params' ] ) );
            else
                $url = $this->mergeQueryString( $command[ 'url' ], array_merge( $params, $command[ 'params' ] ) );

            $conditions = $command[ 'conditions' ];

            if ( !empty( $conditions ) ) {
                $visible = true;
                foreach ( $conditions as $condition ) {
                    if ( array_search( $condition, $this->classes ) === false )
                        $visible = false;
                }
                echo $this->buildTag( 'span', array( 'id' => 'cmd-' . $id, 'style' => ( $visible ? null : 'display: none' ) ), true );
                $id++;
            }

            if ( $separator )
                echo ' | ';
            else
                $separator = true;

            echo $this->imageAndTextLink( $url, $command[ 'image' ], $command[ 'text' ] );

            if ( !empty( $conditions ) )
                echo "</span>\n";
        }
    }

    /**
    * Render the toolbar as HTML list items.
    */
    public function renderListItems()
    {
        foreach ( $this->commands as $command ) {
            $params = array();
            if ( $command[ 'row' ] && $this->rowId != 0 )
                $params[ $this->rowParam ] = $this->rowId;
            else
                $params[ $this->rowParam ] = null;
            if ( $this->parentParam != null ) {
                if ( $command[ 'parent' ] && $this->parentId != 0 )
                    $params[ $this->parentParam ] = $this->parentId;
                else 
                    $params[ $this->parentParam ] = null;
            }

            if ( $this->filterParams !== null )
                $url = $this->filterQueryString( $command[ 'url' ], $this->filterParams, array_merge( $params, $command[ 'params' ] ) );
            else
                $url = $this->mergeQueryString( $command[ 'url' ], array_merge( $params, $command[ 'params' ] ) );

            echo $this->imageAndTextLinkItem( $url, $command[ 'image' ], $command[ 'text' ] );
        }
    }
}
