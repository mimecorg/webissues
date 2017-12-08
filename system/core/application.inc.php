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
* Base class for all WebIssues applications.
*
* The application object is a singleton which creates and holds references
* to other objects providing  information about the current context of execution,
* such as the request, response, session, etc. It is also responsible for
* handling errors.
*
* The abstract method execute() must be implemented in a derived class.
*/
abstract class System_Core_Application
{
    private static $instance = null;

    protected $site = null;
    protected $debug = null;
    protected $connection = null;
    protected $request = null;
    protected $response = null;
    protected $session = null;
    protected $translator = null;

    private $commandLine = null;
    private $siteName = null;

    private $errors = array();
    private $fatalError = null;

    private $startTime = null;
    private $debugInfoEnabled = false;

    private $loggingEnabled = false;

    /**
    * Constructor.
    */
    protected function __construct()
    {
        self::$instance = $this;

        $this->startTime = microtime( true );

        $this->request = new System_Core_Request();
        $this->response = new System_Core_Response();
        $this->site = new System_Core_Site();
        $this->debug = new System_Core_Debug();
        $this->connection = new System_Db_Connection();
        $this->translator = new System_Core_Translator();
        $this->session = new System_Core_Session();
    }

    /**
    * Create an instance of the application class.
    * This method is called by System_Bootstrap::run(). Only one instance
    * of the application can be created.
    * @param $class Class name of the application object to create.
    * @param $parameter Optional parameter passed to the application's constructor.
    * @return An instance of the application.
    */
    public static function createInstance( $class, $parameter = null )
    {
        if ( self::$instance != null )
            throw new System_Core_Exception( 'An application was already created' );

        return new $class( $parameter );
    }

    /**
    * Return the instance of the application class.
    */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
    * Return the System_Core_Site object.
    */
    public function getSite()
    {
        return $this->site;
    }

    /**
    * Return the System_Core_Debug object.
    */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
    * Return the System_Db_Connection object.
    */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
    * Return the System_Core_Request object.
    */
    public function getRequest()
    {
        return $this->request;
    }

    /**
    * Return the System_Core_Response object.
    */
    public function getResponse()
    {
        return $this->response;
    }

    /**
    * Return the System_Core_Session object.
    */
    public function getSession()
    {
        return $this->session;
    }

    /**
    * Return the System_Core_Translator object.
    */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
    * Return the array containing all errors and warnings that have occured.
    * All errors are wrapped in objects deriving the System_Core_Exception class.
    */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
    * Return the fatal error that terminated execution of the script or @c null.
    * The error is an object deriving the System_Core_Exception class.
    */
    public function getFatalError()
    {
        return $this->fatalError;
    }

    /**
    * Return @c true if debugging information is enabled in site configuration.
    * When enabled, full error details are displayed instead of generic error messages.
    * Debugging information is disabled by default; to enable it create
    * a /data/site.ini file:
    * @code
    * [default]
    * debug_info = on
    * @endcode
    */
    public function isDebugInfoEnabled()
    {
        return $this->debugInfoEnabled;
    }

    /**
    * Return @c true if the event log was initialized.
    */
    public function isLoggingEnabled()
    {
        return $this->loggingEnabled;
    }

    /**
    * Main entry point of the application, called by System_Bootstrap::run().
    * Initialize the request, database connection and session, call the
    * execute() method and send the response to the client.
    */
    public function run()
    {
        try {
            $this->initializeRequest();
            $this->initializeSite();
            $this->initializeConnection();
        } catch ( System_Core_SetupException $ex ) {
            $this->handleSetupException( $ex );
        }

        $this->execute();

        $this->response->send();
    }

    /**
    * Create the request and response objects and set up error handling.
    */
    private function initializeRequest()
    {
        if ( WI_BASE_URL == '' ) {
            global $argv;
            $this->commandLine = $argv;
        }

        mb_internal_encoding( 'UTF-8' );

        @date_default_timezone_set( @date_default_timezone_get() );

        if ( $this->commandLine == null )
            $this->request->initialize();
        $this->response->initialize();

        // override PHP error handling
        register_shutdown_function( array( $this, 'shutdown' ) );

        $errors = ini_get( 'display_errors' );
        if ( $errors == 1 || $errors == 'stdout' )
            $this->debugInfoEnabled = true;

        error_reporting( E_ALL );
        ini_set( 'html_errors', 0 );
        ini_set( 'display_errors', 0 );
        set_error_handler( array( $this, 'handleError' ) );

        set_exception_handler( array( $this, 'handleException' ) );
    }

