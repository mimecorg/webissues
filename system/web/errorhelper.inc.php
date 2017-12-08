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
* Class providing user friendly error messages.
*/
class System_Web_ErrorHelper extends System_Web_Base
{
    private $form = null;

    /**
    * Constructor.
    * @param $form The System_Web_Form associated with error messages.
    */
    public function __construct( $form = null )
    {
        parent::__construct();

        $this->form = $form;
    }

    /**
    * Display user friendly error message.
    * @param $key The name of the error.
    * @param $error A System_Api_Error exception or an error code.
    */
    public function handleError( $key, $error )
    {
        if ( is_a( $error, 'System_Api_Error' ) )
            $error = $error->getMessage();

        $message = $this->getErrorMessage( $error );

        if ( $message == '' )
            $this->form->setError( $key, $this->tr( 'Some of the values you entered are incorrect.' ) );
        else
            $this->form->setError( $key, $this->tr( 'Incorrect value: %1.', null, $message ) );
    }

    /**
    * Return the user friendly error message.
    * @param $error An error code.
    */
    public function getErrorMessage( $error )
    {
        switch ( $error ) {
            case System_Api_Error::LoginRequired:
                return $this->tr( 'Your session has expired; please reconnect' );
            case System_Api_Error::AccessDenied:
                return $this->tr( 'You have no permission to perform this operation' );
            case System_Api_Error::IncorrectLogin:
                return $this->tr( 'Invalid login or password' );
            case System_Api_Error::UnknownProject:
                return $this->tr( 'Project does not exist' );
            case System_Api_Error::UnknownFolder:
                return $this->tr( 'Folder does not exist' );
            case System_Api_Error::UnknownIssue:
                return $this->tr( 'Issue does not exist' );
            case System_Api_Error::UnknownFile:
                return $this->tr( 'Attachment does not exist' );
            case System_Api_Error::UnknownUser:
                return $this->tr( 'User does not exist' );
            case System_Api_Error::UnknownType:
                return $this->tr( 'Type does not exist' );
            case System_Api_Error::UnknownAttribute:
                return $this->tr( 'Attribute does not exist' );
            case System_Api_Error::UnknownEvent:
                return $this->tr( 'Event does not exist' );
            case System_Api_Error::ProjectAlreadyExists:
                return $this->tr( 'A project with this name already exists' );
            case System_Api_Error::FolderAlreadyExists:
                return $this->tr( 'A folder with this name already exists' );
            case System_Api_Error::UserAlreadyExists:
                return $this->tr( 'A user with this login or name already exists' );
            case System_Api_Error::TypeAlreadyExists:
                return $this->tr( 'A type with this name already exists' );
            case System_Api_Error::AttributeAlreadyExists:
                return $this->tr( 'An attribute with this name already exists' );
            case System_Api_Error::CannotDeleteProject:
                return $this->tr( 'Project cannot be deleted' );
            case System_Api_Error::CannotDeleteFolder:
                return $this->tr( 'Folder cannot be deleted' );
            case System_Api_Error::CannotDeleteType:
                return $this->tr( 'Type cannot be deleted' );
            case System_Api_Error::InvalidString:
                return $this->tr( 'Text contains invalid characters' );
            case System_Api_Error::InvalidAccessLevel:
                return $this->tr( 'Access level is invalid' );
            case System_Api_Error::InvalidValue:
                return $this->tr( 'Value is invalid' );
            case System_Api_Error::InvalidDefinition:
                return $this->tr( 'Definition is invalid' );
            case System_Api_Error::InvalidPreference:
                return $this->tr( 'Invalid preference value' );
            case System_Api_Error::InvalidSetting:
                return $this->tr( 'Invalid setting value' );
            case System_Api_Error::EmptyValue:
                return $this->tr( 'Required value is missing' );
            case System_Api_Error::StringTooShort:
                return $this->tr( 'Text is too short' );
            case System_Api_Error::StringTooLong:
                return $this->tr( 'Text is too long' );
            case System_Api_Error::NumberTooLittle:
                return $this->tr( 'Number is too small' );
            case System_Api_Error::NumberTooGreat:
                return $this->tr( 'Number is too big' );
            case System_Api_Error::TooManyDecimals:
                return $this->tr( 'Number has too many decimal digits' );
            case System_Api_Error::TooManyDigits:
                return $this->tr( 'Number has too many digits' );
            case System_Api_Error::InvalidFormat:
                return $this->tr( 'Value has incorrect format' );
            case System_Api_Error::InvalidDate:
                return $this->tr( 'Date is not correct' );
            case System_Api_Error::InvalidTime:
                return $this->tr( 'Time is not correct' );
            case System_Api_Error::InvalidEmail:
                return $this->tr( 'Email address is invalid' );
            case System_Api_Error::NoMatchingItem:
                return $this->tr( 'No matching item is selected' );
            case System_Api_Error::DuplicateItems:
                return $this->tr( 'Duplicate items are entered' );
            case System_Api_Error::InvalidLimits:
                return $this->tr( 'Minimum value is greater than maximum value' );
            case System_Api_Error::IncompatibleType:
                return $this->tr( 'Incompatible attribute type' );
            case System_Api_Error::UnknownView:
                return $this->tr( 'View does not exist' );
            case System_Api_Error::UnknownColumn:
                return $this->tr( 'Column does not exist' );
            case System_Api_Error::ViewAlreadyExists:
                return $this->tr( 'A view with this name already exists' );
            case System_Api_Error::MissingColumn:
                return $this->tr( 'A required column is missing' );
            case System_Api_Error::MissingAttribute:
                return $this->tr( 'An attribute is missing' );
            case System_Api_Error::NoItems:
                return $this->tr( 'No items are specified' );
            case System_Api_Error::PasswordNotMatching:
                return $this->tr( 'Passwords do not match; please retype them' );
            case System_Api_Error::UnknownAlert:
                return $this->tr( 'Alert does not exist' );
            case System_Api_Error::AlertAlreadyExists:
                return $this->tr( 'Alert already exists' );
            case System_Api_Error::InvalidAlertEmail:
                return $this->tr( 'Invalid alert email setting' );
            case System_Api_Error::UnknownComment:
                return $this->tr( 'Comment does not exist' );
            case System_Api_Error::CannotDeleteAttribute:
                return $this->tr( 'Attribute cannot be deleted' );
            case System_Api_Error::MustChangePassword:
                return $this->tr( 'You must change your password' );
            case System_Api_Error::CannotReusePassword:
                return $this->tr( 'Cannot reuse password; choose different password' );
            case System_Api_Error::ItemNotFound:
                return $this->tr( 'The specified item was not found' );
            case System_Api_Error::CommaNotAllowed:
                return $this->tr( 'Value cannot contain a comma' );
            case System_Api_Error::TransactionDeadlock:
                return $this->tr( 'Concurrency error; please try again' );
            case System_Api_Error::ConstraintConflict:
                return $this->tr( 'One of the dependent objects no longer exists' );
            case System_Api_Error::EmailAlreadyExists:
                return $this->tr( 'Another user already uses this email address' );
            case System_Api_Error::InvalidActivationKey:
                return $this->tr( 'The activation key is invalid' );
            case System_Api_Error::UnknownRequest:
                return $this->tr( 'Request does not exist' );
            case System_Api_Error::UnknownDescription:
                return $this->tr( 'Description does not exist' );
            case System_Api_Error::DescriptionAlreadyExists:
                return $this->tr( 'Description already exists' );
            case InvalidTextFormat::InvalidTextFormat:
                return $this->tr( 'Text format is invalid' );
            case InvalidTextFormat::UnknownSubscription:
                return $this->tr( 'Subscription does not exist' );
            case System_Api_Error::SubscriptionAlreadyExists:
                return $this->tr( 'Subscription already exists' );
            default:
                return '';
        }
    }
}
