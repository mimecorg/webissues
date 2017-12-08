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
* Back-end engine for Microsoft SQL Server database.
*
* This engine only works on Windows and uses ADODB to connect to the database.
*/
class System_Db_Mssql_Engine implements System_Db_IEngine
{
    const adStateOpen = 0x01;
    const adCmdText = 0x01;
    const adExecuteNoRecords = 0x80;
    const adParamInput = 1;
    const adInteger = 3;
    const adDouble = 5;
    const adVarChar = 202;
    const adVarBinary = 204;
    const adTypeBinary = 1;
    const adSaveCreateOverWrite = 2;
    const adXactReadUncommitted = 256;
    const adXactReadCommitted = 4096;
    const adXactRepeatableRead = 65536;
    const adXactSerializable = 1048576;

    private $connection = null;

    private $affectedRows = 0;

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    public function open( $host, $database, $user, $password )
    {
        $string = "Provider=sqloledb; Data Source=$host; Initial Catalog=$database";
        if ( $user != '' )
            $string .= "; User ID=$user; Password=$password";
        else
            $string .= "; Trusted_Connection=yes";

        try {
            $this->connection = new COM( 'ADODB.Connection', null, CP_UTF8 );
            $this->connection->Open( $string );
        } catch ( com_exception $ex ) {
            throw new System_Db_Exception( null, $ex );
        }
    }

    public function close()
    {
        if ( $this->connection != null ) {
            if ( $this->connection->State & self::adStateOpen )
                $this->connection->Close();
            $this->connection = null;
        }
    }

    public function execute( $query, $params )
    {
        try {
            if ( empty( $params ) ) {
                $this->connection->Execute( $query, $this->affectedRows, self::adCmdText | self::adExecuteNoRecords );
            } else {
                $command = $this->createCommand( $query, $params );
                $command->Execute( $this->affectedRows, $params, self::adCmdText | self::adExecuteNoRecords );
            }
        } catch ( com_exception $ex ) {
            $this->handleError( $ex );
        }
    }

    public function query( $query, $params )
    {
        try {
            if ( empty( $params ) ) {
                $rs = $this->connection->Execute( $query, $this->affectedRows, self::adCmdText );
            } else {
                $command = $this->createCommand( $query, $params );
                $rs = $command->Execute( $this->affectedRows, $params, self::adCmdText );
            }
        } catch ( com_exception $ex ) {
            $this->handleError( $ex );
        }

        return new System_Db_Mssql_Result( $rs );
    }

    private function createCommand( $query, $params )
    {
        $command = new COM( 'ADODB.Command', null, CP_UTF8 );
        $command->ActiveConnection = $this->connection;
        $command->CommandText = $query;
        $command->CreateParameter( '', self::adInteger, self::adParamInput, -1, 0 );
        foreach ( $params as $value ) {
            if ( is_int( $value ) )
                $param = $command->CreateParameter( '', self::adInteger, self::adParamInput, -1 );
            else if ( is_float( $value ) )
                $param = $command->CreateParameter( '', self::adDouble, self::adParamInput, -1 );
            else if ( is_string( $value ) )
                $param = $command->CreateParameter( '', self::adVarChar, self::adParamInput, max( mb_strlen( $value ), 1 ) );
            else
                $param = $command->CreateParameter( '', self::adVarBinary, self::adParamInput, max( count( $value ), 1 ) );
            $command->Parameters->Append( $param );
        }
        return $command;
    }

    public function escapeArgument( $arg, $type, &$params )
    {
        switch( $type ) {
            case 'd':
                $params[] = (int)$arg;
                return '?';
            case 'f':
                $params[] = (float)$arg;
                return '?';
            case 's':
            case 't':
                $params[] = (string)$arg;
                return '?';
            case 'b':
                $stream = new COM( 'ADODB.Stream', null, CP_UTF8 );
                $stream->Type = self::adTypeBinary;
                $stream->Open();
                if ( $arg->getPath() != null ) {
                    $stream->LoadFromFile( $arg->getPath() );
                } else {
                    $path = tempnam( sys_get_temp_dir(), 'wi_' );
                    $arg->saveAs( $path );
                    $stream->LoadFromFile( $path );
                    unlink( $path );
                }
                $stream->Position = 0;
                $params[] = $stream->Read();
                $stream->Close();
                return '?';
        }
    }

