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

class Setup_Install extends System_Web_Component
{
    private $rules = null;

    protected function __construct()
    {
        parent::__construct();
    }

    protected function execute()
    {
        if ( $this->checkAccess() ) {
            $locale = new System_Api_Locale();
            $this->languageOptions = $locale->getAvailableLanguages();
            $this->engineOptions = $this->getDatabaseEngines();
            $this->modeOptions = $this->getModeOptions();
            $this->dataOptions = $this->getDataOptions();

            $this->form = new System_Web_Form( 'install', $this );
            $this->form->addViewState( 'page', 'language' );
            $this->form->addPersistentField( 'language', 'en_US' );
            $this->form->addPersistentField( 'engine', key( $this->engineOptions ) );
            $this->form->addPersistentField( 'host', 'localhost' );
            $this->form->addPersistentField( 'database', 'webissues' );
            $this->form->addPersistentField( 'user', 'webissues' );
            $this->form->addPersistentField( 'password' );
            $this->form->addPersistentField( 'mode', 'new' );
            $this->form->addPersistentField( 'prefix' );
            $this->form->addPersistentField( 'serverName' );
            $this->form->addPersistentField( 'adminPassword' );
            $this->form->addPersistentField( 'adminConfirm' );
            $this->form->addPersistentField( 'adminEmail' );
            $this->form->addPersistentField( 'initialData', 'default' );

            if ( $this->form->loadForm() )
                $this->processForm();

            $this->showRefresh = $this->page == 'site_error';
            $this->showInstall = $this->page == 'new_site' || $this->page == 'existing_site';
            $this->showBack = !$this->showRefresh && $this->page != 'language';
            $this->showNext = !$this->showRefresh && !$this->showInstall;

            $this->initializeRules();
        }

        $this->view->setDecoratorClass( 'Common_Window' );

        switch ( $this->page ) {
            case 'config_exists':
                $this->view->setSlot( 'page_title', $this->t( 'title.ServerAlreadyConfigured' ) );
                $this->view->setSlot( 'window_size', 'small' );
                break;

            case 'language':
                $this->view->setSlot( 'page_title', $this->t( 'title.SelectLanguage' ) );
                $this->view->setSlot( 'window_size', 'small' );
                break;

            case 'site_error':
                $this->view->setSlot( 'page_title', $this->t( 'title.ConfigurationError' ) );
                $this->view->setSlot( 'window_size', 'small' );
                break;

            case 'connection':
                $this->view->setSlot( 'page_title', $this->t( 'title.DatabaseConfiguration' ) );
                break;

            case 'server':
                $this->view->setSlot( 'page_title', $this->t( 'title.ServerConfiguration' ) );
                break;

            case 'new_site':
            case 'existing_site':
                $this->view->setSlot( 'page_title', $this->t( 'title.ConfirmInstallation' ) );
                break;

            case 'completed':
                $this->view->setSlot( 'page_title', $this->t( 'title.InstallationCompleted' ) );
                $this->view->setSlot( 'window_size', 'small' );
                break;

            case 'failed':
                $this->view->setSlot( 'page_title', $this->t( 'title.InstallationFailed' ) );
                break;
        }
    }

    private function checkAccess()
    {
        if ( System_Core_Application::getInstance()->getSite()->isConfigLoaded() ) {
            $this->page = 'config_exists';
            return false;
        }

        return true;
    }

    private function processForm()
    {
        $this->initializeRules();
        $this->form->validate();

        if ( !$this->setupLanguage() ) {
            $this->page = 'language';
            return;
        }

        if ( $this->form->isSubmittedWith( 'back' ) ) {
            switch ( $this->page ) {
                case 'connection':
                    $this->page = 'language';
                    break;
                case 'server':
                case 'existing_site':
                    $this->page = 'connection';
                    break;
                case 'new_site':
                    $this->page = 'server';
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'next' ) && !$this->form->hasErrors() ) {
            switch ( $this->page ) {
                case 'language':
                    if ( $this->validateSite() )
                        $this->page = 'connection';
                    else
                        $this->page = 'site_error';
                    break;
                case 'connection':
                    if ( $this->openConnection() )
                        $this->testConnection();
                    break;
                case 'server':
                    if ( $this->openConnection() )
                        $this->page = 'new_site';
                    break;
            }
        }

        if ( $this->form->isSubmittedWith( 'refresh' ) ) {
            if ( $this->validateSite() )
                $this->page = 'connection';
        }

        if ( $this->form->isSubmittedWith( 'install' ) && !$this->form->hasErrors() ) {
            switch ( $this->page ) {
                case 'new_site':
                    if ( $this->openConnection() ) {
                        if ( $this->installDatabase() ) {
                            if ( $this->writeSiteConfiguration() ) {
                                $this->startSession();
                                $this->page = 'completed';
                            }
                        }
                    }
                    break;
                case 'existing_site':
                    if ( $this->openConnection() ) {
                        if ( $this->updateDatabase() ) {
                            if ( $this->writeSiteConfiguration() ) {
                                $this->startSession();
                                $this->page = 'completed';
                            }
                        }
                    }
                    break;
            }
        }
    }

