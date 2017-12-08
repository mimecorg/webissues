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
* Base class for controllers in the MVC pattern.
*
* A component can be a single web page, a reusable part that can be inserted
* into other components or a decorator for other components.
*
* Each component must implement the execute() method to perform
* appropriate actions, retrieve information and pass it to the view.
*
* The view associated with each component executes a template file which
* contains both HTML markup and PHP statements. The template file usually
* has the same name as the file implementing the component but with .html.php
* extension.
*
* All dynamic properties of the component are automatically passed to
* the view.
*
* @see System_Web_View
*/
abstract class System_Web_Component extends System_Web_Base
{
    protected $view = null;

    private $data = array();

    /**
    * Constructor.
    */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
    * Create a component of the given class with an associated view.
    * @param $class Name of the component class.
    * @param $template Path of the view template file (without extension)
    * or @c null to automatically determine file path from class name.
    * @param $parameter Optional parameter passed to the component's
    * constructor.
    * @param $parentView Optional parent view.
    * @return The component object.
    */
    public static function createComponent( $class, $template = null, $parameter = null, $parentView = null )
    {
        $component = new $class( $parameter );

        if ( !$template )
            $template = str_replace( '_', '/', strtolower( $class ) );

        $component->createView( $template, $class, $parentView );

        return $component;
    }

    /**
    * Return the view associated with the component.
    */
    public function getView()
    {
        return $this->view;
    }

    /**
    * Return all dynamic properties as an array.
    */
    public function getData()
    {
        return $this->data;
    }

    /**
    * Execute the component and rendered the view.
    * @return The rendered content of the associated view.
    */
    public function run()
    {
        $this->execute();
        $this->prepareView();
        return $this->view->render();
    }

    /**
    * Overloading method for handling dynamic properties.
    */
    public function __set( $key, $value )
    {
        $this->data[ $key ] = $value;
    }

    /**
    * Overloading method for handling dynamic properties.
    */
    public function &__get( $key )
    {
        return $this->data[ $key ];
    }

    /**
    * Overloading method for handling dynamic properties.
    */
    public function __isset( $key )
    {
        return isset( $this->data[ $key ] );
    }

    /**
    * Overloading method for handling dynamic properties.
    */
    public function __unset( $key )
    {
        unset( $this->data[ $key ] );
    }

    /**
    * Create the view for the component.
    * @param $template Path of the view template file (without extension).
    * @param $class Name of the component class.
    * @param $parentView Optional parent view.
    */
    protected function createView( $template, $class, $parentView = null )
    {
        $this->view = new System_Web_View( $template, $class, $parentView );
    }

    /**
    * Prepare the view for rendering.
    * This method is called after executing the component but before
    * the view is rendered. The dynamic properties are passed to the view.
    */
    protected function prepareView()
    {
        $this->view->setData( $this->data );
    }

    /**
    * Perform actions related to the component.
    * Abstract method which must be implemented by all components.
    */
    protected abstract function execute();
}
