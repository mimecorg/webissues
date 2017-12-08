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
* Base class for components, views and helper web classes.
*
* It provides commonly used properties and methods for translating strings,
* handling URLs, etc.
*/
class System_Web_Base
{
    /**
    * @name Link Modes
    */
    /*@{*/
    /** Use configured server URL only in mailLink(). */
    const AutoLinks = 0;
    /** Use configured server URL in System_Web_LinkLocator and mailLink(). */
    const MailLinks = 1;
    /** Do not use internal links in System_Web_LinkLocator and mailLink(). */
    const NoInternalLinks = 2;
    /*@}*/

    protected $request = null;
    protected $response = null;
    protected $translator = null;

    private static $linkMode = self::AutoLinks;
    private static $baseUrl = null;

    /**
    * Constructor.
    */
    protected function __construct()
    {
        $application = System_Core_Application::getInstance();
        $this->request = $application->getRequest();
        $this->response = $application->getResponse();
        $this->translator = $application->getTranslator();
    }

    /**
    * Return a translated version of the source string.
    * The original string is returned if no translation is available.
    * Parameter placeholders (%%1, %%2, etc.) are replaced with additional
    * arguments passed to this function.
    * This method calls System_Core_Translator::translate() with appropriate
    * context based on the class name. Note that this mechanism only works when
    * the class calling tr() isn't itself inherited by another class.
    * @param $source The source string to translate.
    * @param $comment An optional comment explaining the use of the string
    * to the translators.
    */
    protected function tr( $source, $comment = null )
    {
        $args = func_get_args();
		return $this->translator->translate( System_Core_Translator::UserLanguage, get_class( $this ), $args );
    }

    /**
    * Convert a URL to absolute if necessary.
    * If the URL begins with a '/', the WI_BASE_URL is prepended to form
    * an absolute URL. This method should be used for creating hyperlinks
    * within the application.
    */
    protected function url( $url )
    {
        if ( $url[ 0 ] == '/' )
            return WI_BASE_URL . $url;
        return $url;
    }

    /**
    * Construct an URL merging query parameters passed to the current request
    * with given parameters. URL encoding is applied to parameter values.
    * Use a @c null value to remove an existing parameter from the query string.
    * @param $url The path part of the URL.
    * @param $params An optional array of query parameters to be merged with
    * current parameters.
    * @return The URL with query string.
    */
    protected function mergeQueryString( $url, $params = array() )
    {
        $current = $this->request->getQueryStrings();
        return $this->appendQueryString( $url, array_merge( $current, $params ) );
    }

    /**
    * Construct an URL merging some of the query parameters passed to the current
    * request with given parameters. URL encoding is applied to parameter values.
    * @param $url The path part of the URL.
    * @param $keys Array of keys of parameters to be preserved.
    * @param $params An optional array of query parameters to be merged with
    * current parameters.
    * @return The URL with query string.
    */
    protected function filterQueryString( $url, $keys, $params = array() )
    {
        $current = array();
        foreach ( $keys as $key ) {
            $value = $this->request->getQueryString( $key );
            if ( $value !== null )
                $current[ $key ] = $value;
        }
        return $this->appendQueryString( $url, array_merge( $current, $params ) );
    }

    /**
    * Construct an URL with given query parameters. URL encoding is applied
    * to parameter values.
    * @param $url The path part of the URL.
    * @param $params Array of query parameters to be appended to the URL.
    * @return The URL with query string.
    */
    protected function appendQueryString( $url, $params )
    {
        ksort( $params );

        $escaped = array();
        foreach ( $params as $key => $value ) {
            if ( isset( $value ) )
                $escaped[] = $key . '=' . urlencode( $value );
        }

        if ( empty( $escaped ) )
            return $url;

        return $url . '?' . join( '&amp;', $escaped );
    }

    /**
    * Create an HTML link.
    * @param $url The URL the link points to (absolute or relative to
    * WI_BASE_URL).
    * @param $text The text of the link.
    * @param $attributes Optional array of attributes to be added
    * to the @c a tag.
    * @return The link tag.
    */
    protected function link( $url, $text, $attributes = array() )
    {
        return $this->buildTag( 'a', array_merge( array( 'href' => $this->url( $url ) ), $attributes ), $text );
    }

    /**
    * Create an HTML link containing a list item.
    * @param $url The URL the link points to (absolute or relative to
    * WI_BASE_URL).
    * @param $text The text of the link.
    * @param $attributes Optional array of attributes to be added
    * to the @c a tag.
    * @return The link tag.
    */
    protected function linkItem( $url, $text, $attributes = array() )
    {
        return $this->buildTag( 'a', array_merge( array( 'href' => $this->url( $url ) ), $attributes ), '<li>' . $text . '</li>' );
    }

    /**
    * Create an HTML image tag.
    * @param $source The source of the image (absolute or relative to
    * WI_BASE_URL).
    * @param $text Text of the alt attribute.
    * @param $attributes Optional array of attributes to be added
    * to the @c img tag.
    * @return The image tag.
    */
    protected function image( $source, $text, $attributes = array() )
    {
        return $this->buildTag( 'img', array_merge( array( 'src' => $this->url( $source ), 'alt' => $text,
            'title' => $text, 'width' => 16, 'height' => 16, 'class' => 'icon' ), $attributes ) );
    }

    /**
    * Create an image and text label.
    * @param $source The source of the image (absolute or relative to
    * WI_BASE_URL).
    * @param $text Text of the alt attribute.
    * @param $attributes Optional array of attributes to be added
    * to the @c img tag.
    * @return The image and text label.
    */
    protected function imageAndText( $source, $text, $attributes = array() )
    {
        return $this->image( $source, $text, $attributes ) . "\n" . $text;
    }