    public function getPagedQuery( $query, $orderBy, $limit, $offset )
    {
        if ( substr( $query, 0, 7 ) != 'SELECT ' )
            throw new System_Db_Exception( 'Not a select query' );
        $query = substr( $query, 7 );

        if ( $offset == 0 )
            return "SELECT TOP $limit $query ORDER BY $orderBy";

        $pos = strpos( $query, ' FROM ' );
        if ( $pos === false )
            throw new System_Db_Exception( 'No from clause found in query' );

        $columns = explode( ',', substr( $query, 0, $pos ) );
        foreach ( $columns as &$column ) {
            $column = rtrim( $column , ' ' );
            $pos = strrpos( $column, ' ' );
            if ( $pos !== false )
                $column = substr( $column, $pos + 1 );
            $pos = strrpos( $column, '.' );
            if ( $pos !== false )
                $column = substr( $column, $pos + 1 );
            $column = "t.$column";
        }

        $select = implode( ', ', $columns );
        $top = $limit + $offset;

        return "SELECT $select FROM ( SELECT TOP $top ROW_NUMBER() OVER (ORDER BY $orderBy) AS row_num, $query ) AS t WHERE t.row_num > $offset";
    }

    public function getLocaleCollation()
    {
        return '';
    }

    public function createAttachment( $data, $size, $fileName )
    {
        $path = tempnam( sys_get_temp_dir(), 'wi_' );

        $stream = new COM( 'ADODB.Stream', null, CP_UTF8 );
        $stream->Type = self::adTypeBinary;
        $stream->Open();
        $stream->Write( $data );
        $stream->Position = 0;
        $stream->SaveToFile( $path, self::adSaveCreateOverWrite );
        $stream->Close();

        return System_Core_Attachment::fromFile( $path, $size, $fileName, System_Core_Attachment::TemporaryFile );
    }

    public function getAffectedRows()
    {
        return $this->affectedRows;
    }

    public function getInsertId( $table, $column )
    {
        $query = 'SELECT @@IDENTITY';
        $rs = $this->connection->Execute( $query, $this->affectedRows, self::adCmdText );
        $insertId = $rs->Fields[ 0 ]->Value;
        $rs->close();
        // NOTE: $insertId is a VARIANT of type VT_DECIMAL so it must be converted to int
        return (int)$insertId;
    }

    public function castExpression( $expression, $type )
    {
        switch ( $type ) {
            case 'd':
                return "CAST( $expression AS int )";
            case 'f':
                return "CAST( $expression AS decimal(14,6) )";
            case 's':
                return "CAST( $expression AS nvarchar(max) )";
            case 't':
                return "CAST( $expression AS datetime )";
            default:
                throw new System_Db_Exception( 'Invalid type' );
        }
    }

    public function checkTableExists( $table )
    {
        $query = "SELECT OBJECT_ID('$table', 'U')";
        $rs = $this->connection->Execute( $query, $this->affectedRows, self::adCmdText );
        $objectId = $rs->Fields[ 0 ]->Value;
        $rs->close();
        return $objectId != null;
    }

    public function getParameter( $key )
    {
        switch ( $key ) {
            case 'server':
                return $this->connection->Properties[ 'DBMS Name' ];
            case 'version':
                return $this->connection->Properties[ 'DBMS Version' ];
            default:
                return null;
        }
    }

    public function beginTransaction( $level, $table )
    {
        switch ( $level ) {
            case System_Db_Transaction::ReadUncommitted:
                $isoLevel = self::adXactReadUncommitted;
                break;
            case System_Db_Transaction::ReadCommitted:
                $isoLevel = self::adXactReadCommitted;
                break;
            case System_Db_Transaction::RepeatableRead:
                $isoLevel = self::adXactRepeatableRead;
                break;
            case System_Db_Transaction::Serializable:
                $isoLevel = self::adXactSerializable;
                break;
            default:
                throw new System_Db_Exception( 'Unsupported isolation level' );
        }

        try {
            $this->connection->IsolationLevel = $isoLevel;
            $this->connection->BeginTrans();
        } catch ( com_exception $ex ) {
            $this->handleError( $ex );
        }
    }

    public function endTransaction( $commit )
    {
        try {
            if ( $commit )
                $this->connection->CommitTrans();
            else
                $this->connection->RollbackTrans();
        } catch ( com_exception $ex ) {
            $this->handleError( $ex );
        }
    }

    private function handleError( $ex )
    {
        foreach ( $this->connection->Errors as $error ) {
            if ( $error->NativeError == 1205 )
                throw new System_Api_Error( System_Api_Error::TransactionDeadlock, $ex );
            if ( $error->NativeError == 547 )
                throw new System_Api_Error( System_Api_Error::ConstraintConflict, $ex );
        }
        throw new System_Db_Exception( null, $ex );
    }
}
