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
* Convert text with markup to HTML formatting.
*/
class System_Web_MarkupProcessor
{
    private $tokens;
    private $index = 0;

    private $token;
    private $value;
    private $extra;
    private $rawValue;

    private $result = array();

    private $prettyPrint = false;

    const T_END = 0;
    const T_TEXT = 1;
    const T_START_CODE = 2;
    const T_START_LIST = 3;
    const T_START_QUOTE = 4;
    const T_START_RTL = 5;
    const T_END_CODE = 6;
    const T_END_LIST = 7;
    const T_END_QUOTE = 8;
    const T_END_RTL = 9;
    const T_LINK = 10;
    const T_BACKTICK = 11;
    const T_NEWLINE = 12;

    /**
    * Convert text with markup to HTML.
    * @param $text The text to convert.
    * @param $prettyPrint Set to true if pretty printing is used (output).
    * @return The HTML version of the text.
    */
    public static function convertToHtml( $text, &$prettyPrint )
    {
        $processor = new System_Web_MarkupProcessor( $text );
        $processor->next();
        $processor->parse();
        if ( $processor->prettyPrint )
            $prettyPrint = true;
        return implode( '', $processor->result );
    }

    /**
    * Convert text with markup to HTML which can be passed to a view.
    * @param $text The text to convert.
    * @param $prettyPrint Set to true if pretty printing is used (output).
    * @return The HTML version of the text wrapped in System_Web_RawValue
    * to prevent escaping it twice.
    */
    public static function convertToRawHtml( $text, &$prettyPrint )
    {
        return new System_Web_RawValue( self::convertToHtml( $text, $prettyPrint ) );
    }