    /**
    * Load site configuration.
    */
    private function initializeSite()
    {
        if ( $this->commandLine != null )
            $this->processCommandLine( count( $this->commandLine ), $this->commandLine );

        $this->site->initializeSite( $this->siteName );

        $level = $this->site->getConfig( 'debug_level' );
        $this->debug->setLevel( $level );

        $file = $this->site->getPath( 'debug_file' );
        $this->debug->setFile( $file );

        $this->debugInfoEnabled = $this->site->getConfig( 'debug_info' );

        if ( $this->debug->checkLevel( DEBUG_REQUESTS ) )
            $this->debug->write( 'Running script: ', $this->request->getRelativePath(), "\n" );

        if ( $this->debug->checkLevel( DEBUG_ERRORS ) ) {
            foreach ( $this->errors as $exception )
                $this->debug->write( '*** ', $exception->__toString(), "\n" );
        }

        $this->site->loadSiteConfig();
    }

    /**
    * Process command line parameters if the script was run from command line.
    * Override this method to get the site name from the parameters.
    */
    protected function processCommandLine( $argc, $argv )
    {
    }

    /**
    * Set the internal name of the site to initialize.
    */
    protected function setSiteName( $siteName )
    {
        $this->siteName = $siteName;
    }

    /**
    * Connect to the database, check its version and initialize
    * the server and session.
    */
    private function initializeConnection()
    {
        $engine = $this->site->getConfig( 'db_engine' );
        $this->connection->loadEngine( $engine );

        $host = $this->site->getConfig( 'db_host' );
        $database = $this->site->getConfig( 'db_database' );
        $user = $this->site->getConfig( 'db_user' );
        $password = $this->site->getConfig( 'db_password' );
        $this->connection->open( $host, $database, $user, $password );

        $prefix = $this->site->getConfig( 'db_prefix' );
        $this->connection->setPrefix( $prefix );

        $serverManager = new System_Api_ServerManager();
        $server = $serverManager->getServer();

        $version = $server[ 'db_version' ];
        $current = version_compare( $version, WI_DATABASE_VERSION );

        if ( version_compare( $version, '1.0' ) < 0 || $current > 0 ) {
            $this->connection->close();
            throw new System_Core_SetupException( 'Database version ' . $version . ' is not compatible with server version ' . WI_VERSION,
                System_Core_SetupException::DatabaseNotCompatible );
        }

        if ( $current < 0 ) {
            $this->initializeServer();
            throw new System_Core_SetupException( 'Database is not updated', System_Core_SetupException::DatabaseNotUpdated );
        }

        $this->loggingEnabled = true;

        if ( !empty( $this->errors ) ) {
            $eventLog = new System_Api_EventLog();
            foreach ( $this->errors as $exception )
                $eventLog->addErrorEvent( $exception );
        }

        $this->initializeServer();
        $this->initializeSession();
    }

    /**
    * Load server configuration.
    */
    public function initializeServer()
    {
        $this->translator->addModule( 'webissues' );

        $serverManager = new System_Api_ServerManager();
        $language = $serverManager->getSetting( 'language' );

        $this->translator->setLanguage( System_Core_Translator::SystemLanguage, $language );
    }

    /**
    * Initialize session and load user preferences.
    */
    public function initializeSession()
    {
        if ( $this->commandLine == null )
            $this->session->initialize();

        $sessionManager = new System_Api_SessionManager();
        $sessionManager->initializePrincipal();

        $preferencesManager = new System_Api_PreferencesManager();
        $language = $preferencesManager->getPreferenceOrSetting( 'language' );

        $this->translator->setLanguage( System_Core_Translator::UserLanguage, $language );
    }

    /**
    * Method called after initializing the application. It should be implemented
    * by a derived class and should process the request and set up the response
    * to be sent to the client.
    */
    protected abstract function execute();

    /**
    * The PHP shutdown function (see http://php.net/register_shutdown_function)
    * called at the end of execution, also in case of a fatal error or unexpected
    * exit. Call @c displayErrorPage() if necessary and close session and
    * database connection.
    */
    public function shutdown()
    {
        // prevent White Screen Of Death on fatal error / unexpected exit
        $exception = System_Core_ErrorException::getLastFatalError();
        if ( $exception != null )
            $this->handleException( $exception );

        if ( !$this->response->isSending() && $this->fatalError == null )
            $this->handleException( new System_Core_Exception( 'Request terminated unexpectedly' ) );

        if ( $this->connection->getTransaction() != null )
            $this->handleException( new System_Core_Exception( 'Uncommitted transaction' ) );

        // display error page if necessary
        if ( $this->fatalError != null ) {
            try {
                if ( $this->response->reset() )
                    $this->displayErrorPage();
            } catch ( Exception $ex ) {
                $this->handleException( $ex );
            }
        }

        // clean up
        try {
            $this->cleanUp();
        } catch ( Exception $ex ) {
            $this->handleException( $ex );
        }

        $this->loggingEnabled = false;

        try {
            $this->session->close();
            $this->connection->close();
        } catch ( Exception $ex ) {
            $this->handleException( $ex );
        }

        // write out debugging messages
        if ( $this->debug->checkLevel( DEBUG_REQUESTS ) ) {
            $output = $this->response->getBufferedOutput();
            if ( $output !== '' )
                $this->debug->write( "Buffered output:\n", $output, "\n" );
            $time = ( microtime( true ) - $this->startTime ) * 1000;
            $this->debug->write( sprintf( "Total execution time: %.1f ms\n", $time ) );
        }
        $this->debug->close();
    }

