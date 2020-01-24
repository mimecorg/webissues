<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2015 WebIssues Team
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
* Manage email inboxes.
*/
class System_Api_InboxManager extends System_Api_Base
{
    private static $inboxes = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Get all email inboxes.
    * @return An array of associative arrays representing inboxes.
    */
    public function getInboxes()
    {
        $query = 'SELECT inbox_id, inbox_engine, inbox_email, inbox_server, inbox_port, inbox_encryption, inbox_user, inbox_password, inbox_mailbox, inbox_no_validate,'
            . ' inbox_leave_messages, inbox_allow_external, inbox_robot, inbox_map_folder, inbox_default_folder, inbox_respond, inbox_subscribe'
            . ' FROM {email_inboxes}';

        return $this->connection->queryTable( $query );
    }

    /**
    * Get the email inbox with given identifier.
    * @param $inboxId Identifier of the inbox.
    * @return Array containing inbox details.
    */
    public function getInbox( $inboxId )
    {
        if ( isset( self::$inboxes[ $inboxId ] ) ) {
            $inbox = self::$inboxes[ $inboxId ];
        } else {
            $query = 'SELECT inbox_id, inbox_engine, inbox_email, inbox_server, inbox_port, inbox_encryption, inbox_user, inbox_password, inbox_mailbox, inbox_no_validate,'
                . ' inbox_leave_messages, inbox_allow_external, inbox_robot, inbox_map_folder, inbox_default_folder, inbox_respond, inbox_subscribe'
                . ' FROM {email_inboxes}'
                . ' WHERE inbox_id = %d';

            if ( !( $inbox = $this->connection->queryRow( $query, $inboxId ) ) )
                throw new System_Api_Error( System_Api_Error::UnknownInbox );

            self::$inboxes[ $inboxId ] = $inbox;
        }

        return $inbox;
    }

    /**
    * Create a new email inbox. An error is thrown if an inbox with given email address
    * already exists.
    * @param $name The name of the inbox to create.
    * @return The identifier of the new inbox.
    */
    public function addInbox( $inbox )
    {
        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'email_inboxes' );

        try {
            $query = 'SELECT inbox_id FROM {email_inboxes} WHERE UPPER( inbox_email ) = %s';
            if ( $this->connection->queryScalar( $query, mb_strtoupper( $inbox[ 'inbox_email' ] ) ) !== false )
                throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );

            $query = 'INSERT INTO {email_inboxes} ( inbox_engine, inbox_email, inbox_server, inbox_port, inbox_encryption, inbox_user, inbox_password, inbox_mailbox,'
                . ' inbox_no_validate, inbox_leave_messages, inbox_allow_external, inbox_robot, inbox_map_folder, inbox_default_folder, inbox_respond, inbox_subscribe )'
                . ' VALUES ( %s, %s, %s, %d, %s?, %s?, %s?, %s?, %d, %d, %d, %d?, %d, %d?, %d, %d )';

            $this->connection->execute( $query, $inbox[ 'inbox_engine' ], $inbox[ 'inbox_email' ], $inbox[ 'inbox_server' ], $inbox[ 'inbox_port' ],
                $inbox[ 'inbox_encryption' ], $inbox[ 'inbox_user' ], $inbox[ 'inbox_password' ], $inbox[ 'inbox_mailbox' ], $inbox[ 'inbox_no_validate' ],
                $inbox[ 'inbox_leave_messages' ], $inbox[ 'inbox_allow_external' ], $inbox[ 'inbox_robot' ], $inbox[ 'inbox_map_folder' ],
                $inbox[ 'inbox_default_folder' ], $inbox[ 'inbox_respond' ], $inbox[ 'inbox_subscribe' ] );

            $inboxId = $this->connection->getInsertId( 'email_inboxes', 'inbox_id' );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return $inboxId;
    }

    /**
    * Modify an email inbox. An error is thrown if another inbox with given email address
    * already exists.
    * @param $inbox The inbox to modify.
    * @param $newInbox The new properties of the inbox.
    * @return @c true if the inbox was modified.
    */
    public function modifyInbox( $inbox, $newInbox )
    {
        $inboxId = $inbox[ 'inbox_id' ];

        $modified = false;
        foreach ( $inbox as $key => $value ) {
            if ( $key != 'inbox_id' && $value != $newInbox[ $key ] )
                $modified = true;
        }

        if ( !$modified )
            return false;

        $transaction = $this->connection->beginTransaction( System_Db_Transaction::Serializable, 'email_inboxes' );

        try {
            if ( mb_strtoupper( $inbox[ 'inbox_email' ] ) != mb_strtoupper( $newInbox[ 'inbox_email' ] ) ) {
                $query = 'SELECT inbox_id FROM {email_inboxes} WHERE UPPER( inbox_email ) = %s';
                if ( $this->connection->queryScalar( $query, mb_strtoupper( $newInbox[ 'inbox_email' ] ) ) !== false )
                    throw new System_Api_Error( System_Api_Error::EmailAlreadyExists );
            }

            $query = 'UPDATE {email_inboxes}'
                . ' SET inbox_engine = %s, inbox_email = %s, inbox_server = %s, inbox_port = %d, inbox_encryption = %s!, inbox_user = %s!, inbox_password = %s!,'
                . ' inbox_mailbox = %s!, inbox_no_validate = %d, inbox_leave_messages = %d, inbox_allow_external = %d, inbox_robot = %d!, inbox_map_folder = %d,'
                . ' inbox_default_folder = %d!, inbox_respond = %d, inbox_subscribe = %d'
                . ' WHERE inbox_id = %d';

            $this->connection->execute( $query, $newInbox[ 'inbox_engine' ], $newInbox[ 'inbox_email' ], $newInbox[ 'inbox_server' ], $newInbox[ 'inbox_port' ],
                $newInbox[ 'inbox_encryption' ], $newInbox[ 'inbox_user' ], $newInbox[ 'inbox_password' ], $newInbox[ 'inbox_mailbox' ], $newInbox[ 'inbox_no_validate' ],
                $newInbox[ 'inbox_leave_messages' ], $newInbox[ 'inbox_allow_external' ], $newInbox[ 'inbox_robot' ], $newInbox[ 'inbox_map_folder' ],
                $newInbox[ 'inbox_default_folder' ], $newInbox[ 'inbox_respond' ], $newInbox[ 'inbox_subscribe' ], $inboxId );

            $transaction->commit();
        } catch ( Exception $ex ) {
            $transaction->rollback();
            throw $ex;
        }

        return true;
    }

    /**
    * Delete an email inbox.
    * @param $inbox The inbox to delete.
    * @return @c true if the inbox was deleted.
    */
    public function deleteInbox( $inbox )
    {
        $inboxId = $inbox[ 'inbox_id' ];

        $query = 'DELETE FROM {email_inboxes} WHERE inbox_id = %d';
        $this->connection->execute( $query, $inboxId );

        return true;
    }
}
