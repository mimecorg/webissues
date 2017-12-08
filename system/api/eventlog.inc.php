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
* Manage the event log of the server.
*/
class System_Api_EventLog extends System_Api_Base
{
    /**
    * @name Log Types
    */
    /*@{*/
    /** Log used for PHP errors and warnings. */
    const Errors = 'errors';
    /** Log used for login events and unauthorized access errors. */
    const Access = 'access';
    /** Audit log for operations related to security. */
    const Audit = 'audit';
    /** Log used by the cron job. */
    const Cron = 'cron';
    /*@}*/

    /**
    * @name Severity Levels
    */
    /*@{*/
    /** Event is for information purposes only. */
    const Information = 0;
    /** Event is a warning or recoverable error. */
    const Warning = 1;
    /** Event is a serious unrecoverable error. */
    const Error = 2;
    /*@}*/

	private $ownerClass = null;

    /**
    * Constructor.
    * @param $owner Optional owner object used as context for translating strings.
    */
    public function __construct( $owner = null )
    {
        parent::__construct();

        if ( $owner != null )
            $this->ownerClass = get_class( $owner );
    }

    /**
    * Get paged list of events.
    * @param $type Optional type used for filtering.
    * @param $orderBy The sorting order specifier.
    * @param $limit Maximum number of rows to return.
    * @param $offset Zero-based index of first row to return.
    * @return An array of associative arrays representing events.
    */
    public function getEvents( $type, $orderBy, $limit, $offset )
    {
        $query = 'SELECT e.event_id, e.event_type, e.event_severity, e.event_message, e.event_time'
            . ' FROM {log_events} AS e';
        if ( !empty( $type ) )
            $query .= ' WHERE e.event_type = %s';

        return $this->connection->queryPage( $query, $orderBy, $limit, $offset, $type );
    }

    /**
    * Return the total number of events.
    * @param $type Optional type used for filtering.
    * @return The number of events.
    */
    public function getEventsCount( $type )
    {
        $query = 'SELECT COUNT(*) FROM {log_events} AS e';
        if ( !empty( $type ) )
            $query .= ' WHERE e.event_type = %s';

        return $this->connection->queryScalar( $query, $type );
    }

    /**
    * Return sortable column definitions for the System_Web_Grid.
    */
    public function getEventsColumns()
    {
        return array(
            'date' => 'e.event_id'
            );
    }

    /**
    * Get details of an event.
    * @param $eventId The identifier of the event.
    * @return Array containing event details.
    */
    public function getEvent( $eventId )
    {
        $query = 'SELECT e.event_id, e.event_type, e.event_severity, e.event_message, e.event_time,'
            . ' e.host_name, u.user_id, u.user_name'
            . ' FROM {log_events} AS e'
            . ' LEFT OUTER JOIN {users} AS u ON u.user_id = e.user_id'
            . ' WHERE e.event_id = %d';

        if ( !( $event = $this->connection->queryRow( $query, $eventId ) ) )
            throw new System_Api_Error( System_Api_Error::UnknownEvent );

        return $event;
    }

    /**
    * Add a new event to the log.
    * @param $type Type of the event log.
    * @param $severity Severity level of the event.
    * @param $message Message describing the event.
    */
    public function addEvent( $type, $severity, $message )
    {
        $userId = System_Api_Principal::getCurrent()->getUserId();

        $request = System_Core_Application::getInstance()->getRequest();
        $host = $request->getHostName();

        $query = 'INSERT INTO {log_events} ( event_type, event_severity, event_message, event_time, user_id, host_name )';
        if ( $userId != 0 )
            $query .= ' VALUES ( %1s, %2d, %3s, %4d, %5d, %6s )';
        else
            $query .= ' VALUES ( %1s, %2d, %3s, %4d, NULL, %6s )';

        $this->connection->execute( $query, $type, $severity, $message, time(), $userId, $host );
    }

    /**
    * Log the exception in the Errors event log. If the exception is
    * a System_Core_ErrorException the severity level is automatically
    * determined and notices are discarded.
    * @param $exception The exception to log.
    */
    public function addErrorEvent( $exception )
    {
        $type = self::Errors;
        $severity = self::Error;
        
        if ( is_a( $exception, 'System_Core_ErrorException' ) ) {
            $errno = $exception->getErrno();
            if ( $errno == E_NOTICE || $errno == E_STRICT || $errno == E_DEPRECATED || $errno == E_USER_NOTICE || $errno == E_USER_DEPRECATED )
                return;
            if ( $errno == E_WARNING || $errno == E_USER_WARNING )
                $severity = self::Warning;
        }

        $this->addEvent( $type, $severity, $exception->__toString() );
    }

    /**
    * Remove old events from the database. The lifetime of events
    * can be configured in server settings.
    */
    public function expireEvents()
    {
        $query = 'DELETE FROM {log_events} WHERE event_time < %d';

        $serverManager = new System_Api_ServerManager();
        $lifetime = $serverManager->getSetting( 'log_max_lifetime' );

        $this->connection->execute( $query, time() - $lifetime );
    }

    /**
    * Return a translated version of the source string.
    * The system default language is used unlike in other tr() methods.
    * @param $source The source string to translate.
    * @param $comment An optional comment explaining the use of the string
    * to the translators.
    */
    public function tr( $source, $comment = null )
    {
        $translator = System_Core_Application::getInstance()->getTranslator();
        $args = func_get_args();
        return $translator->translate( System_Core_Translator::SystemLanguage, $this->ownerClass, $args );
    }
}
