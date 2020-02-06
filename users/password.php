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

require_once( '../system/bootstrap.inc.php' );

class Users_Password extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'email_engine' ) == null )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        if ( System_Api_Principal::getCurrent()->isAuthenticated() ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->logout();

            $translator = System_Core_Application::getInstance()->getTranslator();
            $translator->setLanguage( System_Core_Translator::UserLanguage, null );
        }

        $this->view->setDecoratorClass( 'Common_Window' );
        $this->view->setSlot( 'page_title', $this->t( 'cmd.ResetPassword' ) );
        $this->view->setSlot( 'window_size', 'small' );

        $this->form = new System_Web_Form( 'password', $this );
        $this->form->addViewState( 'page', 'email' );
        $this->form->addViewState( 'userLogin', '' );
        $this->form->addField( 'email' );
        $this->form->addField( 'password' );
        $this->form->addField( 'passwordConfirm' );

        if ( $this->form->loadForm() ) {
            if ( $this->form->isSubmittedWith( 'cancel' ) || $this->form->isSubmittedWith( 'ok' ) && ( $this->page == 'sent' || $this->page == 'done' ) )
                $this->response->redirect( '/index.php' );

            $this->initializeRules();
            $this->form->validate();

            if ( $this->form->isSubmittedWith( 'ok' ) && !$this->form->hasErrors() ) {
                if ( $this->page == 'email' ) {
                    $this->send();
                    if ( !$this->form->hasErrors() )
                        $this->page = 'sent';
                } else {
                    $key = $this->request->getQueryString( 'key' );
                    if ( $key != null ) {
                        $this->setPassword( $key );
                        $this->page = 'done';
                    }
                }
            }
        } else {
            $key = $this->request->getQueryString( 'key' );
            if ( $key != null ) {
                $this->reset( $key );
                $this->page = 'reset';
            }
        }

        $this->initializeRules();
    }

    private function initializeRules()
    {
        if ( $this->rules == $this->page )
            return;

        $this->rules = $this->page;

        $this->form->clearRules();

        if ( $this->page == 'email' ) {
            $this->form->addTextRule( 'email', System_Const::ValueMaxLength );
            $this->form->addEmailRule( 'email' );
        } else if ( $this->page == 'reset' ) {
            $this->form->addTextRule( 'password', System_Const::PasswordMaxLength );
            $this->form->addTextRule( 'passwordConfirm', System_Const::PasswordMaxLength );
            $this->form->addPasswordRule( 'passwordConfirm', 'password' );
        }
    }

    private function send()
    {
        try {
            $userManager = new System_Api_UserManager();
            $user = $userManager->getUserByEmail( $this->email );

            $keyGenerator = new System_Api_KeyGenerator();
            $key = $keyGenerator->generateKey( System_Api_KeyGenerator::PasswordReset );

            $userManager->setPasswordResetKey( $user, $key );

            $data = array( 'user_login' => $user[ 'user_login' ], 'user_name' => $user[ 'user_name' ], 'user_email' => $user[ 'user_email' ], 'reset_key' => $key );

            $helper = new System_Mail_Helper();
            $helper->send( $user[ 'user_email' ], $user[ 'user_name' ], $user[ 'user_language' ], 'Common_Mail_ResetPassword', $data );
        } catch ( System_Api_Error $ex ) {
            if ( $ex->getMessage() == System_Api_Error::UnknownUser )
                $this->form->setError( 'email', $this->t( 'error.NoUserWithThisEmail' ) );
            else
                $this->form->getErrorHelper()->handleError( 'email', $ex );
        }
    }

    private function reset( $key )
    {
        $userManager = new System_Api_UserManager();
        $user = $userManager->getUserWithResetKey( $key );
        $this->userLogin = $user[ 'user_login' ];
    }

    private function setPassword( $key )
    {
        $userManager = new System_Api_UserManager();
        $user = $userManager->getUserWithResetKey( $key );
        $userManager->resetPassword( $user, $this->password );
    }
}

System_Bootstrap::run( 'Common_Application', 'Users_Password' );
