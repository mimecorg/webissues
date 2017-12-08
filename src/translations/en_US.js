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
    personal_views: 'Personal Views',
    personal_alerts: 'Personal Alerts',
    user_preferences: 'User Preferences',
    change_password: 'Change Password',
    log_out: 'Log Out',
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
  unexpected_error: {
    title: 'Unexpected Error'
  },
  error_alert: {
    format: '{0} {1}.',
    error: 'Error:'
  },
  form_group: {
    error_format: '{0} {1}.',
    incorrect_value: 'Incorrect value:'
  },
  edit_issue: {
    title: 'Edit Attributes',
    prompt: 'Edit attributes of issue {0}.',
    name: 'Name:'
  },
  error: {
    not_found: 'The requested page was not found',
    invalid_response: 'Server returned an invalid response',
    server_not_configured: 'The WebIssues Server is not correctly configured',
    server_error: 'An internal server error occurred',
    upload_error: 'File could not be uploaded',
    bad_request: 'Request could not be processed',
    network_error: 'A network error occurred',
    unknown_error: 'An unknown error occurred ({0} {1})'
  },
  error_code: {
    [ErrorCode.UnknownProject]: 'Project does not exist',
    [ErrorCode.UnknownFolder]: 'Folder does not exist',
    [ErrorCode.UnknownIssue]: 'Issue does not exist',
    [ErrorCode.UnknownType]: 'Type does not exist',
    [ErrorCode.EmptyValue]: 'Required value is missing',
    [ErrorCode.UnknownView]: 'View does not exist'
  }
}
