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

/**
* Wrapper for the request to be processed.
*
* This class provides access to URL query strings, posted fields and uploaded
* files, and returns other information about the request. It should be used
* instead of directly accessing $_GET, $_POST etc.
*
* An instance of this class is accessible through the System_Core_Application
* object and as a property of classes inheriting System_Web_Base.
*/
class System_Core_Request
{
    private $queryStrings = array();
    private $formFields = array();
    private $uploadedFiles = array();

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Load input parameters and strip slashes if necessary.
    */
    public function initialize()
    {
        if ( get_magic_quotes_gpc() ) {
            $this->queryStrings = self::stripSlashesDeep( $_GET );
            $this->formFields = self::stripSlashesDeep( $_POST );
        } else {
            $this->queryStrings = $_GET;
            $this->formFields = $_POST;
        }
        $this->uploadedFiles = $_FILES;
    }

    /**
    * Return the path of the entry script relative to the base directory.
    * The relative path is available also when running in command line mode.
    * It always starts with a slash and contains the script name, for example
    * '/client/index.php'.
    */
    public function getRelativePath()
    {
        return substr( WI_SCRIPT_PATH, strlen( WI_ROOT_DIR ) );
    }

    /**
    * Check if the given script is the entry script.
    * @param $path The path of the script relative to the base directory.
    * @return @c true if the script is the entry script.
    */
    public function isRelativePath( $path )
    {
        return $this->getRelativePath() == $path;
    }

    /**
    * Check if the entry script is located under the given path.
    * @param $path The path of the directory relative to the base directory
    * (without trailing slash).
    * @return @c true if the entry script is located in the given directory
    * or any of its subdirectories.
    */
    public function isRelativePathUnder( $path )
    {
        return substr( $this->getRelativePath(), 0, strlen( $path ) + 1 ) == $path . '/';
    }

    /**
    * Return the file name of the entry script (without the '.php' extension).
    */
    public function getScriptBaseName()
    {
        return basename( WI_SCRIPT_PATH, '.php' );
    }

    /**
    * Return all query parameters passed to the request via the URL.
    */
    public function getQueryStrings()
    {
        return $this->queryStrings;
    }

    /**
    * Return the value of a specific query parameter passed via the URL.
    * @param $key Name of the parameter to return.
    * @param $default The default value if the parameter was not passed.
    */
    public function getQueryString( $key, $default = null )
    {
        return isset( $this->queryStrings[ $key ] ) ? $this->queryStrings[ $key ] : $default;
    }

    /**
    * Return the raw query string passed via the URL.
    */
    public function getRawQueryString()
    {
        return $_SERVER[ 'QUERY_STRING' ];
    }

    /**
    * Return all fields passed to the request via the POST method.
    * It's an equivalent of $_POST.
    */
    public function getFormFields()
    {
        return $this->formFields;
    }

    /**
    * Return the value of a specific field passed via the POST method.
    * @param $key Name of the field to return.
    * @param $default The default value if the field was not passed.
    */
    public function getFormField( $key, $default = null )
    {
        return isset( $this->formFields[ $key ] ) ? $this->formFields[ $key ] : $default;
    }

    /**
    * Return a file uploaded via the POST method.
    * @param $key Name of the field containing the uploaded file.
    * @return A System_Core_Attachment object wrapping the file,
    * @c null if no file was uploaded or @c false in case of an error.
    */
    public function getUploadedFile( $key )
    {
        if ( isset( $this->uploadedFiles[ $key ] ) ) {
            $file = $this->uploadedFiles[ $key ];
            if ( $file[ 'error' ] == UPLOAD_ERR_NO_FILE )
                return null;
            $path = $file[ 'tmp_name' ];
            $size = $file[ 'size' ];
            if ( $path != '' && is_uploaded_file( $path ) && $size != 0 )
                return System_Core_Attachment::fromFile( $path, $size, $file[ 'name' ], System_Core_Attachment::UploadedFile );
            return false;
        }
        return null;
    }

    /**
    * Return the name or IP address of the client if available.
    */
    public function getHostName()
    {
        if ( !empty( $_SERVER[ 'REMOTE_HOST' ] ) )
            return $_SERVER[ 'REMOTE_HOST' ];
        if ( !empty( $_SERVER[ 'REMOTE_ADDR' ] ) )
            return $_SERVER[ 'REMOTE_ADDR' ];
        return 'unknown';
    }

    /**
    * Return the original URL of the request.
    * This function is only supported on Apache and compatible servers.
    */
    public function getRequestUrl()
    {
        return isset( $_SERVER[ 'REQUEST_URI' ] ) ? $_SERVER[ 'REQUEST_URI' ] : null;
    }

    /**
    * Return the name and version of the web server.
    */
    public function getServerSoftware()
    {
        return isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) ? $_SERVER[ 'SERVER_SOFTWARE' ] : null;
    }

    /**
    * Return the value of the If-Modified-Since header.
    */
    public function getIfModifiedSince()
    {
        return isset( $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] ) ? $_SERVER[ 'HTTP_IF_MODIFIED_SINCE' ] : null;
    }

    /**
    * Return the value of the If-None-Match header.
    */
    public function getIfNoneMatch()
    {
        return isset( $_SERVER[ 'HTTP_IF_NONE_MATCH' ] ) ? $_SERVER[ 'HTTP_IF_NONE_MATCH' ] : null;
    }

    /**
    * Return the value of the Content-Type header without parameters.
    */
    public function getContentType()
    {
        if ( isset( $_SERVER[ 'CONTENT_TYPE' ] ) ) {
            $header = $_SERVER[ 'CONTENT_TYPE' ];
            $parts = explode( ';', $header );
            return strtolower( trim( $parts[ 0 ] ) );
        }
        return null;
    }

    /**
    * Return the request method, e.g. POST or GET.
    */
    public function getRequestMethod()
    {
        return $_SERVER[ 'REQUEST_METHOD' ];
    }

    /**
    * Return the content of the POST request body.
    */
    public function getPostBody()
    {
        return file_get_contents( 'php://input' );
    }

    /**
    * Return the value of the X-CSRF-Token header.
    */
    public function getCsrfToken()
    {
        return isset( $_SERVER[ 'HTTP_X_CSRF_TOKEN' ] ) ? $_SERVER[ 'HTTP_X_CSRF_TOKEN' ] : null;
    }

    private static function stripSlashesDeep( $value )
    {
        if ( is_array( $value ) )
            return array_map( array( 'System_Core_Request', 'stripSlashesDeep' ), $value );
        return stripslashes( $value );
    }
}
