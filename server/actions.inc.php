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

class Server_Actions
{
    private $principal = null;
    private $validator = null;

    private $reply = array();

    public function __construct()
    {
        $this->principal = System_Api_Principal::getCurrent();
        $this->validator = new System_Api_Validator();
    }

    public function getReply()
    {
        return $this->reply;
    }

    public function hello()
    {
        $serverManager = new System_Api_ServerManager();
        $this->addRow( 'server', $serverManager->getServer() );
    }

    public function login( $login, $password )
    {
        $this->validator->checkString( $login, System_Const::LoginMaxLength );
        $this->validator->checkString( $password, System_Const::PasswordMaxLength );

        $sessionManager = new System_Api_SessionManager();
        $this->addRow( 'user_login', $sessionManager->login( $login, $password ) );
    }

    public function loginNew( $login, $password, $newPassword )
    {
        $this->validator->checkString( $login, System_Const::LoginMaxLength );
        $this->validator->checkString( $password, System_Const::PasswordMaxLength );
        $this->validator->checkString( $newPassword, System_Const::PasswordMaxLength );

        $sessionManager = new System_Api_SessionManager();
        $this->addRow( 'user_login', $sessionManager->login( $login, $password, $newPassword ) );
    }

    public function getSettings()
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $this->addTable( 'settings', $serverManager->getSettingsAsTable() );

