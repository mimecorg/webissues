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

require_once( '../../system/bootstrap.inc.php' );

class Admin_Setup_Update extends System_Web_Component
{
    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        if ( $this->checkAccess() ) {
            $this->form = new System_Web_Form( 'update', $this );
            $this->form->addViewState( 'page', 'login' );
            $this->form->addPersistentField( 'login' );
            $this->form->addPersistentField( 'password' );

            $this->form->addTextRule( 'login', System_Const::LoginMaxLength );
            $this->form->addTextRule( 'password', System_Const::PasswordMaxLength );

            if ( $this->form->loadForm() )
                $this->processForm();

            $this->showUpdate = $this->page == 'update';
            $this->showBack = $this->page != 'login';
            $this->showNext = !$this->showUpdate;
        }

        $this->view->setDecoratorClass( 'Common_FixedBlock' );
        $this->view->setSlot( 'page_title', $this->tr( 'Server Update' ) );
        $this->view->setSlot( 'header', $this->tr( 'Update your WebIssues Server' ) );

        if ( $this->showUpdate ) {
            $javaScript = new System_Web_JavaScript( $this->view );
            $javaScript->registerBlockUI( $this->form->getSubmitSelector( 'update' ), '#progress' );
        }
    }

    private function checkAccess()
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $this->version = $server[ 'db_version' ];

        if ( $this->version == WI_DATABASE_VERSION ) {
            $this->page = 'up_to_date';
            return false;
        }

        return true;
    }

    private function processForm()
    {
        $this->form->validate();

        if ( $this->form->hasErrors() )
            return;

        $sessionManager = new System_Api_SessionManager();
        try {
            $sessionManager->checkAccess( $this->login, $this->password, System_Api_SessionManager::RequireAdministrator );
        } catch ( System_Api_Error $ex ) {
            $this->form->getErrorHelper()->handleError( 'password', $ex );
            return;
        }

        if ( $this->form->isSubmittedWith( 'back' ) ) {
            switch ( $this->page ) {
                case 'update':
                    $this->page = 'login';
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'next' ) && !$this->form->hasErrors() ) {
            switch ( $this->page ) {
                case 'login':
                    $this->page = 'update';
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'update' ) && !$this->form->hasErrors() ) {
            if ( $this->updateDatabase() ) {
                $this->startSession();
                $this->page = 'completed';
            }
        }
    }

    private function updateDatabase()
    {
        set_time_limit( 300 );

        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $updater = new Admin_Setup_Updater( $connection );

            $updater->updateDatabase( $this->version );

            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information,
                $eventLog->tr( 'Updated database to version %1', null, WI_DATABASE_VERSION ) );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'failed';
            $this->error = $e->__toString();

            return false;
        }
    }

    private function startSession()
    {
        System_Core_Application::getInstance()->initializeSession();

        $sessionManager = new System_Api_SessionManager();
        $sessionManager->loginAs( $this->login );
    }
}

System_Bootstrap::run( 'Common_Application', 'Admin_Setup_Update' );
