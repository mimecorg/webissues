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

require_once( '../../../../system/bootstrap.inc.php' );

class Server_Api_Settings_Email_Test
{
    public $access = 'admin';

    public $params = array(
        'emailFrom' => array( 'type' => 'string', 'required' => true ),
        'smtpServer' => array( 'type' => 'string', 'required' => true ),
        'smtpPort' => array( 'type' => 'int', 'required' => true ),
        'smtpEncryption' => 'string',
        'smtpUser' => 'string',
        'smtpPassword' => 'string'
    );

    public function run( $emailFrom, $smtpServer, $smtpPort, $smtpEncryption, $smtpUser, $smtpPassword )
    {
        $validator = new System_Api_Validator();
        $validator->checkString( $emailFrom, System_Const::ValueMaxLength );
        $validator->checkEmailAddress( $emailFrom );
        $validator->checkString( $smtpServer, System_Const::ValueMaxLength );
        $validator->checkIntegerValue( $smtpPort, 1, 65535 );
        $validator->checkString( $smtpEncryption, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkEncryption( $smtpEncryption );
        $validator->checkString( $smtpUser, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );
        $validator->checkString( $smtpPassword, System_Const::ValueMaxLength, System_Api_Validator::AllowEmpty );

        $settings = array(
            'email_engine' => 'smtp',
            'email_from' => $emailFrom,
            'smtp_server' => $smtpServer,
            'smtp_port' => $smtpPort,
            'smtp_encryption' => $smtpEncryption,
            'smtp_user' => $smtpUser,
            'smtp_password' => $smtpPassword
        );

        $mail = System_Web_Component::createComponent( 'Common_Mail_TestMessage' );
        $body = $mail->run();
        $subject = $mail->getView()->getSlot( 'subject' );

        try {
            $engine = new System_Mail_Engine();
            $engine->setSettings( $settings );
            $engine->send( $emailFrom, '', $subject, $body );
            $status = true;
        } catch ( PHPMailer\PHPMailer\Exception $ex ) {
            $status = false;
        }

        $result[ 'status' ] = $status;

        return $result;
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Settings_Email_Test' );
