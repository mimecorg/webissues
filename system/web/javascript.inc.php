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
* Class for interfacing with JavaScript controls.
*/
class System_Web_JavaScript extends System_Web_Base
{
    private $scriptFiles = array(
        'blockui' => 'jquery.blockui.js',
    );

    private $view = null;

    /**
    * Constructor.
    * @param $view The view to attach the JavaScript to.
    */
    public function __construct( $view )
    {
        parent::__construct();

        $this->view = $view;
    }

    /**
    * Register blocking the UI when a button is clicked.
    * @param $triggerSelector The jQuery selector of the submit button.
    * @param $messageSelector The jQuery selector of the message box to display.
    */
    public function registerBlockUI( $triggerSelector, $messageSelector )
    {
        $this->registerScripts( array( 'blockui' ) );

        $this->registerCode( "
            $( '$triggerSelector' ).click( function() {
                $.blockUI( {
                    message: $( '$messageSelector' )
                } );
            } );" );
    }

    private function registerScripts( $scripts )
    {
        foreach ( $scripts as $file )
            $this->view->mergeSlotItem( 'script_files', '/common/js/' . $this->scriptFiles[ $file ] );
    }

    private function registerCode( $code )
    {
        $this->view->appendSlotItem( 'inline_code', $code );
    }
}
