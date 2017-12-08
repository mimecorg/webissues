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

class Common_Users_Register extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'self_register' ) != 1 || $serverManager->getSetting( 'email_engine' ) == null )
            throw new System_Api_Error( System_Api_Error::AccessDenied );

        $this->autoApprove = $serverManager->getSetting( 'register_auto_approve' ) == 1;

        $this->view->setDecoratorClass( 'Common_FixedBlock' );
        $this->view->setSlot( 'page_title', $this->tr( 'Register New Account' ) );

        if ( System_Api_Principal::getCurrent()->isAuthenticated() ) {
            $sessionManager = new System_Api_SessionManager();
            $sessionManager->logout();
        }

        $this->form = new System_Web_Form( 'register', $this );
        $this->form->addViewState( 'page', 'register' );
        $this->form->addField( 'userName' );
        $this->form->addField( 'login' );
        $this->form->addField( 'password' );
        $this->form->addField( 'passwordConfirm' );
        $this->form->addField( 'email' );

        if ( $this->form->loadForm() ) {
            if ( $this->form->isSubmittedWith( 'cancel' ) || $this->form->isSubmittedWith( 'ok' ) ) {
                if ( $this->request->isRelativePathUnder( '/mobile' ) )
                    $this->response->redirect( '/mobile/index.php' );
                else
                    $this->response->redirect( '/index.php' );
            }

            $this->initializeRules();
            $this->validate();

            if ( $this->form->isSubmittedWith( 'register' ) && !$this->form->hasErrors() ) {
                $this->register();
                if ( !$this->form->hasErrors() )
                    $this->page = 'registered';
            }
        } else {
            $key = $this->request->getQueryString( 'key' );
            if ( $key != null ) {
                if ( $this->autoApprove ) {
                    $this->approve( $key );
                    $this->page = 'approved';
                } else {
                    $this->activate( $key );
                    $this->page = 'activated';
                }
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

        if ( $this->page == 'register' ) {
            $this->form->addTextRule( 'userName', System_Const::NameMaxLength );
            $this->form->addTextRule( 'login', System_Const::LoginMaxLength );
            $this->form->addTextRule( 'password', System_Const::PasswordMaxLength );
            $this->form->addTextRule( 'passwordConfirm', System_Const::PasswordMaxLength );
            $this->form->addPasswordRule( 'passwordConfirm', 'password' );
            $this->form->addTextRule( 'email', System_Const::ValueMaxLength );
        }
    }

    private function validate()
    {
        $this->form->validate();

        if ( $this->page == 'register' && !$this->form->hasErrors() ) {
            $validator = new System_Api_Validator();
            try {
                $validator->checkEmailAddress( $this->email );
            } catch ( System_Api_Error $ex ) {
                $this->form->getErrorHelper()->handleError( 'email', $ex );
            }
        }
    }

    private function register()
    {
        $registrationManager = new System_Api_RegistrationManager();
        try {
            $key = $registrationManager->generateKey();
            $registrationManager->addRequest( $this->login, $this->userName, $this->password, $this->email, $key );

            $register = array( 'user_login' => $this->login, 'user_name' => $this->userName, 'user_email' => $this->email, 'request_key' => $key );

            $mail = System_Web_Component::createComponent( 'Common_Mail_Register', null, $register );
            $body = $mail->run();
            $subject = $mail->getView()->getSlot( 'subject' );

            $engine = new System_Mail_Engine();
            $engine->loadSettings();
            $engine->send( $this->email, $this->userName, $subject, $body );
        } catch ( System_Api_Error $ex ) {
            $this->form->getErrorHelper()->handleError( $ex->getMessage() == System_Api_Error::EmailAlreadyExists ? 'email' : 'userName', $ex );
        }
    }

    private function activate( $key )
    {
        $registrationManager = new System_Api_RegistrationManager();
        $request = $registrationManager->getRequestWithKey( $key );
        $registrationManager->activateRequest( $request );
    }

    private function approve( $key )
    {
        $registrationManager = new System_Api_RegistrationManager();
        $request = $registrationManager->getRequestWithKey( $key );
        $registrationManager->approveRequest( $request );
    }
}
