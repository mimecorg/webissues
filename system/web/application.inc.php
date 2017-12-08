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
* Web application using the MVC pattern.
*
* A web application consists of components (controllers in the MVC
* pattern) and associated views. A component can be executed, performing
* appropriate actions, and the view can be rendered as HTML content.
* Data can be passed from the component to its associated view.
*
* Each entry script executes one component called the page component.
* Components can be nested in other components and/or decorated
* by other components.
*
* @see System_Web_Component, System_Web_View
*/
class System_Web_Application extends System_Core_Application
{
    protected $pageClass = null;

    protected $page = null;

    /**
    * Constructor.
    * The instance of the application is normally created by calling
    * System_Bootstrap::run() with appropriate parameters.
    * @param $pageClass Name of the page component class, inheriting
    * System_Web_Component, to be created and executed.
    */
    protected function __construct( $pageClass )
    {
        parent::__construct();
        $this->pageClass = $pageClass;
    }

    /**
    * Return the page component.
    */
    public function getPage()
    {
        return $this->page;
    }

    /**
    * Create and execute the page component.
    * The view associated with the component is rendered as the content
    * of the response.
    */
    protected function execute()
    {
        if ( $this->debug->checkLevel( DEBUG_REQUESTS ) )
            $this->debug->write( 'Executing page: ' . $this->pageClass . "\n" );

        $this->page = System_Web_Component::createComponent( $this->pageClass );
        $this->preparePage();

        $content = $this->page->run();

        $this->response->setContentType( 'text/html; charset=UTF-8' );
        $this->response->setContent( $content );
    }

    /**
    * Initialize the page component.
    * This method is called before the component is executed and can be
    * reimplemented in derived classes to initialize default settings,
    * perform access authorization, etc.
    */
    protected function preparePage()
    {
    }
}