    /**
    * Method called just before the script exists.
    */
    protected function cleanUp()
    {
    }

    /**
    * Clean up expired sessions and events from the database. This method
    * is called by PHP session mechanism when garbage collection occurs
    * (see http://php.net/session_set_save_handler).
    */
    public function collectGarbage()
    {
        $sessionManager = new System_Api_SessionManager();
        $sessionManager->expireSessions();

        $eventLog = new System_Api_EventLog();
        $eventLog->expireEvents();

        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'self_register' ) == 1 ) {
            $registrationManager = new System_Api_RegistrationManager();
            $registrationManager->expireRequests();
        }
    }

    /**
    * Handler for non-fatal PHP errors (see http://php.net/set_error_handler).
    * Wrap the error in a System_Core_ErrorException object, add it to the list
    * of errors and log it if possible. Execution continues normally.
    */
    public function handleError( $errno, $message, $file, $line )
    {
        if ( $errno & error_reporting() ) {
            $exception = new System_Core_ErrorException( $errno, $message, $file, $line );

            $this->errors[] = $exception;

            $this->logException( $exception );
        }
    }

    /**
    * Handler for unhandled exceptions (see http://php.net/set_exception_handler).
    * If necessary wrap the exception in a System_Core_Exception object, add to the list
    * of errors and log it. Execution is terminated by PHP when this function is called.
    * This method is also called when shutdown() detects a fatal error.
    */
    public function handleException( $exception )
    {
        // wrap foreign exceptions into our exception
        if ( !is_a( $exception, 'System_Core_Exception' ) )
            $exception = new System_Core_Exception( null, $exception );

        $this->errors[] = $exception;

        if ( $this->fatalError == null )
            $this->fatalError = $exception;

        // roll back any pending transaction before logging the error
        if ( $this->loggingEnabled && $this->connection->getTransaction() != null ) {
            try {
                $this->connection->getTransaction()->rollback();
            } catch ( Exception $ex ) {
                $this->loggingEnabled = false;
                $this->handleException( $ex );
            }
        }

        $this->logException( $exception );
    }

    /**
    * Log an error to debug log (if debugging is enabled) and to the event log
    * in database (if connection is opened and the severity level is an error or
    * warning).
    * Errors which occur before debug log or database connection is opened
    * are logged once the log or connection is opened. Errors which occur while
    * logging another error are not logged to prevent infinite recursion.
    */
    protected function logException( $exception )
    {
        if ( $this->commandLine != null && is_resource( STDERR ) )
            fwrite( STDERR, $exception->__toString() . "\n" );

        if ( $this->debug->checkLevel( DEBUG_ERRORS ) )
            $this->debug->write( '*** ', $exception->__toString(), "\n" );

        if ( $this->loggingEnabled ) {
            try {
                $eventLog = new System_Api_EventLog();
                $eventLog->addErrorEvent( $exception );
            } catch ( Exception $ex ) {
                $this->loggingEnabled = false;
                $this->handleException( $ex );
            }
        }
    }

    /**
    * Handle a System_Core_SetupException thrown during initializeConnection().
    * By default handleException() is called and execution is terminated.
    * Derived classes may override this method to ignore setup errors
    * while the site is being configured.
    */
    protected function handleSetupException( $exception )
    {
        $this->handleException( $exception );
        exit;
    }

    /**
    * Display an error page when a fatal error has occurred.
    * This method is called during shutdown() so fatal errors and unexpected
    * exits can be detected. By default a simple HTML error page is rendered
    * or an error message is printed in command line mode. Derived classes
    * may override this method.
    *
    * Note that error details should only be displayed if debugging
    * information is enabled. Also the error response is not automatically sent
    * so this function must call System_Core_Response::send().
    */
    protected function displayErrorPage()
    {
        $content = '';

        if ( $this->commandLine == null ) {
            $content .= "<html>\n";
            $content .= "<head><title>Unexpected Error</title></head>\n";
            $content .= "<body>\n";
            $content .= "<h1>Unexpected Error</h1>\n";
            $content .= "<p>An unexpected error occured while processing the request.</p>\n";
            $content .= "</body>\n";
            $content .= "</html>\n";
        } else {
            $content .= "An unexpected error occured while executing the command.\n";
        }

        $this->response->setContentType( 'text/html; charset=UTF-8' );
        $this->response->setContent( $content );

        $this->response->send();
    }
}
