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

import { ErrorCode } from '@/constants'

export default {
  common: {
    ok: 'OK',
    cancel: 'Cancel',
    close: 'Close',
    previous: 'Previous',
    next: 'Next'
  },
  header: {
    add: 'Add',
    add_issue: 'Add Issue',
    go_to: 'Go To',
    go_to_item: 'Go To Item',
    administration: 'Administration',
    administration_menu: 'Administration Menu',
    projects: 'Projects',
    user_accounts: 'User Accounts',
    registration_requests: 'Registration Requests',
    issue_types: 'Issue Types',
    public_alerts: 'Public Alerts',
    archived_projects: 'Archived Projects',
    general_settings: 'General Settings',
    access_settings: 'Access Settings',
    email_settings: 'Email Settings',
    inbox_settings: 'Inbox Settings',
    advanced_settings: 'Advanced Settings',
    status_report: 'Status Report',
    event_log: 'Event Log',
    user_title: 'User: {0}',
    anonymous_user: 'Anonymous User',
    personal_views: 'Personal Views',
    personal_alerts: 'Personal Alerts',
    user_preferences: 'User Preferences',
    change_password: 'Change Password',
    log_out: 'Log Out',
    log_in: 'Log In',
    register: 'Register',
    about_webissues: 'About WebIssues',
    webissues_manual: 'WebIssues Manual',
    toggle_navigation: 'Toggle Navigation'
  },
  main: {
    select_type: 'Select Type',
    all_issues: 'All Issues',
    personal_views: 'Personal Views',
    public_views: 'Public Views',
    all_projects: 'All Projects',
    all_folders: 'All Folders',
    type_title: 'Type: {0}',
    view_title: 'View: {0}',
    project_title: 'Project: {0}',
    folder_title: 'Folder: {0}',
    id: 'ID',
    name: 'Name',
    created_date: 'Created Date',
    created_by: 'Created By',
    modified_date: 'Modified Date',
    modified_by: 'Modified By',
    search_by: 'Search By: {0}',
    search: 'Search',
    reload: 'Reload',
    more: 'More',
    mark_all_as_read: 'Mark All As Read',
    mark_all_as_unread: 'Mark All As Unread',
    project_description: 'Project Description',
    export_to_csv: 'Export To CSV',
    no_issues: 'No issues',
    issues_count: '{0} issues',
    issues_count_of: '{0}-{1} of {2} issues'
  },
  edit_issue: {
    title: 'Edit Attributes',
    prompt: 'Edit attributes of issue {0}.',
    name: 'Name:'
  },
  error: {
    page_not_found: 'Page Not Found',
    network_error: 'Network Error',
    session_expired: 'Session Expired',
    login_required: 'Login Required',
    access_denied: 'Access Denied',
    unexpected_error: 'Unexpected Error'
  },
  error_message: {
    page_not_found: 'The requested page was not found.',
    network_error: 'The server could not be reached because of a network error.',
    session_expired: 'Your session has expired. Please log in again.',
    login_required: 'You need to log in to perform the requested operation.',
    access_denied: 'You have insufficient permissions to perform the requested operation.',
    invalid_response: 'Server returned an invalid response.',
    server_not_configured: 'Server is not correctly configured.',
    server_error: 'An internal server error occurred.',
    upload_error: 'File could not be uploaded.',
    bad_request: 'Server could not understand the request.',
    unknown_error: 'An unknown error occurred.',
  },
  error_code: {
    [ErrorCode.LoginRequired]: 'Login required.',
    [ErrorCode.AccessDenied]: 'Access denied.',
    [ErrorCode.IncorrectLogin]: 'Invalid login or password.',
    [ErrorCode.UnknownProject]: 'Project does not exist.',
    [ErrorCode.UnknownFolder]: 'Folder does not exist.',
    [ErrorCode.UnknownIssue]: 'Issue does not exist.',
    [ErrorCode.UnknownFile]: 'Attachment does not exist.',
    [ErrorCode.UnknownUser]: 'User does not exist.',
    [ErrorCode.UnknownType]: 'Type does not exist.',
    [ErrorCode.UnknownAttribute]: 'Attribute does not exist.',
    [ErrorCode.UnknownEvent]: 'Event does not exist.',
    [ErrorCode.ProjectAlreadyExists]: 'A project with this name already exists.',
    [ErrorCode.FolderAlreadyExists]: 'A folder with this name already exists.',
    [ErrorCode.UserAlreadyExists]: 'A user with this login or name already exists.',
    [ErrorCode.TypeAlreadyExists]: 'A type with this name already exists.',
    [ErrorCode.AttributeAlreadyExists]: 'An attribute with this name already exists.',
    [ErrorCode.CannotDeleteProject]: 'Project cannot be deleted.',
    [ErrorCode.CannotDeleteFolder]: 'Folder cannot be deleted.',
    [ErrorCode.CannotDeleteType]: 'Type cannot be deleted.',
    [ErrorCode.InvalidString]: 'Text contains invalid characters.',
    [ErrorCode.InvalidAccessLevel]: 'Access level is invalid.',
    [ErrorCode.InvalidValue]: 'Value is invalid.',
    [ErrorCode.InvalidDefinition]: 'Definition is invalid.',
    [ErrorCode.InvalidPreference]: 'Invalid preference value.',
    [ErrorCode.InvalidSetting]: 'Invalid setting value.',
    [ErrorCode.EmptyValue]: 'Required value is missing.',
    [ErrorCode.StringTooShort]: 'Text is too short.',
    [ErrorCode.StringTooLong]: 'Text is too long.',
    [ErrorCode.NumberTooLittle]: 'Number is too small.',
    [ErrorCode.NumberTooGreat]: 'Number is too big.',
    [ErrorCode.TooManyDecimals]: 'Number has too many decimal digits.',
    [ErrorCode.TooManyDigits]: 'Number has too many digits.',
    [ErrorCode.InvalidFormat]: 'Value has incorrect format.',
    [ErrorCode.InvalidDate]: 'Date is not correct.',
    [ErrorCode.InvalidTime]: 'Time is not correct.',
    [ErrorCode.InvalidEmail]: 'Email address is invalid.',
    [ErrorCode.NoMatchingItem]: 'No matching item is selected.',
    [ErrorCode.DuplicateItems]: 'Duplicate items are entered.',
    [ErrorCode.InvalidLimits]: 'Minimum value is greater than maximum value.',
    [ErrorCode.IncompatibleType]: 'Incompatible attribute type.',
    [ErrorCode.UnknownView]: 'View does not exist.',
    [ErrorCode.UnknownColumn]: 'Column does not exist.',
    [ErrorCode.ViewAlreadyExists]: 'A view with this name already exists.',
    [ErrorCode.MissingColumn]: 'A required column is missing.',
    [ErrorCode.MissingAttribute]: 'An attribute is missing.',
    [ErrorCode.NoItems]: 'No items are specified.',
    [ErrorCode.PasswordNotMatching]: 'Passwords do not match; please retype them.',
    [ErrorCode.UnknownAlert]: 'Alert does not exist.',
    [ErrorCode.AlertAlreadyExists]: 'Alert already exists.',
    [ErrorCode.InvalidAlertEmail]: 'Invalid alert email setting.',
    [ErrorCode.UnknownComment]: 'Comment does not exist.',
    [ErrorCode.CannotDeleteAttribute]: 'Attribute cannot be deleted.',
    [ErrorCode.MustChangePassword]: 'You must change your password.',
    [ErrorCode.CannotReusePassword]: 'Cannot reuse password; choose different password.',
    [ErrorCode.ItemNotFound]: 'The specified item was not found.',
    [ErrorCode.CommaNotAllowed]: 'Value cannot contain a comma.',
    [ErrorCode.TransactionDeadlock]: 'Concurrency error; please try again.',
    [ErrorCode.ConstraintConflict]: 'One of the dependent objects no longer exists.',
    [ErrorCode.EmailAlreadyExists]: 'Another user already uses this email address.',
    [ErrorCode.UnknownDescription]: 'Description does not exist.',
    [ErrorCode.DescriptionAlreadyExists]: 'Description already exists.',
    [ErrorCode.InvalidTextFormat]: 'Text format is invalid.',
    [ErrorCode.UnknownSubscription]: 'Subscription does not exist.',
    [ErrorCode.SubscriptionAlreadyExists]: 'Subscription already exists.'
  }
}
