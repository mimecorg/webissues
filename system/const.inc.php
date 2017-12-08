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
* Definitions of various global constants.
*/
class System_Const
{
    /**
    * @name Various
    */
    /*@{*/
    /** Maximum value of a 32-bit signed integer. */
    const INT_MAX = 2147483647;
    /*@}*/

    /**
    * @name Access Levels
    */
    /*@{*/
    /** User has no access to server or project. */
    const NoAccess = 0;
    /** User has regular access to server or project. */
    const NormalAccess = 1;
    /** User is an administrator of the server or project. */
    const AdministratorAccess = 2;
    /*@}*/

    /**
    * @name Alert Emails
    */
    /*@{*/
    /** No emails are sent for the alert. */
    const NoEmail = 0;
    /** Immediate notifications are sent for the alert. */
    const ImmediateNotificationEmail = 1;
    /** Summary notifications are sent for the alert. */
    const SummaryNotificationEmail = 2;
    /** Summary reports are sent for the alert. */
    const SummaryReportEmail = 3;
    /*@}*/

    /**
    * @name Issue Changes
    */
    /*@{*/
    /** The issue was created with the given name. */
    const IssueCreated = 0;
    /** The issue was renamed. */
    const IssueRenamed = 1;
    /** The value of an attribute was changed. */
    const ValueChanged = 2;
    /** A comment was added. */
    const CommentAdded = 3;
    /** A file was added. */
    const FileAdded = 4;
    /** The issue was moved to another folder. */
    const IssueMoved = 5;
    /*@}*/

    /**
    * @name Text Formats
    */
    /*@{*/
    /** Plain text format. */
    const PlainText = 0;
    /** Text with markup. */
    const TextWithMarkup = 1;
    /*@}*/

    /**
    * Limits
    */
    /*@{*/
    /** Maximum length of name of project/folder etc. */
    const NameMaxLength = 40;
    /** Maximum length of attribute value or issue name. */
    const ValueMaxLength = 255;
    /** Maximum length of file name. */
    const FileNameMaxLength = 80;
    /** Maximum length of file description. */
    const DescriptionMaxLength = 255;
    /** Maximum length of key of preference/setting. */
    const KeyMaxLength = 40;
    /** Maximum length of name of user login. */
    const LoginMaxLength = 40;
    /** Maximum length of password. */
    const PasswordMaxLength = 40;
    /*@}*/
}
