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

class Common_Users_Login extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_MessageBlock' );
        $this->view->setSlot( 'page_title', $this->tr( 'Log in to WebIssues' ) );

        if ( System_Api_Principal::getCurrent()->isAuthenticated() ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->logout();
        }

        $this->form = new System_Web_Form( 'login', $this );
        $this->form->addViewState( 'page', 'login' );
        $this->form->addPersistentField( 'login' );
        $this->form->addPersistentField( 'password' );
        $this->form->addField( 'newPassword' );
        $this->form->addField( 'newPasswordConfirm' );

        if ( $this->form->loadForm() ) {
            if ( $this->form->isSubmittedWith( 'cancel' ) ) {
                $this->page = 'login';
                $this->login = '';
                $this->password = '';
            } else {
                $this->initializeRules();
                $this->form->validate();

                if ( $this->form->isSubmittedWith( 'login' ) && !$this->form->hasErrors() ) {
                    if ( $this->submit() ) {
                        $url = $this->request->getQueryString( 'url' );
                        if ( $url == null || $url[ 0 ] != '/' ) {
                            if ( $this->request->isRelativePathUnder( '/mobile' ) )
                                $url = '/mobile/client/index.php';
                            else
                                $url = '/client/index.php';
                        }
                        $this->response->redirect( $url );
                    }
                }
            }
        }

        $this->initializeRules();

        $this->toolBar = new System_Web_Toolbar();
        $this->toolBar->setFilterParameters( array() );

        if ( !$this->request->isRelativePathUnder( '/mobile' ) ) {
            $serverManager = new System_Api_ServerManager();
        
            if ( $serverManager->getSetting( 'anonymous_access' ) == 1 )
                $this->toolBar->addFixedCommand( '/client/index.php', '/common/images/user-disabled-16.png', $this->tr( 'Anonymous Access' ) );

            if ( $serverManager->getSetting( 'self_register' ) == 1 && $serverManager->getSetting( 'email_engine' ) != null )
                $this->toolBar->addFixedCommand( '/register.php', '/common/images/user-new-16.png', $this->tr( 'Register New Account' ) );
        }
    }

    private function initializeRules()
    {
        if ( $this->rules == $this->page )
            return;

        $this->rules = $this->page;

        $this->form->clearRules();

        $this->form->addTextRule( 'login', System_Const::LoginMaxLength );
        $this->form->addTextRule( 'password', System_Const::PasswordMaxLength );

        if ( $this->page == 'password' ) {
            $this->form->addTextRule( 'newPassword', System_Const::PasswordMaxLength );
            $this->form->addTextRule( 'newPasswordConfirm', System_Const::PasswordMaxLength );
            $this->form->addPasswordRule( 'newPasswordConfirm', 'newPassword' );
        }
    }

    private function submit()
    {
        $sessionManager = new System_Api_SessionManager();
        try {
            $sessionManager->login( $this->login, $this->password, $this->newPassword );
            return true;
        } catch ( System_Api_Error $ex ) {
            if ( $ex->getMessage() == System_Api_Error::MustChangePassword )
                $this->page = 'password';
            else if ( $this->page == 'password' )
                $this->form->getErrorHelper()->handleError( 'newPassword', $ex );
            else
                $this->form->getErrorHelper()->handleError( 'password', $ex );
            return false;
        }
    }
}
