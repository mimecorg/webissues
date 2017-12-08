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
* Exception thrown by all API methods.
*
* The exception message must be a valid error code as defined by the WebIssues
* protocol. The constants defined in this class should be used as messages.
*/
class System_Api_Error extends System_Core_Exception
{
    const LoginRequired = '300 Login Required';
    const AccessDenied = '301 Access Denied';
    const IncorrectLogin = '302 Incorrect Login';
    const UnknownProject = '303 Unknown Project';
    const UnknownFolder = '304 Unknown Folder';
    const UnknownIssue = '305 Unknown Issue';
    const UnknownFile = '306 Unknown File';
    const UnknownUser = '307 Unknown User';
    const UnknownType = '308 Unknown Type';
    const UnknownAttribute = '309 Unknown Attribute';
    const UnknownEvent = '310 Unknown Event';
    const ProjectAlreadyExists = '311 Project Already Exists';
    const FolderAlreadyExists = '312 Folder Already Exists';
    const UserAlreadyExists = '313 User Already Exists';
    const TypeAlreadyExists = '314 Type Already Exists';
    const AttributeAlreadyExists = '315 Attribute Already Exists';
    const CannotDeleteProject = '316 Cannot Delete Project';
    const CannotDeleteFolder = '317 Cannot Delete Folder';
    const CannotDeleteType = '318 Cannot Delete Type';
    const InvalidString = '319 Invalid String';
    const InvalidAccessLevel = '320 Invalid Access Level';
    const InvalidValue = '321 Invalid Value';
    const InvalidDefinition = '322 Invalid Definition';
    const InvalidPreference = '323 Invalid Preference';
    const InvalidSetting = '324 Invalid Setting';
    const EmptyValue = '325 Empty Value';
    const StringTooShort = '326 String Too Short';
    const StringTooLong = '327 String Too Long';
    const NumberTooLittle = '328 Number Too Little';
    const NumberTooGreat = '329 Number Too Great';
    const TooManyDecimals = '330 Too Many Decimals';
    const TooManyDigits = '331 Too Many Digits';
    const InvalidFormat = '332 Invalid Format';
    const InvalidDate = '333 Invalid Date';
    const InvalidTime = '334 Invalid Time';
    const InvalidEmail = '335 Invalid Email';
    const NoMatchingItem = '336 No Matching Item';
    const DuplicateItems = '337 Duplicate Items';
    const InvalidLimits = '338 Invalid Limits';
    const IncompatibleType = '339 Incompatible Type';
    const UnknownView = '340 Unknown View';
    const UnknownColumn = '341 Unknown Column';
    const ViewAlreadyExists = '342 View Already Exists';
    const MissingColumn = '343 Missing Column';
    const MissingAttribute = '344 Missing Attribute';
    const NoItems = '345 No Items';
    const PasswordNotMatching = '346 Password Not Matching';
    const UnknownAlert = '347 Unknown Alert';
    const AlertAlreadyExists = '348 Alert Already Exists';
    const InvalidAlertEmail = '349 Invalid Alert Email';
    const UnknownComment = '350 Unknown Comment';
    const CannotDeleteAttribute = '351 Cannot Delete Attribute';
    const MustChangePassword = '352 Must Change Password';
    const CannotReusePassword = '353 Cannot Reuse Password';
    const ItemNotFound = '354 Item Not Found';
    const CommaNotAllowed = '355 Comma Not Allowed';
    const TransactionDeadlock = '356 Transaction Deadlock';
    const ConstraintConflict = '357 Constraint Conflict';
    const EmailAlreadyExists = '358 Email Already Exists';
    const InvalidActivationKey = '359 Invalid Activation Key';
    const UnknownRequest = '360 Unknown Request';
    const UnknownDescription = '361 Unknown Description';
    const DescriptionAlreadyExists = '362 Description Already Exists';
    const InvalidTextFormat = '363 Invalid Text Format';
    const UnknownSubscription = '364 Unknown Subscription';
    const SubscriptionAlreadyExists = '365 SubscriptionAlreadyExists';

    public function __construct( $message, $wrappedException = null )
    {
        parent::__construct( $message, $wrappedException );
    }
}
