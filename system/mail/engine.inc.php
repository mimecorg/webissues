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

function PHPMailerAutoload( $className )
{
    $path = WI_ROOT_DIR . '/system/mail/class.' . strtolower( $className ) . '.php';
    if ( is_readable( $path ) )
        include_once( $path );
}

spl_autoload_register( 'PHPMailerAutoload' );

class System_Mail_PHPMailer extends PHPMailer
{
    public function __construct()
    {
        parent::__construct( true );
    }

    /**
     * Do not validate the address becaue WebIssues already handles it.
     */
    public static function validateAddress( $address, $patternselect = 'auto' )
    {
        return true;
    }
};

/**
* Engine for sending email messages using PHPMailer.
*/
class System_Mail_Engine
{
    private $mailer = null;

    private $replyTo = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        require_once( WI_ROOT_DIR . '/system/mail/class.phpmailer.php' );

        $this->mailer = new System_Mail_PHPMailer();

        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'quoted-printable';
        $this->mailer->LE = "\n";

        $translator = System_Core_Application::getInstance()->getTranslator();
        $language = $translator->getLanguage( System_Core_Translator::SystemLanguage );

        if ( $language != null && $language != 'en_US' )
            $this->mailer->setLanguage( $language, WI_ROOT_DIR . '/system/mail/language/' );
    }

    /**
    * Load e-mail settings from the database.
    */
    public function loadSettings()
    {
        $serverManager = new System_Api_ServerManager();
        $this->setSettings( $serverManager->getSettings() );
    }

    /**
    * Initialize PHPMailer with given settings.
    * @param $settings Array of settings to use.
    */
    public function setSettings( $settings )
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $this->mailer->setFrom( $settings[ 'email_from' ], $server[ 'server_name' ] );

        $this->replyTo = $settings[ 'email_from' ];

        $engine = $settings[ 'email_engine' ];

        switch ( $engine ) {
            case 'smtp':
                $this->mailer->isSMTP();
                $this->mailer->Host = $settings[ 'smtp_server' ];
                $this->mailer->Port = $settings[ 'smtp_port' ];
                if ( !empty( $settings[ 'smtp_encryption' ] ) )
                    $this->mailer->SMTPSecure = $settings[ 'smtp_encryption' ];
                if ( !empty( $settings[ 'smtp_user' ] ) ) {
                    $this->mailer->SMTPAuth = true;
                    $this->mailer->Username = $settings[ 'smtp_user' ];
                    $this->mailer->Password = $settings[ 'smtp_password' ];
                }
                $this->mailer->SMTPKeepAlive = true;
                break;

            case 'standard':
                $this->mailer->isMail();
                break;

            default:
                throw new System_Core_Exception( "Unknown email engine '$engine'" );
        }
    }

    /**
    * Change the Reply-To address of sent messages.
    */
    public function setReplyTo( $address, $name )
    {
        if ( $this->replyTo != $address ) {
            $this->mailer->clearReplyTos();
            $this->mailer->addReplyTo( $address, $name );

            $this->replyTo = $address;
        }
    }

    /**
    * Send an email.
    * @param $address Address of the recipient.
    * @param $name Name of the recipient.
    * @param $subject Subject of the email.
    * @param $body Body of the email.
    */
    public function send( $address, $name, $subject, $body )
    {
        $this->mailer->clearAddresses();
        $this->mailer->addAddress( $address, $name );

        $this->mailer->isHTML();
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;

        $this->mailer->send();
    }

    /**
    * Close the connection to the server.
    */
    public function close()
    {
        if ( $this->mailer == null )
            return;

        $this->mailer->smtpClose();

        $this->mailer = null;
    }
}
