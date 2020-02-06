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

class System_Mail_Helper
{
    private $engine;

    public function __construct()
    {
        $this->engine = new System_Mail_Engine();
        $this->engine->loadSettings();
    }

    public function send( $email, $name, $language, $component, $data )
    {
        $translator = System_Core_Application::getInstance()->getTranslator();
        $oldLanguage = $translator->getLanguage( System_Core_Translator::UserLanguage );
        $translator->setLanguage( System_Core_Translator::UserLanguage, $language );

        $mail = System_Web_Component::createComponent( $component, null, $data );
        $body = $mail->run();
        $subject = $mail->getView()->getSlot( 'subject' );

        $translator->setLanguage( System_Core_Translator::UserLanguage, $oldLanguage );

        $this->engine->send( $email, $name, $subject, $body );
    }
};
