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
* Wrapper for an attachment file.
*
* This is an abstraction of the file content which provides a consistent
* interface whether the file is located in the file system or is stored
* in memory (for example when it was retrieved from the database).
*
* @see System_Core_Request::getUploadedFile(),
* System_Api_IssueManager::getAttachment().
*/
class System_Core_Attachment
{
    const NoFile = 0;
    const RegularFile = 1;
    const UploadedFile = 2;
    const TemporaryFile = 3;

    private $data = null;
    private $size = null;
    private $fileName = null;

    private $path = null;
    private $fileType = self::NoFile;

    /**
    * Create an attachment from data in memory.
    * @param $data The content of the file.
    * @param $size The size of the file in bytes.
    * @param $fileName The name of the file.
    */
    public function __construct( $data, $size, $fileName )
    {
        $this->data = $data;
        $this->size = $size;
        $this->fileName = $fileName;
    }

    /**
    * Return the file content.
    * The file is loaded into memory if necessary.
    */
    public function getData()
    {
        if ( $this->data !== null )
            return $this->data;
        return file_get_contents( $this->path );
    }

    /**
    * Return the size of the file in bytes.
    */
    public function getSize()
    {
        return $this->size;
    }

    /**
    * Return the original name of the file.
    */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
    * Return the path of the file or @c null if it's stored in memory.
    */
    public function getPath()
    {
        return $this->path;
    }

    /**
    * Return the type of the file.
    */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
    * Save the file to the given path. If the file was uploaded,
    * the PHP move_uploaded_file() function is used.
    * @param $destination The destination path to write the file to.
    */
    public function saveAs( $destination )
    {
        if ( $this->fileType == self::UploadedFile )
            return move_uploaded_file( $this->path, $destination );
        return file_put_contents( $destination, $this->getData() );
    }

    /**
    * Ensure that the file exists.
    */
    public function validate()
    {
        if ( $this->data === null && !file_exists( $this->path ) )
            throw new System_Core_Exception( 'Attachment file does not exist' );
    }

    /**
    * Output the file content as server's response.
    * Use System_Core_Response::setAttachment() instead of calling
    * this method directly.
    */
    public function outputData()
    {
        $fileName = $this->fileName;
        if ( strpos( $_SERVER[ 'HTTP_USER_AGENT' ], 'MSIE' ) !== false )
            $fileName = rawurlencode( $fileName );

        header( 'Content-Length: ' . $this->size );
        header( 'Content-Disposition: inline; filename="' . $fileName . '"' );

        if ( $this->data !== null )
            echo $this->data;
        else
            readfile( $this->path );

        if ( $this->fileType == self::TemporaryFile )
            unlink( $this->path );
    }

    /**
    * Create an attachment from a file.
    * System_Core_Request::getUploadedFile() should be used to get an uploaded file
    * instead of using $_FILE directly.
    * @param $path The path of the file.
    * @param $size The size of the file in bytes.
    * @param $fileName The original name of the file.
    * @param $fileType One of the file type constants.
    * @return The created System_Core_Attachment object.
    */
    public static function fromFile( $path, $size, $fileName, $fileType = self::RegularFile )
    {
        $attachment = new System_Core_Attachment( null, $size, $fileName );
        $attachment->path = $path;
        $attachment->fileType = $fileType;
        return $attachment;
    }
}