    /**
    * Create an HTML link containing an image.
    * @param $url The URL the link points to (absolute or relative to
    * WI_BASE_URL).
    * @param $source The source of the image (absolute or relative to
    * WI_BASE_URL).
    * @param $text Text of the alt attribute.
    * @param $imageAttributes Optional array of attributes to be added
    * to the @c img tag.
    * @param $linkAttributes Optional array of attributes to be added
    * to the @c a tag.
    * @return The link tag.
    */
    protected function imageLink( $url, $source, $text, $imageAttributes = array(), $linkAttributes = array() )
    {
        return $this->link( $url, "\n" . $this->image( $source, $text, $imageAttributes ), array_merge( array( 'class' => 'image-link' ), $linkAttributes ) );
    }

    /**
    * Create an HTML link containing an image and text label.
    * @param $url The URL the link points to (absolute or relative to
    * WI_BASE_URL).
    * @param $source The source of the image (absolute or relative to
    * WI_BASE_URL).
    * @param $text Text of the label.
    * @param $imageAttributes Optional array of attributes to be added
    * to the @c img tag.
    * @param $linkAttributes Optional array of attributes to be added
    * to the @c a tag.
    * @param $tipText Optional text of the image alt attribute.
    * @return The link tag.
    */
    protected function imageAndTextLink( $url, $source, $text, $imageAttributes = array(), $linkAttributes = array(), $tipText = null )
    {
        return $this->link( $url, "\n" . $this->image( $source, $tipText != null ? $tipText : $text, $imageAttributes ), $linkAttributes )
            . $this->link( $url, $text, $linkAttributes );
    }

    /**
    * Create an HTML link containing a list item with image and text label.
    * @param $url The URL the link points to (absolute or relative to
    * WI_BASE_URL).
    * @param $source The source of the image (absolute or relative to
    * WI_BASE_URL).
    * @param $text Text of the label.
    * @param $imageAttributes Optional array of attributes to be added
    * to the @c img tag.
    * @param $linkAttributes Optional array of attributes to be added
    * to the @c a tag.
    * @param $tipText Optional text of the image alt attribute.
    * @return The link tag.
    */
    protected function imageAndTextLinkItem( $url, $source, $text, $imageAttributes = array(), $linkAttributes = array(), $tipText = null )
    {
        return $this->link( $url, '<li>' . $this->image( $source, $tipText != null ? $tipText : $text, $imageAttributes ) . ' ' . $text . '</li>', $linkAttributes );
    }

    /**
    * Create an HTML tag with attributes. Attribute values and text should
    * be already escaped.
    * @param $name The name of the tag.
    * @param $attributes Array of attributes to be added to the tag. If a value
    * is @c null or @c false, the attribute is omitted. If a value is @c true
    * the attribute name is duplicated as it's value.
    * @param $text Optional text to be placed between opening and closing tag.
    * If left blank, an empty tag is created. If @c true is passed, only
    * the opening tag is created.
    * @return The created tag.
    */
    protected function buildTag( $name, $attributes, $text = null )
    {
        $tag = $name;
        foreach ( $attributes as $key => $value ) {
            if ( $value === null || $value === false )
                continue;
            if ( $value === true )
                $value = $key;
            $tag .= " $key=\"$value\"";
        }
        if ( $text === null )
            return "<$tag>";
        else if ( $text === true )
            return "<$tag>\n";
        else
            return "<$tag>$text</$name>\n";
    }

    /**
    * Truncate text if it is longer than given length.
    * A tooltip containing the original text is created if text is truncated.
    * @param $text The text to truncate.
    * @param $maxLength The maximum length of the text.
    * @return The truncated text.
    */
    protected function truncate( $text, $maxLength )
    {
        if ( mb_strlen( $text ) > $maxLength ) {
            $toolTip = System_Web_Escaper::escape( $text );
            $truncated = System_Web_Escaper::escape( mb_substr( $text, 0, $maxLength - 3 ) . '...' );
            return new System_Web_RawValue( "<span title=\"$toolTip\">$truncated</span>" );
        }

        return $text;
    }

    /**
    * Create an HTML link if the server URL for emails is configured. Only the text
    * is returned if server URL is not configured or link mode is set to NoInternalLinks.
    */
    protected function mailLink( $url, $text, $attributes = array(), $settingOnly = false )
    {
        $baseUrl = self::getBaseUrl( $settingOnly );
        if ( $baseUrl != '' )
            return $this->link( $baseUrl . $url, $text, $attributes );
        else
            return $text;
    }

    /**
    * Set the current link mode for System_Web_LinkLocator and mailLink().
    */
    public static function setLinkMode( $mode )
    {
        self::$linkMode = $mode;
    }

    /**
    * Get the current link mode.
    */
    public static function getLinkMode()
    {
        return self::$linkMode;
    }

    /**
    * Return the server URL for emails unless the mode is set to NoInternalLinks.
    */
    public static function getBaseUrl()
    {
        if ( self::$linkMode == self::NoInternalLinks )
            return '';

        if ( self::$baseUrl === null ) {
            $serverManager = new System_Api_ServerManager();
            $baseUrl = $serverManager->getSetting( 'base_url' );
            if ( $baseUrl != '' )
                self::$baseUrl = rtrim( $baseUrl, '/' );
            else
                self::$baseUrl = '';
        }

        return self::$baseUrl;
    }
}