    private function initializeRules()
    {
        if ( $this->rules == $this->page )
            return;

        $this->rules = $this->page;

        $this->form->clearRules();

        switch ( $this->page ) {
            case 'language':
                $this->form->addItemsRule( 'language', $this->languageOptions );
                break;

            case 'connection':
                $this->form->addItemsRule( 'engine', $this->engineOptions );
                $this->form->addTextRule( 'host', System_Const::ValueMaxLength );
                $this->form->addTextRule( 'database', System_Const::ValueMaxLength );
                $this->form->addTextRule( 'user', System_Const::ValueMaxLength, System_Api_Parser::AllowEmpty );
                $this->form->addTextRule( 'password', System_Const::ValueMaxLength, System_Api_Parser::AllowEmpty );
                $this->form->addItemsRule( 'mode', $this->modeOptions );
                $this->form->addTextRule( 'prefix', System_Const::NameMaxLength, System_Api_Parser::AllowEmpty );
                break;

            case 'server':
                $this->form->addTextRule( 'serverName', System_Const::NameMaxLength );
                $this->form->addTextRule( 'adminPassword', System_Const::PasswordMaxLength );
                $this->form->addTextRule( 'adminConfirm', System_Const::PasswordMaxLength );
                $this->form->addPasswordRule( 'adminConfirm', 'adminPassword' );
                $this->form->addTextRule( 'adminEmail', System_Const::ValueMaxLength, System_Api_Parser::AllowEmpty );
                $this->form->addEmailRule( 'adminEmail' );
                $this->form->addItemsRule( 'initialData', $this->dataOptions );
                break;
        }
    }

    private function setupLanguage()
    {
        if ( !empty( $this->language ) && isset( $this->languageOptions[ $this->language ] ) ) {
            $translator = System_Core_Application::getInstance()->getTranslator();
            $translator->setLanguage( System_Core_Translator::SystemLanguage, $this->language );
            $translator->setLanguage( System_Core_Translator::UserLanguage, $this->language );

            if ( $this->serverName === null )
                $this->serverName = $this->t( 'installer.MyWebIssuesServer' );

            $this->modeOptions = $this->getModeOptions();
            $this->dataOptions = $this->getDataOptions();

            return true;
        }

        return false;
    }

    private function validateSite()
    {
        $site = System_Core_Application::getInstance()->getSite();

        $siteDirectory = $site->getPath( 'site_dir' );

        if ( !System_Core_FileSystem::isDirectoryWritable( $siteDirectory ) ) {
            $this->error = $this->t( 'error.CannotAccessDirectory', array( System_Core_FileSystem::toNativeSeparators( $siteDirectory ) ) );
            return false;
        }

        $storageDirectory = $siteDirectory . '/storage';

        if ( !System_Core_FileSystem::isDirectoryWritable( $storageDirectory ) ) {
            $this->error = $this->t( 'error.CannotAccessDirectory', array( System_Core_FileSystem::toNativeSeparators( $storageDirectory ) ) );
            return false;
        }

        $debugLevel = $site->getConfig( 'debug_level' );
        if ( $debugLevel > 0 ) {
            $debugFile = $site->getPath( 'debug_file' );

            if ( !System_Core_FileSystem::isFileWritable( $debugFile ) ) {
                $this->error = $this->t( 'error.CannotAccessFile', array( System_Core_FileSystem::toNativeSeparators( $debugFile ) ) );
                return false;
            }
        }

        return true;
    }

    private function getDatabaseEngines()
    {
        $engines = array();

        $engines[ 'mysqli' ] = 'MySQL';
        $engines[ 'pgsql' ] = 'PostgreSQL';

        if ( strtoupper( substr( PHP_OS, 0, 3 ) ) == 'WIN' )
            $engines[ 'mssql' ] = 'SQL Server';

        return $engines;
    }

    private function getModeOptions()
    {
        $options = array();

        $options[ 'new' ] = $this->t( 'text.CreateNewTables' );
        $options[ 'existing' ] = $this->t( 'text.UseExistingTables' );

        return $options;
    }

    private function getDataOptions()
    {
        $options = array();

        $options[ '' ] = $this->t( 'text.NoIssueTypes' );
        $options[ 'default' ] = $this->t( 'text.DefaultIssueTypes' );

        return $options;
    }