        $locale = new System_Api_Locale();
        $this->addTable( 'languages', $locale->getLanguagesAsTable() );
        $this->addTable( 'time_zones', $locale->getTimeZonesAsTable() );
    }

    public function listUsers()
    {
        $this->principal->checkAuthenticated();

        $userManager = new System_Api_UserManager();
        $this->addTable( 'users', $userManager->getUsers() );
        $this->addTable( 'rights', $userManager->getRights() );
        $this->addTable( 'preferences', $userManager->getPreferences() );
    }

    public function addUser( $login, $name, $password, $isTemp )
    {
        $this->principal->checkAdministrator();

        $this->validator->checkString( $login, System_Const::LoginMaxLength );
        $this->validator->checkString( $name, System_Const::NameMaxLength );
        $this->validator->checkString( $password, System_Const::PasswordMaxLength );
        $this->validator->checkBooleanValue( $isTemp );

        $userManager = new System_Api_UserManager();
        $this->setId( $userManager->addUser( $login, $name, $password, $isTemp ) );
    }

    public function setPassword( $userId, $newPassword, $isTemp )
    {
        $this->principal->checkAdministrator();

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );
        $this->validator->checkString( $newPassword, System_Const::PasswordMaxLength );
        $this->validator->checkBooleanValue( $isTemp );

        $this->setOkIf( $userManager->setPassword( $user, $newPassword, $isTemp ) );
    }

    public function changePassword( $password, $newPassword )
    {
        $this->principal->checkAuthenticated();

        $userManager = new System_Api_UserManager();
        $this->validator->checkString( $password, System_Const::PasswordMaxLength );
        $this->validator->checkString( $newPassword, System_Const::PasswordMaxLength );

        $this->setOkIf( $userManager->changePassword( $password, $newPassword ) );
    }

    public function renameUser( $userId, $newName )
    {
        $this->principal->checkAdministrator();

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );
        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $userManager->renameUser( $user, $newName ) );
    }

    public function grantUser( $userId, $newAccess )
    {
        $this->principal->checkAdministrator();

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );
        $this->validator->checkAccessLevel( $newAccess );

        $this->setOkIf( $userManager->grantUser( $user, $newAccess ) );
    }

    public function grantMember( $userId, $projectId, $newAccess )
    {
        $this->principal->checkAuthenticated();

        $userManager = new System_Api_UserManager();
        $projectManager = new System_Api_ProjectManager();

        $user = $userManager->getUser( $userId );
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $this->validator->checkAccessLevel( $newAccess );

        $this->setOkIf( $userManager->grantMember( $user, $project, $newAccess ) );
    }

    public function listTypes()
    {
        $this->principal->checkAuthenticated();

        $typeManager = new System_Api_TypeManager();
        $this->addTable( 'issue_types', $typeManager->getIssueTypes() );
        $this->addTable( 'attr_types', $typeManager->getAttributeTypes() );

        $viewManager = new System_Api_ViewManager();
        $this->addTable( 'views', $viewManager->getViews() );
        $this->addTable( 'view_settings', $viewManager->getViewSettings() );
    }

    public function addType( $name )
    {
        $this->principal->checkAdministrator();

        $this->validator->checkString( $name, System_Const::NameMaxLength );

        $typeManager = new System_Api_TypeManager();
        $this->setId( $typeManager->addIssueType( $name ) );
    }

    public function renameType( $typeId, $newName )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $typeManager->renameIssueType( $type, $newName ) );
    }

    public function deleteType( $typeId, $force )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $this->validator->checkBooleanValue( $force );

        $this->setOkIf( $typeManager->deleteIssueType( $type, $force ? System_Api_TypeManager::ForceDelete : 0 ) );
    }

    public function addAttribute( $typeId, $name, $definition )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $this->validator->checkString( $name, System_Const::NameMaxLength );
        $this->validator->checkAttributeDefinition( $definition );

        $this->setId( $typeManager->addAttributeType( $type, $name, $definition ) );
    }

    public function renameAttribute( $attributeId, $newName )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $attribute = $typeManager->getAttributeType( $attributeId );
        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $typeManager->renameAttributeType( $attribute, $newName ) );
    }

    public function modifyAttribute( $attributeId, $newDefinition )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $attribute = $typeManager->getAttributeType( $attributeId );
        $this->validator->checkAttributeDefinition( $newDefinition );
        $this->validator->checkCompatibleType( $attribute, $newDefinition );

        $this->setOkIf( $typeManager->modifyAttributeType( $attribute, $newDefinition ) );
    }

    public function deleteAttribute( $attributeId, $force )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $attribute = $typeManager->getAttributeType( $attributeId );
        $this->validator->checkBooleanValue( $force );

        $this->setOkIf( $typeManager->deleteAttributeType( $attribute, $force ? System_Api_TypeManager::ForceDelete : 0 ) );
    }

    public function listProjects()
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $this->addTable( 'projects', $projectManager->getProjects() );
        $this->addTable( 'folders', $projectManager->getFolders() );

        $alertManager = new System_Api_AlertManager();
        $this->addTable( 'alerts', $alertManager->getAlerts() );
    }

    public function getSummary( $projectId, $sinceStamp )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId );

        if ( $project[ 'stamp_id' ] > $sinceStamp ) {
            $this->addRow( 'projects', $project );

            if ( $projectManager->isDescriptionModified( $project, $sinceStamp ) )
                $this->addRow( 'project_descr', $projectManager->getProjectDescription( $project ) );
            else if ( $projectManager->isDescriptionDeleted( $project, $sinceStamp ) )
                $this->addRow( 'project_descr_stub', $project );
        }
    }

    public function addProject( $name )
    {
        $this->principal->checkAdministrator();

        $this->validator->checkString( $name, System_Const::NameMaxLength );

        $projectManager = new System_Api_ProjectManager();
        $this->setId( $projectManager->addProject( $name ) );
    }

    public function renameProject( $projectId, $newName )
    {
        $this->principal->checkAdministrator();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId );
        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $projectManager->renameProject( $project, $newName ) );
    }

    public function archiveProject( $projectId )
    {
        $this->principal->checkAdministrator();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId );

        $this->setOkIf( $projectManager->archiveProject( $project ) );
    }

    public function deleteProject( $projectId, $force )
    {
        $this->principal->checkAdministrator();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId );
        $this->validator->checkBooleanValue( $force );

        $this->setOkIf( $projectManager->deleteProject( $project, $force ? System_Api_ProjectManager::ForceDelete : 0 ) );
    }

    public function addFolder( $projectId, $typeId, $name )
    {
        $this->principal->checkAuthenticated();

        $typeManager = new System_Api_TypeManager();
        $projectManager = new System_Api_ProjectManager();

        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $type = $typeManager->getIssueType( $typeId );
        $this->validator->checkString( $name, System_Const::NameMaxLength );

        $this->setId( $projectManager->addFolder( $project, $type, $name ) );
    }

    public function renameFolder( $folderId, $newName )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId, System_Api_ProjectManager::RequireAdministrator );
        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $projectManager->renameFolder( $folder, $newName ) );
    }

    public function deleteFolder( $folderId, $force )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId, System_Api_ProjectManager::RequireAdministrator );
        $this->validator->checkBooleanValue( $force );

        $this->setOkIf( $projectManager->deleteFolder( $folder, $force ? System_Api_ProjectManager::ForceDelete : 0 ) );
    }

    public function moveFolder( $folderId, $projectId )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId, System_Api_ProjectManager::RequireAdministrator );
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );

        $this->setOkIf( $projectManager->moveFolder( $folder, $project ) );
    }

    public function addProjectDescription( $projectId, $text, $format )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $this->validator->checkString( $text, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $format );

        $this->setId( $projectManager->addProjectDescription( $project, $text, $format ) );
    }

    public function editProjectDescription( $projectId, $newText, $newFormat )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $descr = $projectManager->getProjectDescription( $project );
        $this->validator->checkString( $newText, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $newFormat );

        $this->setIdIf( $projectManager->editProjectDescription( $descr, $newText, $newFormat ) );
    }

    public function deleteProjectDescription( $projectId )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $descr = $projectManager->getProjectDescription( $project );

        $this->setId( $projectManager->deleteProjectDescription( $descr ) );
    }
    
    public function setProjectAccess( $projectId, $isPublic )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $project = $projectManager->getProject( $projectId, System_Api_ProjectManager::RequireAdministrator );
        $this->validator->checkBooleanValue( $isPublic );

        $this->setOkIf( $projectManager->setProjectAccess( $project, $isPublic ) );
    }

    public function listIssues( $folderId, $sinceStamp )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $issueManager = new System_Api_IssueManager();

        $folder = $projectManager->getFolder( $folderId );

        if ( $folder[ 'stamp_id' ] > $sinceStamp ) {
            $this->addRow( 'folders', $folder );
            $this->addTable( 'issues', $issueManager->getIssues( $folder, $sinceStamp ) );
            $this->addTable( 'attr_values', $issueManager->getAttributeValuesForFolder( $folder, $sinceStamp ) );
            $this->addTable( 'issue_stubs', $issueManager->getIssueStubs( $folder, $sinceStamp ) );
        }
    }

    public function getDetails( $issueId, $sinceStamp, $markAsRead )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );

        $this->validator->checkBooleanValue( $markAsRead );

        if ( $issue[ 'stamp_id' ] > $sinceStamp ) {
            $this->addRow( 'issues', $issue );
            $this->addTable( 'attr_values', $issueManager->getAttributeValuesForIssue( $issue ) );

            if ( $issueManager->isDescriptionModified( $issue, $sinceStamp ) )
                $this->addRow( 'description', $issueManager->getDescription( $issue ) );
            else if ( $issueManager->isDescriptionDeleted( $issue, $sinceStamp ) )
                $this->addRow( 'descr_stub', $issue );

            $changes = $issueManager->getChanges( $issue, $sinceStamp );

            $this->addTable( 'changes', $changes );
            $this->addTable( 'comments', $issueManager->getChangesOfType( $changes, System_Const::CommentAdded ) );
            $this->addTable( 'files', $issueManager->getChangesOfType( $changes, System_Const::FileAdded ) );

            $this->addTable( 'change_stubs', $issueManager->getChangeStubs( $issue, $sinceStamp ) );

            if ( $markAsRead ) {
                $stateManager = new System_Api_StateManager();
                $stateManager->setIssueRead( $issue, $issue[ 'stamp_id' ] );
            }
        }
    }

    public function addIssue( $folderId, $name )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $typeManager = new System_Api_TypeManager();
        $issueManager = new System_Api_IssueManager();

        $folder = $projectManager->getFolder( $folderId );
        $this->validator->checkString( $name, System_Const::ValueMaxLength );

        $values = $typeManager->getDefaultAttributeValuesForFolder( $folder );

        $this->setId( $issueManager->addIssue( $folder, $name, $values ) );
    }

    public function renameIssue( $issueId, $newName )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );
        $this->validator->checkString( $newName, System_Const::ValueMaxLength );

        $this->setIdIf( $issueManager->renameIssue( $issue, $newName ) );
    }

    public function deleteIssue( $issueId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministrator );

        $this->setIdIf( $issueManager->deleteIssue( $issue ) );
    }

    public function moveIssue( $issueId, $folderId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministrator );

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId, System_Api_ProjectManager::RequireAdministrator );

        $this->setIdIf( $issueManager->moveIssue( $issue, $folder ) );
    }

    public function setValue( $issueId, $attributeId, $newValue )
    {
        $this->principal->checkAuthenticated();

        $typeManager = new System_Api_TypeManager();
        $issueManager = new System_Api_IssueManager();

        $issue = $issueManager->getIssue( $issueId );
        $attribute = $typeManager->getAttributeTypeForIssue( $issue, $attributeId );
        $this->validator->setProjectId( $issue[ 'project_id' ] );
        $this->validator->checkAttributeValue( $attribute[ 'attr_def' ], $newValue );

        $this->setIdIf( $issueManager->setValue( $issue, $attribute, $newValue ) );
    }

    public function addComment( $issueId, $text, $format )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );
        $this->validator->checkString( $text, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $format );

        $this->setId( $issueManager->addComment( $issue, $text, $format ) );
    }

    public function editComment( $commentId, $newText, $newFormat )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $issueManager = new System_Api_IssueManager();
        $comment = $issueManager->getComment( $commentId, System_Api_IssueManager::RequireAdministratorOrOwner );
        $this->validator->checkString( $newText, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $newFormat );

        $this->setIdIf( $issueManager->editComment( $comment, $newText, $newFormat ) );
    }

    public function deleteComment( $commentId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $comment = $issueManager->getComment( $commentId, System_Api_IssueManager::RequireAdministratorOrOwner );

        $this->setId( $issueManager->deleteComment( $comment ) );
    }

    public function addAttachment( $issueId, $name, $description, $attachment )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'file_max_size' );

        if ( $attachment->getSize() > $maxLength )
            throw new Server_Error( Server_Error::UploadError );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );
        $this->validator->checkString( $name, System_Const::FileNameMaxLength );
        $this->validator->checkString( $description, System_Const::DescriptionMaxLength, System_Api_Validator::AllowEmpty );

        $this->setId( $issueManager->addFile( $issue, $attachment, $name, $description ) );
    }

    public function getAttachment( $fileId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $this->setReplyAttachment( $issueManager->getAttachment( $fileId ) );
    }

    public function editAttachment( $fileId, $newName, $newDescription )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $file = $issueManager->getFile( $fileId, System_Api_IssueManager::RequireAdministratorOrOwner );
        $this->validator->checkString( $newName, System_Const::FileNameMaxLength );
        $this->validator->checkString( $newDescription, System_Const::DescriptionMaxLength, System_Api_Validator::AllowEmpty );

        $this->setId( $issueManager->editFile( $file, $newName, $newDescription ) );
    }

    public function deleteAttachment( $fileId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $file = $issueManager->getFile( $fileId, System_Api_IssueManager::RequireAdministratorOrOwner );

        $this->setId( $issueManager->deleteFile( $file ) );
    }

    public function addDescription( $issueId, $text, $format )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministratorOrOwner );
        $this->validator->checkString( $text, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $format );

        $this->setId( $issueManager->addDescription( $issue, $text, $format ) );
    }

    public function editDescription( $issueId, $newText, $newFormat )
    {
        $this->principal->checkAuthenticated();

        $serverManager = new System_Api_ServerManager();
        $maxLength = $serverManager->getSetting( 'comment_max_length' );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministratorOrOwner );
        $descr = $issueManager->getDescription( $issue );
        $this->validator->checkString( $newText, $maxLength, System_Api_Validator::MultiLine );
        $this->validator->checkTextFormat( $newFormat );

        $this->setIdIf( $issueManager->editDescription( $descr, $newText, $newFormat ) );
    }

    public function deleteDescription( $issueId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, System_Api_IssueManager::RequireAdministratorOrOwner );
        $descr = $issueManager->getDescription( $issue );

        $this->setId( $issueManager->deleteDescription( $descr ) );
    }

    public function findItem( $itemId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $this->setId( $issueManager->findItem( $itemId ) );
    }

    public function setPreference( $userId, $key, $newValue )
    {
        $this->principal->checkAdministratorOrSelf( $userId );

        $userManager = new System_Api_UserManager();
        $user = $userManager->getUser( $userId );

        $preferencesManager = new System_Api_PreferencesManager( $user );

        $this->validator->checkPreference( $key, $newValue );

        $this->setOkIf( $preferencesManager->setPreference( $key, $newValue ) );
    }

    public function addView( $typeId, $name, $definition, $isPublic )
    {
        $this->principal->checkAuthenticated();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $this->validator->checkString( $name, System_Const::NameMaxLength );
        $this->validator->checkViewDefinition( $attributes, $definition );
        $this->validator->checkBooleanValue( $isPublic );

        $viewManager = new System_Api_ViewManager();

        if ( $isPublic ) {
            $this->principal->checkAdministrator();
            $this->setId( $viewManager->addPublicView( $type, $name, $definition ) );
        } else {
            $this->setId( $viewManager->addPersonalView( $type, $name, $definition ) );
        }
    }

    public function renameView( $viewId, $newName )
    {
        $this->principal->checkAuthenticated();

        $viewManager = new System_Api_ViewManager();
        $view = $viewManager->getView( $viewId, System_Api_ViewManager::AllowEdit );

        $this->validator->checkString( $newName, System_Const::NameMaxLength );

        $this->setOkIf( $viewManager->renameView( $view, $newName ) );
    }

    public function modifyView( $viewId, $newDefinition )
    {
        $this->principal->checkAuthenticated();

        $viewManager = new System_Api_ViewManager();
        $view = $viewManager->getView( $viewId, System_Api_ViewManager::AllowEdit );

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueTypeForView( $view );
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $this->validator->checkViewDefinition( $attributes, $newDefinition );

        $this->setOkIf( $viewManager->modifyView( $view, $newDefinition ) );
    }

    public function publishView( $viewId, $isPublic )
    {
        $this->principal->checkAdministrator();

        $viewManager = new System_Api_ViewManager();
        $view = $viewManager->getView( $viewId, System_Api_ViewManager::AllowEdit );

        $this->validator->checkBooleanValue( $isPublic );

        if ( $isPublic )
            $this->setOkIf( $viewManager->publishView( $view ) );
        else
            $this->setOkIf( $viewManager->unpublishView( $view ) );
    }

    public function deleteView( $viewId )
    {
        $this->principal->checkAuthenticated();

        $viewManager = new System_Api_ViewManager();
        $view = $viewManager->getView( $viewId, System_Api_ViewManager::AllowEdit );

        $this->setOkIf( $viewManager->deleteView( $view ) );
    }

    public function setViewSetting( $typeId, $key, $newValue )
    {
        $this->principal->checkAdministrator();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );
        $attributes = $typeManager->getAttributeTypesForIssueType( $type );

        $this->validator->checkViewSetting( $type, $attributes, $key, $newValue );

        $viewManager = new System_Api_ViewManager();
        $this->setOkIf( $viewManager->setViewSetting( $type, $key, $newValue ) );
    }

    public function listStates( $sinceState )
    {
        $this->principal->checkAuthenticated();

        $stateManager = new System_Api_StateManager();
        $this->addTable( 'issue_states', $stateManager->getStates( $sinceState ) );
    }

    public function setIssueRead( $issueId, $readId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );

        $stateManager = new System_Api_StateManager();
        $this->setIdIf( $stateManager->setIssueRead( $issue, $readId ) );
    }

    public function setFolderRead( $folderId, $readId )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId );

        $stateManager = new System_Api_StateManager();
        $this->setOkIf( $stateManager->setFolderRead( $folder, $readId ) );
    }

    public function addSubscription( $issueId )
    {
        $this->principal->checkAuthenticated();

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId );

        $subscriptionManager = new System_Api_SubscriptionManager();
        $this->setId( $subscriptionManager->addSubscription( $issue ) );
    }

    public function deleteSubscription( $subscriptionId )
    {
        $this->principal->checkAuthenticated();

        $subscriptionManager = new System_Api_SubscriptionManager();
        $subscription = $subscriptionManager->getSubscription( $subscriptionId );

        $this->setOkIf( $subscriptionManager->deleteSubscription( $subscription ) );
    }

    public function addAlert( $folderId, $viewId, $alertEmail, $summaryDays, $summaryHours, $isPublic )
    {
        $this->principal->checkAuthenticated();

        $projectManager = new System_Api_ProjectManager();
        $folder = $projectManager->getFolder( $folderId, $isPublic ? System_Api_ProjectManager::RequireAdministrator : 0 );

        if ( $viewId != 0 ) {
            $typeManager = new System_Api_TypeManager();
            $type = $typeManager->getIssueTypeForFolder( $folder );

            $viewManager = new System_Api_ViewManager();
            $view = $viewManager->getViewForIssueType( $type, $viewId, $isPublic ? System_Api_ViewManager::IsPublic : 0 );
        } else {
            $view = null;
        }

        $this->validator->checkAlertEmail( $alertEmail );
        $this->validator->checkSummaryDays( $alertEmail, $summaryDays );
        $this->validator->checkSummaryHours( $alertEmail, $summaryHours );
        $this->validator->checkBooleanValue( $isPublic );

        $alertManager = new System_Api_AlertManager();
        $this->setId( $alertManager->addAlert( $folder, $view, $alertEmail, $summaryDays, $summaryHours, $isPublic ? System_Api_AlertManager::IsPublic : 0 ) );
    }

    public function addGlobalAlert( $typeId, $viewId, $alertEmail, $summaryDays, $summaryHours, $isPublic )
    {
        if ( $isPublic )
            $this->principal->checkAdministrator();
        else
            $this->principal->checkAuthenticated();

        $typeManager = new System_Api_TypeManager();
        $type = $typeManager->getIssueType( $typeId );

        if ( $viewId != 0 ) {
            $viewManager = new System_Api_ViewManager();
            $view = $viewManager->getViewForIssueType( $type, $viewId, $isPublic ? System_Api_ViewManager::IsPublic : 0 );
        } else {
            $view = null;
        }

        $this->validator->checkAlertEmail( $alertEmail );
        $this->validator->checkSummaryDays( $alertEmail, $summaryDays );
        $this->validator->checkSummaryHours( $alertEmail, $summaryHours );
        $this->validator->checkBooleanValue( $isPublic );

        $alertManager = new System_Api_AlertManager();
        $this->setId( $alertManager->addGlobalAlert( $type, $view, $alertEmail, $summaryDays, $summaryHours, $isPublic ? System_Api_AlertManager::IsPublic : 0 ) );
    }

    public function modifyAlert( $alertId, $alertEmail, $summaryDays, $summaryHours )
    {
        $this->principal->checkAuthenticated();

        $alertManager = new System_Api_AlertManager();
        $alert = $alertManager->getAlert( $alertId );

        $this->validator->checkAlertEmail( $alertEmail );
        $this->validator->checkSummaryDays( $alertEmail, $summaryDays );
        $this->validator->checkSummaryHours( $alertEmail, $summaryHours );

        $this->setOkIf( $alertManager->modifyAlert( $alert, $alertEmail, $summaryDays, $summaryHours ) );
    }

    public function deleteAlert( $alertId )
    {
        $this->principal->checkAuthenticated();

        $alertManager = new System_Api_AlertManager();
        $alert = $alertManager->getAlert( $alertId );

        $this->setOkIf( $alertManager->deleteAlert( $alert ) );
    }

    private function addTable( $group, $table )
    {
        $this->reply[ $group ] = $table;
    }

    private function addRow( $group, $row )
    {
        $this->reply[ $group ][] = $row;
    }

    private function setId( $id )
    {
        $this->reply[ 'id' ][][ 'id' ] = $id;
    }

    private function setIdIf( $id )
    {
        if ( $id )
            $this->setId( $id );
    }

    private function setOk()
    {
        $this->reply[ 'ok' ][] = array();
    }

    private function setOkIf( $condition )
    {
        if ( $condition )
            $this->setOk();
    }

    private function setReplyAttachment( $attachment )
    {
        $this->reply = $attachment;
    }
}
