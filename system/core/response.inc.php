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
* Wrapper for a response to be sent to the client.
*
* It holds the status code, content and headers of the response and provides
* a buffering mechanism to prevent sending a response before it's complete.
*
* An instance of this class is accessible through the System_Core_Application
* object and as a property of classes inheriting System_Web_Base.
*/
class System_Core_Response
{
    private $bufferLevel = 0;

    private $sending = false;

    private $status = null;
    private $contentType = null;
    private $content = null;
    private $attachment = null;
    private $headers = array();

    private $bufferedOutput = '';

    /**
    * Constructor.
    */
    public function __construct()
    {
    }

    /**
    * Set the status code of the response.
    * @param $status A HTTP/1.1 status code, for example '401 Unauthorized'.
    */
    public function setStatus( $status )
    {
        $this->status = $status;
    }

    /**
    * Return the status code of the response.
    */
    public function getStatus()
    {
        return $this->status;
    }

    /**
    * Set the content type of the response.
    * @param $contentType A MIME type of the response content, for example
    * 'text/html; charset=UTF-8'.
    */
    public function setContentType( $contentType )
    {
        $this->contentType = $contentType;
    }

    /**
    * Return the content type of the response.
    */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
    * Set the content of the response.
    * @param $content A string containing the response content.
    */
    public function setContent( $content )
    {
        $this->content = $content;
    }

    /**
    * Return the content of the response.
    */
    public function getContent()
    {
        return $this->content;
    }

    /**
    * Set the content of the response from the given file.
    * @param $attachment A System_Core_Attachment object wrapping
    * a file stored in memory or in the file system.
    */
    public function setAttachment( $attachment )
    {
        $attachment->validate();
        $this->attachment = $attachment;
    }

    /**
    * Add a header to the response. The previous value of the
    * header is replaced.
    * @param $key Header name.
    * @param $value Header value.
    */
    public function setCustomHeader( $key, $value )
    {
        $this->headers[ $key ] = $value;
    }

    /**
    * Return @c true if sending the response has already begun.
    */
    public function isSending()
    {
        return $this->sending;
    }

    /**
    * Return the output buffered between initialize() and send()
    * which is normally discarded.
    */
    public function getBufferedOutput()
    {
        return $this->bufferedOutput;
    }

    /**
    * Initialize the response.
    * All output between initialize() and send() is buffered to prevent
    * sending the response too early.
    */
    public function initialize()
    {
        ob_start();
        $this->bufferLevel = ob_get_level();
    }

    /**
    * Send the response headers and content to the client.
    * An exception is thrown if sending the response has already started
    * or if unbalanced output buffering was detected.
    * This method is normally called by System_Core_Application::run().
    */
    public function send()
    {
        $this->prepareToSend();

        if ( $this->status != null )
            header( 'HTTP/1.1 ' . $this->status );

        foreach ( $this->headers as $key => $value )
            header( $key . ': ' . $value );

        if ( $this->contentType != null )
            header( 'Content-Type: ' . $this->contentType );

        if ( $this->attachment != null )
            $this->attachment->outputData();
        else
            echo $this->content;
    }

    /**
    * Redirect the client to the given URL and terminate the request.
    * @param $url A URL relative to the base URL or an absolute URL.
    */
    public function redirect( $url )
    {
        $url = str_replace( '&amp;', '&', $url );

        if ( $url[ 0 ] == '/' )
            $url = WI_BASE_URL . $url;

        $this->prepareToRedirect();

        header( 'Location: ' . $url );

        exit;
    }

    /**
    * Remove all output buffering and clean the response headers and content.
    * This method is called before System_Core_Application::displayErrorPage()
    * to check if the error page can be displayed.
    * @return @c true if response was reset, @c false if sending the response
    * has already started.
    */
    public function reset()
    {
        if ( $this->sending || headers_sent() || ob_get_level() < $this->bufferLevel )
            return false;

        while ( ob_get_level() > $this->bufferLevel )
            ob_end_clean();

        $this->status = null;
        $this->contentType = null;
        $this->content = null;
        $this->attachment = null;
        $this->headers = array();

        return true;
    }

    private function prepareToSend()
    {
        if ( $this->sending )
            throw new System_Core_Exception( 'Sending response already started' );

        if ( headers_sent( $file, $line ) )
            throw new System_Core_ErrorException( E_ERROR, 'Output generated before sending the response', $file, $line );

        if ( ob_get_level() != $this->bufferLevel )
            throw new System_Core_Exception( 'Unbalanced output buffering detected' );

        $this->bufferedOutput = ob_get_clean();
        $this->sending = true;
    }

    private function prepareToRedirect()
    {
        if ( $this->sending )
            throw new System_Core_Exception( 'Sending response already started' );

        if ( headers_sent( $file, $line ) )
            throw new System_Core_ErrorException( E_ERROR, 'Output generated before sending the response', $file, $line );

        while ( ob_get_level() > $this->bufferLevel )
            ob_end_clean();

        $this->sending = true;
    }
}