    private function openConnection()
    {
        if ( !$this->checkEngine() ) {
            $this->page = 'connection';
            return false;
        }

        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $connection->loadEngine( $this->engine );
            $connection->open( $this->host, $this->database, $this->user, $this->password );
            $connection->setPrefix( $this->prefix );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'connection';
            $this->form->setError( 'connection', $this->t( 'error.ConnectionError' ) );

            return false;
        }
    }

    private function testConnection()
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            if ( !$this->checkPrerequisites() )
                return;

            if ( $this->mode == 'existing' ) {
                if ( !$connection->checkTableExists( 'server' ) ) {
                    $this->form->setError( 'mode', $this->t( 'error.NoDatabaseTables' ) );
                } else {
                    $serverManager = new System_Api_ServerManager();
                    $server = $serverManager->getServer();

                    $this->serverName = $server[ 'server_name' ];

                    $version = $server[ 'db_version' ];
                    $current = version_compare( $version, WI_DATABASE_VERSION );

                    if ( version_compare( $version, '1.0' ) < 0 || $current > 0 ) {
                        $this->form->setError( 'mode', $this->t( 'error.IncompatibleDatabase' ) );
                    } else {
                        $this->update = ( $current < 0 );
                        $this->page = 'existing_site';
                    }
                }
            } else {
                if ( $connection->checkTableExists( 'server' ) )
                    $this->form->setError( 'mode', $this->t( 'error.TablesAlreadyExist' ) );
                else
                    $this->page = 'server';
            }
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'connection';
            $this->form->setError( 'connection', $this->t( 'error.DatabaseError' ) );
        }
    }

    private function checkEngine()
    {
        switch ( $this->engine ) {
            case 'mysqli':
                if ( !function_exists( 'mysqli_connect' ) ) {
                    $this->form->setError( 'engine', $this->t( 'error.MissingExtension', array( 'mysqli' ) ) );
                    return false;
                }
                break;

            case 'pgsql':
                if ( !function_exists( 'pg_connect' ) ) {
                    $this->form->setError( 'engine', $this->t( 'error.MissingExtension', array( 'pgsql' ) ) );
                    return false;
                }
                break;

            case 'mssql':
                if ( !@class_exists( 'COM', false ) ) {
                    $this->form->setError( 'engine', $this->t( 'error.MissingExtension', array( 'com_dotnet' ) ) );
                    return false;
                }
                break;
        }

        return true;
    }

    private function checkPrerequisites()
    {
        switch ( $this->engine ) {
            case 'mysqli':
                if ( !$this->checkDatabaseVersion( '5.0.15' ) )
                    return false;

                $connection = System_Core_Application::getInstance()->getConnection();

                if ( !$connection->getParameter( 'have_innodb' ) ) {
                    $this->form->setError( 'connection', $this->t( 'error.NoInnoDBStorage' ) );
                    return false;
                }
                break;

            case 'pgsql':
                if ( !$this->checkDatabaseVersion( '8.0' ) )
                    return false;
                break;

            case 'mssql':
                if ( !$this->checkDatabaseVersion( '09.00.1399' ) )
                    return false;
                break;
        }

        return true;
    }

    private function checkDatabaseVersion( $minVersion )
    {
        $connection = System_Core_Application::getInstance()->getConnection();

        $version = $connection->getParameter( 'version' );

        if ( version_compare( $version, $minVersion ) < 0 ) {
            $this->form->setError( 'connection', $this->t( 'error.IncorrectDatabaseVersion', array( $version, $minVersion ) ) );
            return false;
        }

        return true;
    }

    private function installDatabase()
    {
        set_time_limit( 300 );

        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $installer = new Setup_Installer( $connection );

            $installer->installSchema();
            $installer->installData( $this->serverName, $this->adminPassword, $this->adminEmail );

            switch ( $this->initialData ) {
                case 'default':
                    $installer->installDefaultTypes();
                    break;
            }

            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.InstallationCompleted' ) );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'failed';
            $this->error = $e->__toString();

            return false;
        }
    }

    private function updateDatabase()
    {
        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $version = $server[ 'db_version' ];

        if ( $version == WI_DATABASE_VERSION )
            return true;

        set_time_limit( 300 );

        $connection = System_Core_Application::getInstance()->getConnection();

        try {
            $updater = new Setup_Updater( $connection );

            $updater->updateDatabase( $version );

            $eventLog = new System_Api_EventLog( $this );
            $eventLog->addEvent( System_Api_EventLog::Audit, System_Api_EventLog::Information, $eventLog->t( 'log.DatabaseUpdated', array( WI_DATABASE_VERSION ) ) );

            return true;
        } catch ( System_Db_Exception $e ) {
            $connection->close();

            $this->page = 'failed';
            $this->error = $e->__toString();

            return false;
        }
    }

    private function writeSiteConfiguration()
    {
        foreach( array( 'engine', 'host', 'database', 'user', 'password', 'prefix' ) as $key )
            $values[ 'db_' . $key ] = $this->$key;

        $config = System_Web_Component::createComponent( 'Setup_Config', null, $values );
        $body = "<?php\n" . $config->run();

        $site = System_Core_Application::getInstance()->getSite();

        $siteDir = $site->getPath( 'site_dir' );
        $path = $siteDir . '/config.inc.php';

        if ( @file_put_contents( $path, $body, LOCK_EX ) === false ) {
            $this->error = $this->t( 'error.CannotSaveConfiguration' );
            $this->page = 'failed';
            return false;
        }

        return true;
    }

    private function startSession()
    {
        System_Core_Application::getInstance()->initializeServer();
        System_Core_Application::getInstance()->initializeSession();

        $sessionManager = new System_Api_SessionManager();
        $sessionManager->loginAs( 'admin' );
    }
}

System_Bootstrap::run( 'Common_Application', 'Setup_Install' );