    private function __construct( $text )
    {
        // similar to System_Web_LinkLocator's automatic links, but simpler because we know the exact beginning and end of the link
        $mail = '(?:mailto:)?[\w.%+-]+@[\w.-]+\.[a-z]{2,}';
        $url = '(?:(?:https?|ftp|file):\/\/|www\.|ftp\.|\\\\\\\\)[\w+&@#\/\\\\%=~|$?!:,.()-]+';
        $id = '#\d+';
        $link = "(?:$mail|$url|$id)";

        $this->tokens = preg_split( '/(\n|`[^`\n]+`|\[\/?(?:list|code|quote|rtl)(?:[ \t][^]\n]*)?\](?:[ \t]*\n)?|\[' . $link . '(?:[ \t][^]\n]*)?\])/ui', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
    }

    private function next()
    {
        if ( $this->index < count( $this->tokens ) && ( $this->index % 2 ) == 0 ) {
            $token = $this->tokens[ $this->index++ ];
            if ( $token != '' ) {
                $this->token = self::T_TEXT;
                $this->value = $this->rawValue = $token;
                return;
            }
        }

        if ( $this->index < count( $this->tokens ) ) {
            $token = $this->tokens[ $this->index++ ];
            $this->rawValue = $token;

            if ( $token[ 0 ] == '[' ) {
                $index = strcspn( $token, " \t]" );
                $this->value = substr( $token, 1, $index - 1 );
                $this->extra = trim( substr( $token, $index, strrpos( $token, ']' ) - $index ), " \t" );

                $tag = strtolower( $this->value );
                if ( $tag == 'code' )
                    $this->token = self::T_START_CODE;
                else if ( $tag == 'list' )
                    $this->token = self::T_START_LIST;
                else if ( $tag == 'quote' )
                    $this->token = self::T_START_QUOTE;
                else if ( $tag == 'rtl' )
                    $this->token = self::T_START_RTL;
                else if ( $tag == '/code' )
                    $this->token = self::T_END_CODE;
                else if ( $tag == '/list' )
                    $this->token = self::T_END_LIST;
                else if ( $tag == '/quote' )
                    $this->token = self::T_END_QUOTE;
                else if ( $tag == '/rtl' )
                    $this->token = self::T_END_RTL;
                else
                    $this->token = self::T_LINK;
            } else if ( $token[ 0 ] == '`' ) {
                $this->token = self::T_BACKTICK;
                $this->value = substr( $token, 1, -1 );
            } else {
                $this->token = self::T_NEWLINE;
            }

            return;
        }

        $this->token = self::T_END;
    }

    private function parse()
    {
        while ( $this->token != self::T_END )
            $this->parseBlock();
    }

    private function parseBlock()
    {
        switch ( $this->token ) {
            case self::T_START_CODE:
                $classes = '';
                if ( $this->extra != '' ) {
                    $lang = strtolower( $this->extra );
                    $langs = array( 'bash', 'c', 'c++', 'c#', 'css', 'html', 'java', 'javascript', 'js', 'perl', 'php', 'python', 'ruby', 'sh', 'sql', 'vb', 'xml' );
                    if ( array_search( $lang, $langs ) !== false ) {
                        $lang = str_replace( array( '+', '#' ), array( 'p', 's' ), $lang );
                        $classes = ' prettyprint lang-' . $lang;
                        $this->prettyPrint = true;
                    }
                }
                $this->result[] = '<pre class="code' . $classes . '">';
                $this->next();
                $this->parseCode();
                if ( $this->token == self::T_END_CODE )
                    $this->next();
                $this->result[] = '</pre>';
                break;

            case self::T_START_LIST:
                $this->result[] = '<ul><li>';
                $this->next();
                $this->parseList();
                if ( $this->token == self::T_END_LIST )
                    $this->next();
                $this->result[] = '</li></ul>';
                break;

            case self::T_START_QUOTE:
                $this->result[] = '<div class="quote">';
                if ( $this->extra != '' ) {
                    $title = System_Web_LinkLocator::convertToHtml( $this->extra );
                    if ( substr( $title, -1, 1 ) != ':' )
                        $title .= ':';
                    $this->result[] = '<div class="quote-title">' . $title . '</div>';
                }
                $this->next();
                $this->parseQuote();
                if ( $this->token == self::T_END_QUOTE )
                    $this->next();
                $this->result[] = '</div>';
                break;

            case self::T_START_RTL:
                $this->result[] = '<div class="rtl">';
                $this->next();
                $this->parseRtl();
                if ( $this->token == self::T_END_RTL )
                    $this->next();
                $this->result[] = '</div>';
                break;

            case self::T_TEXT:
            case self::T_BACKTICK:
            case self::T_LINK:
                $this->parseText();
                break;

            case self::T_NEWLINE:
                $this->result[] = "\n";
                $this->next();
                break;

            default:
                // ignore error (e.g. unbalanced closing tag)
                $this->next();
                break;
        }
    }

    private function parseText()
    {
        $tags = array();

        for ( ; ; ) {
            switch ( $this->token ) {
                case self::T_TEXT:
                    $subtokens = preg_split( '/(\*\*+|__+)/', $this->value, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
                    foreach ( $subtokens as $subtoken ) {
                        if ( $subtoken == '**' || $subtoken == '__' ) {
                            $tag = $subtoken == '**' ? 'strong' : 'em';
                            $key = array_search( $tag, $tags );
                            if ( $key === false ) {
                                $tags[] = $tag;
                                $this->result[] = '<' . $tag . '>';
                            } else {
                                for ( $i = count( $tags ) - 1; $i >= $key; $i-- )
                                    $this->result[] = '</' . $tags[ $i ] . '>';
                                array_splice( $tags, $key, 1 );
                                for ( $i = $key; $i < count( $tags ); $i++ )
                                    $this->result[] = '<' . $tags[ $i ] . '>';
                            }
                        } else {
                            $this->result[] = System_Web_LinkLocator::convertToHtml( $subtoken );
                        }
                    }
                    $this->next();
                    break;

                case self::T_BACKTICK:
                    $this->result[] = '<code>' . htmlspecialchars( $this->value ) . '</code>';
                    $this->next();
                    break;

                case self::T_LINK:
                    $title = ( $this->extra != '' ) ? $this->extra : $this->value;
                    $url = System_Web_LinkLocator::convertUrl( $this->value );
                    $this->result[] = '<a href="' . htmlspecialchars( $url ) . '">' . htmlspecialchars( $title ) . '</a>';
                    $this->next();
                    break;

                default:
                    for ( $i = count( $tags ) - 1; $i >= 0; $i-- )
                        $this->result[] = '</' . $tags[ $i ] . '>';
                    return;
            }
        }
    }

    private function parseCode()
    {
        $nest = 1;

        while ( $this->token != self::T_END ) {
            if ( $this->token == self::T_START_CODE ) {
                $nest++;
            } else if ( $this->token == self::T_END_CODE ) {
                if ( --$nest == 0 )
                    break;
            }

            $this->result[] = htmlspecialchars( $this->rawValue );
            $this->next();
        }
    }

    private function parseList()
    {
        $nest = 1;

        $level = $this->getItemLevel();
        if ( $level > 1 ) {
            $this->result[] = str_repeat( '<ul><li>', $level - 1 );
            $nest = $level;
        }

        while ( $this->token != self::T_END && $this->token != self::T_END_LIST ) {
            $this->parseBlock();

            $level = $this->getItemLevel();
            if ( $level > $nest ) {
                $this->result[] = str_repeat( '<ul><li>', $level - $nest );
                $nest = $level;
            } else if ( $level > 0 ) {
                if ( $level < $nest )
                    $this->result[] = str_repeat( '</li></ul>', $nest - $level );
                $this->result[] = '</li><li>';
                $nest = $level;
            }
        }

        if ( $nest > 1 )
            $this->result[] = str_repeat( '</li></ul>', $nest - 1 );
    }

    private function getItemLevel()
    {
        if ( $this->token == self::T_TEXT && preg_match( '/^[ \t]*(\*{1,6})[ \t](.*)/', $this->value, $parts ) ) {
            if ( $parts[ 2 ] != '' )
                $this->value = $parts[ 2 ];
            else
                $this->next();
            return strlen( $parts[ 1 ] );
        }
        return 0;
    }

    private function parseQuote()
    {
        while ( $this->token != self::T_END && $this->token != self::T_END_QUOTE )
            $this->parseBlock();
    }

    private function parseRtl()
    {
        while ( $this->token != self::T_END && $this->token != self::T_END_RTL )
            $this->parseBlock();
    }
}
