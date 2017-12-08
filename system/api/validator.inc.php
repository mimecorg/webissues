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
* Validate arguments, attribute values, settings and preferences.
*
* This class should be used for validating values before storing them
* in the database. Localized date and number formats are not supported by this
* class. All methods of this class do not return any value, instead they throw
* a System_Api_Error exception if validation fails.
*/
class System_Api_Validator
{
    /**
    * @name Flags
    */
    /*@{*/
    /**
    * Mark string value as not required. If this flag is set, passing an empty
    * string to checkString doesn't generate an error.
    */
    const AllowEmpty = 1;
    /**
    * Mark string value as multi-line. If this flag is set, the string may
    * begin with a space and contain TAB and NL characters and multiple
    * spaces.
    */
    const MultiLine = 2;
    /*@}*/

    protected $locale = null;

    private $projectId = null;

    /**
    * Constructor.
    */
    public function __construct()
    {
        $this->locale = new System_Api_Locale();
    }

    /**
    * Set the identifier of the project used as context of validation.
    * This is used for validating USER attributes when only project members
    * are accepted.
    */
    public function setProjectId( $projectId )
    {
        $this->projectId = $projectId;
    }

    /**
    * Check if the argument is a valid string.
    * @param $string The string argument to validate.
    * @param $maxLength Optional maximum allowed length of the string.
    * @param $flags If AllowEmpty is given, value is not required. If MultiLine
    * is given, value can be a multi-line string.
    */
    public function checkString( $string, $maxLength = null, $flags = 0 )
    {
        if ( $string == '' ) {
            if ( $flags & self::AllowEmpty )
                return;
            throw new System_Api_Error( System_Api_Error::EmptyValue );
        }

        if ( !mb_check_encoding( $string ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        if ( $maxLength !== null && mb_strlen( $string ) > $maxLength )
            throw new System_Api_Error( System_Api_Error::StringTooLong );


        if ( $flags & self::MultiLine ) {
            $char = mb_substr( $string, -1, 1 );
            if ( $char == ' ' || $char == '\n' || $char == '\t' )
                throw new System_Api_Error( System_Api_Error::InvalidString );

            // no control characters allowed except TAB and LF
            if ( preg_match( '/[\x00-\x08\x0b-\x1f\x7f]/', $string ) ) 
                throw new System_Api_Error( System_Api_Error::InvalidString ); 
        } else {
            if ( mb_substr( $string, -1, 1 ) == ' ' )
                throw new System_Api_Error( System_Api_Error::InvalidString );

            if ( mb_substr( $string, 0, 1 ) == ' ' )
                throw new System_Api_Error( System_Api_Error::InvalidString );

            // no control characters and multiple spaces allowed
            if ( preg_match( '/([\x00-\x1f\x7f]|  )/', $string ) )
                throw new System_Api_Error( System_Api_Error::InvalidString );
        }
    }

    /**
    * Check if the argument is a valid access level constant as defined in
    * System_Const.
    * @param $access The integer argument to validate.
    */
    public function checkAccessLevel( $access )
    {
        if ( !is_int( $access ) || $access < System_Const::NoAccess || $access > System_Const::AdministratorAccess )
            throw new System_Api_Error( System_Api_Error::InvalidAccessLevel );
    }

    /**
    * Check if the argument is a valid alert email constant as defined in
    * System_Const.
    * @param $access The integer argument to validate.
    */
    public function checkAlertEmail( $email )
    {
        if ( !is_int( $email ) || $email < System_Const::NoEmail || $email > System_Const::SummaryReportEmail )
            throw new System_Api_Error( System_Api_Error::InvalidAlertEmail );
    }

    /**
    * Check if the argument is a valid set of days of week.
    * @param $email The alert email constant.
    * @param $value Set of days of week.
    */
    public function checkSummaryDays( $email, $value )
    {
        $this->checkString( $value, 255, self::AllowEmpty );

        if ( $email == System_Const::SummaryNotificationEmail || $email == System_Const::SummaryReportEmail ) {
            if ( $value != '' )
                $this->checkIntArray( $value, 0, 6 );
            else
                throw new System_Api_Error( System_Api_Error::EmptyValue );
        } else {
            if ( $value != '' )
                throw new System_Api_Error( System_Api_Error::InvalidValue );
        }
    }

    /**
    * Check if the argument is a valid set of hours.
    * @param $email The alert email constant.
    * @param $value Set of hours.
    */
    public function checkSummaryHours( $email, $value )
    {
        $this->checkString( $value, 255, self::AllowEmpty );

        if ( $email == System_Const::SummaryNotificationEmail || $email == System_Const::SummaryReportEmail ) {
            if ( $value != '' )
                $this->checkIntArray( $value, 0, 23 );
            else
                throw new System_Api_Error( System_Api_Error::EmptyValue );
        } else {
            if ( $value != '' )
                throw new System_Api_Error( System_Api_Error::InvalidValue );
        }
    }

    /**
    * Check if the argument is a valid text format constant as defined in
    * System_Const.
    * @param $format The integer argument to validate.
    */
    public function checkTextFormat( $format )
    {
        if ( !is_int( $format ) || $format < System_Const::PlainText || $format > System_Const::TextWithMarkup )
            throw new System_Api_Error( System_Api_Error::InvalidTextFormat );
    }

    /**
    * Check if the argument is either 0 or 1.
    * @param $value The integer argument to validate.
    */
    public function checkBooleanValue( $value )
    {
        $this->checkIntegerValue( $value, 0, 1 );
    }

    /**
    * Check if the argument is an integer in the given range.
    * @param $value The integer argument to validate.
    * @param $min The optional minimum allowed value.
    * @param $max The optional maximum allowed value.
    */
    public function checkIntegerValue( $value, $min = null, $max = null )
    {
        if ( !is_int( $value ) )
            throw new System_Api_Error( System_Api_Error::InvalidValue );
        if ( $min !== null && $value < (int)$min )
            throw new System_Api_Error( System_Api_Error::NumberTooLittle );
        if ( $max !== null && $value > (int)$max )
            throw new System_Api_Error( System_Api_Error::NumberTooGreat );
    }

    private function checkLength( $value, $minLength, $maxLength )
    {
        $length = mb_strlen( $value );
        if ( $minLength !== null && $length < (int)$minLength )
            throw new System_Api_Error( System_Api_Error::StringTooShort );
        if ( $maxLength !== null && $length > (int)$maxLength )
            throw new System_Api_Error( System_Api_Error::StringTooLong );
    }

    /**
    * Check if the argument is a decimal number.
    * @param $value The string argument to validate.
    * @param $decimal The number of decimal digits.
    * @param $min The optional minimum allowed value.
    * @param $max The optional maximum allowed value.
    */
    public function checkDecimalNumber( $value, $decimal, $min = null, $max = null )
    {
        // make sure the number is well formed
        if ( $value !== number_format( $value, $decimal, '.', '' ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        // make sure the number doesn't exceed 14 digits of precision
        if ( abs( $value ) >= pow( 10.0, 14 - $decimal ) )
            throw new System_Api_Error( System_Api_Error::TooManyDigits );

        if ( $min !== null && (float)$value < (float)$min )
            throw new System_Api_Error( System_Api_Error::NumberTooLittle );
        if ( $max !== null && (float)$value > (float)$max )
            throw new System_Api_Error( System_Api_Error::NumberTooGreat );
    }

    /**
    * Check if the argument is a valid email address.
    * @param $value The string argument to validate.
    */
    public function checkEmailAddress( $value )
    {
        if ( !preg_match( '/^[\w.%+-]+@[\w.-]+\.[a-z]{2,}$/ui', $value ) )
            throw new System_Api_Error( System_Api_Error::InvalidEmail );
    }

    /**
    * Check if the argument is a valid date.
    * @param $value Value in 'yyyy-mm-dd' format.
    */
    public function checkDate( $value )
    {
        if ( !preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d)$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        if ( !checkdate( $matches[ 2 ], $matches[ 3 ], $matches[ 1 ] ) )
            throw new System_Api_Error( System_Api_Error::InvalidDate );
    }

    /**
    * Check if the argument is a valid time.
    * @param $value Time in 'hh:mm' format.
    */
    public function checkTime( $value )
    {
        if ( !preg_match( '/^(\d\d):(\d\d)$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        if ( $matches[ 1 ] > 23 || $matches[ 2 ] > 59 )
            throw new System_Api_Error( System_Api_Error::InvalidTime );
    }

    /**
    * Check if the argument is a valid date and time.
    * @param $value Value in 'yyyy-mm-dd hh:mm' format.
    */
    public function checkDateTime( $value )
    {
        if ( !preg_match( '/^(\d\d\d\d)-(\d\d)-(\d\d) (\d\d):(\d\d)$/', $value, $matches ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );

        if ( !checkdate( $matches[ 2 ], $matches[ 3 ], $matches[ 1 ] ) )
            throw new System_Api_Error( System_Api_Error::InvalidDate );

        if ( $matches[ 4 ] > 23 || $matches[ 5 ] > 59 )
            throw new System_Api_Error( System_Api_Error::InvalidTime );
    }

    /**
    * Check if the argument is a valid time zone.
    * @param $value Name of the time zone.
    */
    public function checkTimeZone( $value )
    {
        if ( array_search( $value, $this->locale->getAvailableTimeZones() ) === false )
            throw new System_Api_Error( System_Api_Error::NoMatchingItem );
    }

    /**
    * Check if the attribute type definition is valid.
    * @param $definition The definition to validate.
    */
    public function checkAttributeDefinition( $definition )
    {
        if ( preg_match( '/[\x00-\x1f\x7f]/', $definition ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        $info = System_Api_DefinitionInfo::fromString( $definition );

        $requiredKeys = array();
        $optionalKeys = array( 'required' => 'i', 'default' => 's' );

        switch ( $info->getType() ) {
            case 'TEXT':
                $optionalKeys[ 'multi-line' ] = 'i';
                $optionalKeys[ 'max-length' ] = 'i';
                $optionalKeys[ 'min-length' ] = 'i';
                break;

            case 'ENUM':
                $requiredKeys[ 'items' ] = 'a';
                $optionalKeys[ 'editable' ] = 'i';
                $optionalKeys[ 'multi-select' ] = 'i';
                $optionalKeys[ 'max-length' ] = 'i';
                $optionalKeys[ 'min-length' ] = 'i';
                break;

            case 'NUMERIC':
                $optionalKeys[ 'decimal' ] = 'i';
                $optionalKeys[ 'min-value' ] = 's';
                $optionalKeys[ 'max-value' ] = 's';
                $optionalKeys[ 'strip' ] = 'i';
                break;

            case 'DATETIME':
                $optionalKeys[ 'time' ] = 'i';
                $optionalKeys[ 'local' ] = 'i';
                break;

            case 'USER':
                $optionalKeys[ 'members' ] = 'i';
                $optionalKeys[ 'multi-select' ] = 'i';
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidDefinition );
        }

        if ( !$info->checkMetadataKeys( $requiredKeys, $optionalKeys ) )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        foreach ( $info->getAllMetadata() as $key => $value ) {
            switch ( $key ) {
                case 'min-value':
                case 'max-value':
                    $this->checkString( $value, System_Const::ValueMaxLength, self::AllowEmpty );
                    break;

                case 'required':
                case 'multi-line':
                case 'editable':
                case 'multi-select':
                case 'strip':
                case 'time':
                case 'local':
                case 'members':
                    $this->checkBooleanValue( $value );
                    break;

                case 'decimal':
                    $this->checkIntegerValue( $value, 0, 6 );
                    break;

                case 'max-length':
                case 'min-length':
                    $this->checkIntegerValue( $value, 1, System_Const::ValueMaxLength );
                    break;

                case 'items':
                    if ( empty( $value ) )
                        throw new System_Api_Error( System_Api_Error::NoItems );
                    foreach ( $value as $item )
                        $this->checkString( $item, System_Const::ValueMaxLength );
                    if ( count( array_unique( $value ) ) != count( $value ) )
                        throw new System_Api_Error( System_Api_Error::DuplicateItems );
                    break;
            }
        }

        $default = $info->getMetadata( 'default', '' );

        $flags = self::AllowEmpty;
        if ( $info->getType() == 'TEXT' && $info->getMetadata( 'multi-line', 0 ) )
            $flags |= self::MultiLine;
        $this->checkString( $default, System_Const::ValueMaxLength, $flags );

        switch ( $info->getType() ) {
            case 'TEXT':
                $minLength = $info->getMetadata( 'min-length' );
                $maxLength = $info->getMetadata( 'max-length' );
                if ( $minLength !== null && $maxLength !== null && (int)$minLength > (int)$maxLength )
                    throw new System_Api_Error( System_Api_Error::InvalidLimits );
                break;

            case 'ENUM':
                $editable = $info->getMetadata( 'editable', 0 );
                $multiSelect = $info->getMetadata( 'multi-select', 0 );
                $minLength = $info->getMetadata( 'min-length' );
                $maxLength = $info->getMetadata( 'max-length' );
                if ( ( !$editable || $multiSelect ) && ( $minLength !== null || $maxLength !== null ) )
                    throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                if ( $minLength !== null && $maxLength !== null && (int)$minLength > (int)$maxLength )
                    throw new System_Api_Error( System_Api_Error::InvalidLimits );
                if ( $minLength !== null || $maxLength !== null ) {
                    foreach ( $info->getMetadata( 'items' ) as $item )
                        $this->checkLength( $item, $minLength, $maxLength );
                }
                if ( $multiSelect ) {
                    foreach ( $info->getMetadata( 'items' ) as $item )
                        $this->checkItem( $item );
                }
                break;

            case 'NUMERIC':
                $decimal = $info->getMetadata( 'decimal', 0 );
                $minimum = $info->getMetadata( 'min-value' );
                $maximum = $info->getMetadata( 'max-value' );
                $strip = $info->getMetadata( 'strip', 0 );
                if ( $minimum !== null )
                    $this->checkDecimalNumber( $minimum, $decimal );
                if ( $maximum !== null )
                    $this->checkDecimalNumber( $maximum, $decimal );
                if ( $minimum !== null && $maximum !== null && (float)$minimum > (float)$maximum )
                    throw new System_Api_Error( System_Api_Error::InvalidLimits );
                if ( $decimal == 0 && $strip )
                    throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                break;

            case 'DATETIME':
                $time = $info->getMetadata( 'time', 0 );
                $local = $info->getMetadata( 'local', 0 );
                if ( !$time && $local )
                    throw new System_Api_Error( System_Api_Error::InvalidDefinition );
                break;
        }

        if ( $default != '' )
            $this->checkFilterValue( $info, $default );
    }

    /**
    * Check if the definition is compatible with current attribute definition.
    * @param $attribute
    * @param $definition
    */
    public function checkCompatibleType( $attribute, $definition )
    {
        $info = System_Api_DefinitionInfo::fromString( $attribute[ 'attr_def' ] );
        $oldType = $info->getType();

        $info = System_Api_DefinitionInfo::fromString( $definition );
        $newType = $info->getType();

        $compatibleTypes = array( 'TEXT', 'ENUM', 'USER' );

        if ( in_array( $oldType, $compatibleTypes ) ) {
            if ( !in_array( $newType, $compatibleTypes ) )
                throw new System_Api_Error( System_Api_Error::IncompatibleType );
        } else {
            if ( $newType != $oldType )
                throw new System_Api_Error( System_Api_Error::IncompatibleType );
        }
    }

    /**
    * Check if the attribute value is valid for given type definition.
    * @param $definition The definition of the attribute type.
    * @param $value The value to validate.
    */
    public function checkAttributeValue( $definition, $value )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        $flags = self::AllowEmpty;
        if ( $info->getType() == 'TEXT' && $info->getMetadata( 'multi-line', 0 ) )
            $flags |= self::MultiLine;
        $this->checkString( $value, System_Const::ValueMaxLength, $flags );

        $this->checkAttributeValueInfo( $info, $value );
    }

    /**
    * Check if the attribute value is valid for given type definition.
    * @param $info The System_Api_DefinitionInfo object containing type
    * definition.
    * @param $value The value to validate.
    */
    protected function checkAttributeValueInfo( $info, $value )
    {
        if ( $value == '' ) {
            if ( $info->getMetadata( 'required', 0 ) )
                throw new System_Api_Error( System_Api_Error::EmptyValue );
            return;
        }

        switch ( $info->getType() ) {
            case 'TEXT':
                $this->checkLength( $value, $info->getMetadata( 'min-length' ), $info->getMetadata( 'max-length' ) );
                break;

            case 'ENUM':
                if ( $info->getMetadata( 'multi-select', 0 ) ) {
                    $this->checkList( $value );

                    if ( !$info->getMetadata( 'editable', 0 ) ) {
                        $items = $info->getMetadata( 'items' );
                        $parts = explode( ', ', $value );

                        foreach ( $parts as $part ) {
                            if ( array_search( $part, $items ) === false )
                                throw new System_Api_Error( System_Api_Error::NoMatchingItem );
                        }

                        if ( count( array_unique( $parts ) ) != count( $parts ) )
                            throw new System_Api_Error( System_Api_Error::DuplicateItems );
                    }
                } else {
                    if ( !$info->getMetadata( 'editable', 0 ) ) {
                        $items = $info->getMetadata( 'items' );
                        if ( array_search( $value, $items ) === false )
                            throw new System_Api_Error( System_Api_Error::NoMatchingItem );
                    } else {
                        $this->checkLength( $value, $info->getMetadata( 'min-length' ), $info->getMetadata( 'max-length' ) );
                    }
                }
                break;

            case 'NUMERIC':
                $this->checkDecimalNumber( $value, $info->getMetadata( 'decimal', 0 ),
                    $info->getMetadata( 'min-value' ), $info->getMetadata( 'max-value' ) );
                break;

            case 'DATETIME':
                if ( $info->getMetadata( 'time', 0 ) )
                    $this->checkDateTime( $value );
                else
                    $this->checkDate( $value );
                break;

            case 'USER':
                $members = $info->getMetadata( 'members', 0 );
                $userManager = new System_Api_UserManager();
                if ( $info->getMetadata( 'multi-select', 0 ) ) {
                    $this->checkList( $value );

                    $parts = explode( ', ', $value );

                    foreach ( $parts as $part )
                        $userManager->checkUserName( $part, $members ? $this->projectId : null );

                    if ( count( array_unique( $parts ) ) != count( $parts ) )
                        throw new System_Api_Error( System_Api_Error::DuplicateItems );
                } else {
                    $userManager->checkUserName( $value, $members ? $this->projectId : null );
                }
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidDefinition );
        }
    }

    /**
    * Check if the server setting is valid.
    * @param $key Name of the setting to validate.
    * @param $value Value of the setting to validate.
    */
    public function checkSetting( $key, $value )
    {
        $this->checkString( $value, null, self::AllowEmpty );

        switch ( $key ) {
            case 'language':
                $this->checkLocale( $key, $value );
                break;

            case 'number_format':
            case 'date_format':
            case 'time_format':
            case 'first_day_of_week':
            case 'time_zone':
                if ( $value != '' )
                    $this->checkLocale( $key, $value );
                break;

            case 'project_page_size':
            case 'folder_page_size':
            case 'history_page_size':
            case 'project_page_mobile':
            case 'folder_page_mobile':
            case 'history_page_mobile':
                $this->checkDecimalNumber( $value, 0, 1, 100 );
                break;

            case 'comment_max_length':
                $this->checkDecimalNumber( $value, 0, 1000, 100000 );
                break;

            case 'file_max_size':
                $this->checkDecimalNumber( $value, 0, 16 * 1024, 256 * 1024 * 1024 );
                break;

            case 'file_db_max_size':
                $this->checkDecimalNumber( $value, 0, 0, System_Const::INT_MAX );
                break;

            case 'session_max_lifetime':
                $this->checkDecimalNumber( $value, 0, 300, 86400 );
                break;

            case 'log_max_lifetime':
            case 'register_max_lifetime':
                $this->checkDecimalNumber( $value, 0, 300, System_Const::INT_MAX );
                break;

            case 'gc_divisor':
                $this->checkDecimalNumber( $value, 0, 0, 10000 );
                break;

            case 'hide_id_column':
            case 'hide_empty_values':
            case 'self_register':
            case 'register_auto_approve':
            case 'anonymous_access':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 0, 1 );
                break;

            case 'history_order':
                if ( $value != 'asc' && $value != 'desc' )
                    throw new System_Api_Error( System_Api_Error::InvalidSetting );
                break;

            case 'history_filter':
                $this->checkDecimalNumber( $value, 0, 1, 4 );
                break;

            case 'default_format':
                $this->checkDecimalNumber( $value, 0, 0, 1 );
                break;

            case 'email_engine':
                if ( $value != '' && $value != 'standard' && $value != 'smtp' )
                    throw new System_Api_Error( System_Api_Error::InvalidSetting );
                break;

            case 'inbox_engine':
                if ( $value != '' && $value != 'imap' && $value != 'pop3' )
                    throw new System_Api_Error( System_Api_Error::InvalidSetting );
                break;

            case 'email_from':
            case 'register_notify_email':
            case 'inbox_email':
                if ( $value != '' )
                    $this->checkEmailAddress( $value );
                break;

            case 'smtp_server':
            case 'smtp_user':
            case 'smtp_password':
            case 'inbox_server':
            case 'inbox_user':
            case 'inbox_password':
            case 'inbox_mailbox':
                break;

            case 'smtp_port':
            case 'inbox_port':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 1, 65535 );
                break;

            case 'smtp_encryption':
            case 'inbox_encryption':
                if ( $value != '' && $value != 'ssl' && $value != 'tls' )
                    throw new System_Api_Error( System_Api_Error::InvalidSetting );
                break;

            case 'base_url':
                if ( $value != '' )
                    $this->checkBaseUrl( $value );
                break;

            case 'inbox_no_validate':
            case 'inbox_leave_messages':
            case 'inbox_allow_external':
            case 'inbox_map_folder':
            case 'inbox_respond':
            case 'inbox_subscribe':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 0, 1 );
                break;

            case 'inbox_robot':
            case 'inbox_default_folder':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 1 );
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidSetting );
        }
    }

    /**
    * Check if the user preference is valid.
    * @param $key Name of the preference to validate.
    * @param $value Value of the preference to validate.
    */
    public function checkPreference( $key, $value )
    {
        $this->checkString( $value, null, self::AllowEmpty );

        switch ( $key ) {
            case 'language':
            case 'number_format':
            case 'date_format':
            case 'time_format':
            case 'first_day_of_week':
            case 'time_zone':
                if ( $value != '' )
                    $this->checkLocale( $key, $value );
                break;

            case 'project_page_size':
            case 'folder_page_size':
            case 'history_page_size':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 1, 100 );
                break;

            case 'history_order':
                if ( $value != '' && $value != 'asc' && $value != 'desc' )
                    throw new System_Api_Error( System_Api_Error::InvalidSetting );
                break;

            case 'history_filter':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 1, 4 );
                break;

            case 'default_format':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 0, 1 );
                break;

