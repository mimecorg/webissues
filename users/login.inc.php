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

class Users_Login extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        if ( System_Api_Principal::getCurrent()->isAuthenticated() ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->logout();

            $translator = System_Core_Application::getInstance()->getTranslator();
            $translator->setLanguage( System_Core_Translator::UserLanguage, null );
        }

        $this->view->setDecoratorClass( 'Common_Window' );
        $this->view->setSlot( 'page_title', $this->t( 'title.LogInToWebIssues' ) );
        $this->view->setSlot( 'window_size', 'small' );

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
                        if ( $url == null || $url[ 0 ] != '/' )
                            $url = '/client/index.php';
                        $this->response->redirect( $url );
                    }
                }
            }
        }

        $this->initializeRules();

        $serverManager = new System_Api_ServerManager();

        $this->anonymousAccess = $serverManager->getSetting( 'anonymous_access' ) == 1;
        $this->selfRegister = $serverManager->getSetting( 'self_register' ) == 1 && $serverManager->getSetting( 'email_engine' ) != null;
        $this->resetPassword = $serverManager->getSetting( 'email_engine' ) != null;
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
