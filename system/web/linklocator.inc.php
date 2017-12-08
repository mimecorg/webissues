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
* Helper methods for converting URLs into clickable links.
*
* The following items are recognized as links:
*  - email addresses (optionally starting with mailto:)
*  - URLs starting with http://, https://, ftp://, file://, www., ftp. and \\ (UNC paths)
*  - links to issues, comments or attachments starting with #
*
* Also the special HTML characters are converted to entities.
*/
class System_Web_LinkLocator extends System_Web_Base
{
    /**
    * Convert text with links to HTML.
    * @param $text The plain text to convert.
    * @param $maxLength Optional maximum length of the text.
    * @return The HTML version of the text.
    */
    public static function convertToHtml( $text, $maxLength = null )
    {
        // regex for emails and URLs is based on:
        //  - http://www.regular-expressions.info/email.html 
        //  - http://www.regexguru.com/2008/11/detecting-urls-in-a-block-of-text/

        $mail = '\b(?:mailto:)?[\w.%+-]+@[\w.-]+\.[a-z]{2,}\b';
        $url = '(?:\b(?:(?:https?|ftp|file):\/\/|www\.|ftp\.)|\\\\\\\\)(?:\([\w+&@#\/\\\\%=~|$?!:,.-]*\)|[\w+&@#\/\\\\%=~|$?!:,.-])*(?:\([\w+&@#\/\\\\%=~|$?!:,.-]*\)|[\w+&@#\/\\\\%=~|$])';
        $id = '#\d+\b';
        $pattern = "/($mail|$url|$id)/ui";

        $matches = preg_split( $pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE );

        $result = array();
        foreach ( $matches as $i => $match ) {
            if ( $i % 2 == 0 ) {
                if ( $maxLength !== null ) {
                    $length = mb_strlen( $match );
                    if ( $length > $maxLength - 3 ) {
                        if ( $maxLength > 3 )
                            $result[] = htmlspecialchars( mb_substr( $match, 0, $maxLength - 3 ) );
                        $result[] = '...';
                        break;
                    }
                    $maxLength -= $length;
                }
                $result[] = htmlspecialchars( $match );
            } else {
                $url = htmlspecialchars( self::convertUrl( $match ) );
                if ( $maxLength !== null ) {
                    $length = mb_strlen( $match );
                    if ( $length > $maxLength - 3 ) {
                        if ( $maxLength > 3 )
                            $result[] = "<a href=\"$url\">" . htmlspecialchars( mb_substr( $match, 0, $maxLength - 3 ) ) . '...</a>';
                        else
                            $result[] = '...';
                        break;
                    }
                    $maxLength -= $length;
                }
                $result[] = "<a href=\"$url\">" . htmlspecialchars( $match ) . '</a>';
            }
        }

        return implode( '', $result );
    }

    /**
    * Convert text with links to HTML which can be passed to a view.
    * @param $text The plain text to convert.
    * @return The HTML version of the text wrapped in System_Web_RawValue
    * to prevent escaping it twice.
    */
    public static function convertToRawHtml( $text )
    {
        return new System_Web_RawValue( self::convertToHtml( $text ) );
    }

    /**
    * Convert text with links to HTML and truncate it if it is too long.
    * A tooltip containing the original text is created if text is truncated.
    * @param $text The plain text to convert.
    * @param $maxLength The maximum length of the text.
    * @return The HTML version of the text wrapped in System_Web_RawValue.
    */
    public static function convertAndTruncate( $text, $maxLength )
    {
        if ( mb_strlen( $text ) > $maxLength ) {
            $toolTip = htmlspecialchars( $text );
            $truncated = self::convertToHtml( $text, $maxLength );
            return new System_Web_RawValue( "<span title=\"$toolTip\">$truncated</span>" );
        }

        return self::convertToRawHtml( $text );
    }

    public static function convertUrl( $url )
    {
        if ( $url[ 0 ] == '#' ) {
            if ( self::getLinkMode() == self::AutoLinks ) {
                $baseUrl = WI_BASE_URL;
                if ( $baseUrl != '' && System_Core_Application::getInstance()->getRequest()->isRelativePathUnder( '/mobile' ) )
                    $baseUrl .= '/mobile';
            } else {
                $baseUrl = self::getBaseUrl();
            }
            if ( $baseUrl != '' )
                return $baseUrl . '/client/index.php?item=' . substr( $url, 1 );
            else
                return '#item' . substr( $url, 1 );
        }

        if ( strtolower( substr( $url, 0, 4 ) ) == 'www.' )
            return 'http://' . $url;
        else if ( strtolower( substr( $url, 0, 4 ) ) == 'ftp.' )
            return 'ftp://' . $url;
        else if ( substr( $url, 0, 2 ) == '\\\\' )
            return 'file:///' . $url;
        else if ( strpos( $url, ':' ) === false )
            return 'mailto:' . $url;
        else
            return $url;
    }
}
