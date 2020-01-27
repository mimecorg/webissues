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

export const GuideURL = 'https://doc.mimec.org/webissues-guide/';

export const Access = {
  NoAccess: 0,
  NormalAccess: 1,
  AdministratorAccess: 2
};

export const AlertType = {
  Alert: 1,
  ChangeReport: 2,
  IssueReport: 3
};

export const AlertFrequency = {
  Daily: 0,
  Weekly: 1
};

export const EventType = {
  Errors: 'errors',
  Access: 'access',
  Audit: 'audit',
  Cron: 'cron'
};

export const EventSeverity = {
  Information: 0,
  Warning: 1,
  Error: 2
};

export const Change = {
  IssueCreated: 0,
  IssueRenamed: 1,
  ValueChanged: 2,
  CommentAdded: 3,
  FileAdded: 4,
  IssueMoved: 5
};

export const Column = {
  Name: 0,
  ID: 1,
  CreatedDate: 2,
  CreatedBy: 3,
  ModifiedDate: 4,
  ModifiedBy: 5,
  Location: 6,
  UserDefined: 1000
};

export const History = {
  AllHistory: 1,
  Comments: 2,
  Files: 3,
  CommentsAndFiles: 4
};

export const TextFormat = {
  PlainText: 0,
  TextWithMarkup: 1
};

export const MaxLength = {
  Name: 40,
  Value: 255,
  FileName: 80,
  FileDescription: 255
};

export const ErrorCode = {
  LoginRequired: 300,
  AccessDenied: 301,
  IncorrectLogin: 302,
  UnknownProject: 303,
  UnknownFolder: 304,
  UnknownIssue: 305,
  UnknownFile: 306,
  UnknownUser: 307,
  UnknownType: 308,
  UnknownAttribute: 309,
  UnknownEvent: 310,
  ProjectAlreadyExists: 311,
  FolderAlreadyExists: 312,
  UserAlreadyExists: 313,
  TypeAlreadyExists: 314,
  AttributeAlreadyExists: 315,
  CannotDeleteProject: 316,
  CannotDeleteFolder: 317,
  CannotDeleteType: 318,
  InvalidString: 319,
  InvalidAccessLevel: 320,
  InvalidValue: 321,
  InvalidDefinition: 322,
  InvalidPreference: 323,
  InvalidSetting: 324,
  EmptyValue: 325,
  StringTooShort: 326,
  StringTooLong: 327,
  NumberTooLittle: 328,
  NumberTooGreat: 329,
  TooManyDecimals: 330,
  TooManyDigits: 331,
  InvalidFormat: 332,
  InvalidDate: 333,
  InvalidTime: 334,
  InvalidEmail: 335,
  NoMatchingItem: 336,
  DuplicateItems: 337,
  InvalidLimits: 338,
  IncompatibleType: 339,
  UnknownView: 340,
  UnknownColumn: 341,
  ViewAlreadyExists: 342,
  MissingColumn: 343,
  MissingAttribute: 344,
  NoItems: 345,
  PasswordNotMatching: 346,
  UnknownAlert: 347,
  AlertAlreadyExists: 348,
  InvalidAlertEmail: 349,
  UnknownComment: 350,
  CannotDeleteAttribute: 351,
  MustChangePassword: 352,
  CannotReusePassword: 353,
  ItemNotFound: 354,
  CommaNotAllowed: 355,
  TransactionDeadlock: 356,
  ConstraintConflict: 357,
  EmailAlreadyExists: 358,
  InvalidActivationKey: 359,
  UnknownRequest: 360,
  UnknownDescription: 361,
  DescriptionAlreadyExists: 362,
  InvalidTextFormat: 363,
  UnknownSubscription: 364,
  SubscriptionAlreadyExists: 365,
  LoginAlreadyExists: 366,
  InvalidResetKey: 367,
  UnknownInbox: 368,
  InvalidAlertType: 369,
  InvalidAlertFrequency: 370
};

export const Reason = {
  APIError: 'APIError',
  ParseError: 'ParseError',
  PageNotFound: 'PageNotFound',
  ServerError: 'ServerError',
  BadRequest: 'BadRequest',
  InvalidResponse: 'InvalidResponse',
  NetworkError: 'NetworkError',
  UnsupportedVersion: 'UnsupportedVersion'
};

export const KeyCode = {
  Tab: 9,
  Enter: 13,
  Esc: 27,
  Up: 38,
  Down: 40,
  F4: 115
};