            case 'email':
                if ( $value != '' )
                    $this->checkEmailAddress( $value );
                break;

            case 'notify_details':
            case 'notify_no_read':
                if ( $value != '' )
                    $this->checkDecimalNumber( $value, 0, 0, 1 );
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidPreference );
        }
    }

    private function checkLocale( $key, $value )
    {
        switch ( $key ) {
            case 'language':
                $languages = $this->locale->getAvailableLanguages();
                if ( !isset( $languages[ $value ] ) )
                    throw new System_Api_Error( System_Api_Error::NoMatchingItem );
                break;

            case 'number_format':
            case 'date_format':
            case 'time_format':
                $formats = $this->locale->getAvailableFormats( $key );
                if ( !isset( $formats[ $value ] ) )
                    throw new System_Api_Error( System_Api_Error::NoMatchingItem );
                break;

            case 'first_day_of_week':
                $this->checkDecimalNumber( $value, 0, 0, 6 );
                break;

            case 'time_zone':
                $this->checkTimeZone( $value );
                break;
        }
    }

    private function checkBaseUrl( $value )
    {
        if ( !preg_match( '/^https?:\/\/[\w+&@#\/\\\\%=~|$?!:,.()-]+\/$/ui', $value ) )
            throw new System_Api_Error( System_Api_Error::InvalidSetting );
    }

    /**
    * Check if the view definition is valid.
    * @param $attributes Array of attributes of the issue type related
    * to the view.
    * @param $definition The definition to validate.
    */
    public function checkViewDefinition( $attributes, $definition )
    {
        if ( preg_match( '/[\x00-\x1f\x7f]/', $definition ) )
            throw new System_Api_Error( System_Api_Error::InvalidString );

        $info = System_Api_DefinitionInfo::fromString( $definition );

        $this->checkViewDefinitionInfo( $attributes, $info, true );
    }

    private function checkViewDefinitionInfo( $attributes, $info, $allowFilters )
    {
        if ( $info->getType() != 'VIEW' )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        $requiredKeys = array( 'columns' => 's', 'sort-column' => 'i' );
        $optionalKeys = array( 'sort-desc' => 'i' );

        if ( $allowFilters )
            $optionalKeys[ 'filters' ] = 'a';

        if ( !$info->checkMetadataKeys( $requiredKeys, $optionalKeys ) )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        $allColumns = array(
            System_Api_Column::ID, System_Api_Column::Name,
            System_Api_Column::CreatedDate, System_Api_Column::CreatedBy,
            System_Api_Column::ModifiedDate, System_Api_Column::ModifiedBy
        );

        foreach ( $attributes as $attribute )
            $allColumns[] = System_Api_Column::UserDefined + $attribute[ 'attr_id' ];

        $columns = $this->convertToIntArray( $info->getMetadata( 'columns' ) );
        foreach ( $columns as $column ) {
            if ( array_search( $column, $allColumns ) === false )
                throw new System_Api_Error( System_Api_Error::UnknownColumn );
        }
        if ( count( array_unique( $columns ) ) != count( $columns ) )
            throw new System_Api_Error( System_Api_Error::DuplicateItems );
        if ( count( $columns ) < 2 || $columns[ 0 ] != System_Api_Column::ID || $columns[ 1 ] != System_Api_Column::Name )
            throw new System_Api_Error( System_Api_Error::MissingColumn );

        $sortColumn = $info->getMetadata( 'sort-column' );
        if ( array_search( $sortColumn, $columns ) === false )
            throw new System_Api_Error( System_Api_Error::UnknownColumn );

        foreach ( $info->getAllMetadata() as $key => $value ) {
            switch ( $key ) {
                case 'sort-desc':
                    $this->checkBooleanValue( $value );
                    break;

                case 'filters':
                    foreach ( $value as $filter )
                        $this->checkFilterDefinition( $attributes, $filter );
                    break;
            }
        }
    }

    /**
    * Check if the view setting is valid.
    * @param $type Issue type related to the setting.
    * @param $attributes Array of attributes of the issue type related
    * to the setting.
    * @param $key Name of the setting to validate.
    * @param $value Value of the setting to validate.
    */
    public function checkViewSetting( $type, $attributes, $key, $value )
    {
        $this->checkString( $value, null, self::AllowEmpty );

        switch ( $key ) {
            case 'attribute_order':
                $attributeIds = $this->convertToIntArray( $value );
                if ( count( array_unique( $attributeIds ) ) != count( $attributeIds ) )
                    throw new System_Api_Error( System_Api_Error::DuplicateItems );
                $allAttributeIds = array();
                foreach ( $attributes as $attribute )
                    $allAttributeIds[] = $attribute[ 'attr_id' ];
                $count = count( array_intersect( $attributeIds, $allAttributeIds ) );
                if ( $count < count( $attributeIds ) )
                    throw new System_Api_Error( System_Api_Error::UnknownAttribute );
                if ( $count < count( $allAttributeIds ) )
                    throw new System_Api_Error( System_Api_Error::MissingAttribute );
                break;

            case 'default_view':
                $info = System_Api_DefinitionInfo::fromString( $value );
                $this->checkViewDefinitionInfo( $attributes, $info, false );
                break;

            case 'initial_view':
                if ( $value != '' ) {
                    $viewId = (int)$value;
                    if ( (string)$viewId !== $value )
                        throw new System_Api_Error( System_Api_Error::InvalidFormat );
                    $viewManager = new System_Api_ViewManager();
                    if ( !$viewManager->isPublicViewForIssueType( $type, $viewId ) )
                        throw new System_Api_Error( System_Api_Error::UnknownView );
                }
                break;

            default:
                throw new System_Api_Error( System_Api_Error::InvalidSetting );
        }
    }

    /**
    * Check if the definition of the filter condition is valid.
    * @param $attributes Array of attributes of the issue type related
    * to the filter.
    * @param $definiton Definition of the filter condition.
    */
    public function checkFilterDefinition( $attributes, $definition )
    {
        $info = System_Api_DefinitionInfo::fromString( $definition );

        $requiredKeys = array( 'column' => 'i', 'value' => 's' );
        $optionalKeys = array();

        if ( !$info->checkMetadataKeys( $requiredKeys, $optionalKeys ) )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        $column = $info->getMetadata( 'column' );
        $attributeDefinition = null;

        switch ( $column ) {
            case System_Api_Column::ID:
                $attributeDefinition = 'NUMERIC';
                break;

            case System_Api_Column::Name:
                $attributeDefinition = 'TEXT';
                break;

            case System_Api_Column::CreatedDate:
            case System_Api_Column::ModifiedDate:
                $attributeDefinition = 'DATETIME';
                break;

            case System_Api_Column::CreatedBy:
            case System_Api_Column::ModifiedBy:
                $attributeDefinition = 'USER';
                break;

            default:
                if ( $column > System_Api_Column::UserDefined ) {
                    $attributeId = $column - System_Api_Column::UserDefined;
                    $attributeDefinition = null;
                    foreach ( $attributes as $attribute ) {
                        if ( $attribute[ 'attr_id' ] == $attributeId ) {
                            $attributeDefinition = $attribute[ 'attr_def' ];
                            break;
                        }
                    }
                }
                break;
        }

        if ( $attributeDefinition == null )
            throw new System_Api_Error( System_Api_Error::UnknownColumn );

        $attributeInfo = System_Api_DefinitionInfo::fromString( $attributeDefinition );

        $valueInfo = new System_Api_DefinitionInfo();
        $filters = array( 'EQ', 'NEQ' );

        switch ( $attributeInfo->getType() ) {
            case 'TEXT':
            case 'ENUM':
            case 'USER':
                $valueInfo->setType( 'ENUM' );
                $valueInfo->setMetadata( 'editable', 1 );
                $filters = array_merge( $filters, array( 'CON', 'BEG', 'END', 'IN' ) );
                break;

            case 'NUMERIC':
                $valueInfo->setType( 'NUMERIC' );
                $valueInfo->setMetadata( 'decimal', $attributeInfo->getMetadata( 'decimal' ) );
                $filters = array_merge( $filters, array( 'GT', 'LT', 'GTE', 'LTE' ) );
                break;

            case 'DATETIME':
                $valueInfo->setType( 'DATETIME' );
                $filters = array_merge( $filters, array( 'GT', 'LT', 'GTE', 'LTE' ) );
                break;
        }

        $type = $info->getType();
        if ( array_search( $type, $filters ) === false )
            throw new System_Api_Error( System_Api_Error::InvalidDefinition );

        if ( $type != 'EQ' && $type != 'NEQ' )
            $valueInfo->setMetadata( 'required', 1 );

        if ( $type == 'IN' )
            $valueInfo->setMetadata( 'multi-select', 1 );

        $value = $info->getMetadata( 'value' );
        $this->checkString( $value, System_Const::ValueMaxLength, self::AllowEmpty );

        $this->checkFilterValue( $valueInfo, $value );
    }

    private function checkFilterValue( $info, $value )
    {
        $type = $info->getType();

        if ( ( $type == 'TEXT' || $type == 'ENUM' || $type == 'USER' ) && mb_substr( $value, 0, 4 ) == '[Me]' ) {
            if ( mb_substr( $value, 4 ) !== '' )
                throw new System_Api_Error( System_Api_Error::InvalidFormat );
            return;
        }

        if ( $type == 'DATETIME' && mb_substr( $value, 0, 7 ) == '[Today]' ) {
            $offset = mb_substr( $value, 7 );
            if ( $offset !== '' ) {
                $days = (int)$offset;
                if ( $days == 0 || sprintf( '%+d', $days ) !== $offset )
                    throw new System_Api_Error( System_Api_Error::InvalidFormat );
            }
            return;
        }

        $this->checkAttributeValueInfo( $info, $value );
    }

    /**
    * Convert a string to an array of integers.
    * @param $value String containing comma separated numbers.
    * @return An array of integers.
    */
    public function convertToIntArray( $value )
    {
        $result = array();

        if ( $value != '' ) {
            $items = explode( ',', $value );

            foreach ( $items as $item ) {
                $integer = (int)$item;
                if ( (string)$integer !== $item )
                    throw new System_Api_Error( System_Api_Error::InvalidFormat );
                $result[] = $integer;
            }
        }

        return $result;
    }

    private function checkIntArray( $value, $min, $max )
    {
        $items = $this->convertToIntArray( $value );

        foreach ( $items as $item )
            $this->checkIntegerValue( $item, $min, $max );

        if ( count( array_unique( $items ) ) != count( $items ) )
            throw new System_Api_Error( System_Api_Error::DuplicateItems );
    }

    private function checkList( $value )
    {
        $itemPattern = '[^, ]+(?: [^, ]+)*';

        if ( !preg_match( "/^$itemPattern(?:, $itemPattern)*$/", $value ) )
            throw new System_Api_Error( System_Api_Error::InvalidFormat );
    }

    private function checkItem( $item )
    {
        if ( strpos( $item, ',' ) !== false )
            throw new System_Api_Error( System_Api_Error::CommaNotAllowed );
    }
}
