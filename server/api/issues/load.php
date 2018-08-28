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

require_once( '../../../system/bootstrap.inc.php' );

class Server_Api_Issues_Load
{
    public $access = 'anonymous';

    public $params = array(
        'issueId' => array( 'type' => 'int', 'required' => true ),
        'description' => array( 'type' => 'bool', 'default' => false ),
        'attributes' => array( 'type' => 'bool', 'default' => false ),
        'history' => array( 'type' => 'bool', 'default' => false ),
        'modifiedSince' => array( 'type' => 'int', 'default' => 0 ),
        'filter' => array( 'type' => 'int', 'default' => System_Api_HistoryProvider::AllHistory ),
        'html' => array( 'type' => 'bool', 'default' => false ),
        'unread' => array( 'type' => 'bool', 'default' => false ),
        'access' => 'string'
    );

    public function run( $issueId, $description, $attributes, $history, $modifiedSince, $filter, $html, $unread, $access )
    {
        if ( $filter < System_Api_HistoryProvider::AllHistory || $filter > System_Api_HistoryProvider::CommentsAndFiles )
            throw new Server_Error( Server_Error::InvalidArguments );

        $flags = 0;
        if ( $access == 'admin' )
            $flags = System_Api_IssueManager::RequireAdministrator;
        else if ( $access == 'adminOrOwner' )
            $flags = System_Api_IssueManager::RequireAdministratorOrOwner;
        else if ( $access != null )
            throw new Server_Error( Server_Error::InvalidArguments );

        $issueManager = new System_Api_IssueManager();
        $issue = $issueManager->getIssue( $issueId, $flags );

        $formatter = new System_Api_Formatter();
        $principal = System_Api_Principal::getCurrent();

        $resultDetails[ 'id' ] = $issue[ 'issue_id' ];
        $resultDetails[ 'name' ] = $issue[ 'issue_name' ];
        $resultDetails[ 'folderId' ] = $issue[ 'folder_id' ];
        $resultDetails[ 'typeId' ] = $issue[ 'type_id' ];
        $resultDetails[ 'createdDate' ] = $issue[ 'created_date' ];
        $resultDetails[ 'createdBy' ] = $issue[ 'created_user' ];
        $resultDetails[ 'modifiedDate' ] = $issue[ 'modified_date' ];
        $resultDetails[ 'modifiedBy' ] = $issue[ 'modified_user' ];
        $resultDetails[ 'stamp' ] = $issue[ 'stamp_id' ];

        $result[ 'details' ] = $resultDetails;

        if ( $html )
            System_Web_Base::setLinkMode( System_Web_Base::RouteLinks );

        if ( $description ) {
            if ( $issue[ 'descr_id' ] != null ) {
                $descr = $issueManager->getDescription( $issue );

                $resultDescription[ 'modifiedBy' ] = $descr[ 'modified_user' ];
                $resultDescription[ 'modifiedDate' ] = $descr[ 'modified_date' ];
                $resultDescription[ 'text' ] = $this->convertText( $descr[ 'descr_text' ], $html, $descr[ 'descr_format' ] );
                $resultDescription[ 'format' ] = $descr[ 'descr_format' ];

                $result[ 'description' ] = $resultDescription;
            } else {
                $result[ 'description' ] = null;
            }
        }

        if ( $attributes ) {
            $attributeValues = $issueManager->getAllAttributeValuesForIssue( $issue );

            $typeManager = new System_Api_TypeManager();
            $type = $typeManager->getIssueTypeForIssue( $issue );

            $viewManager = new System_Api_ViewManager();
            $attributeValues = $viewManager->sortByAttributeOrder( $type, $attributeValues );

            $result[ 'attributes' ] = array();

            foreach( $attributeValues as $value ) {
                $resultAttr = array();
                $resultAttr[ 'id' ] = $value[ 'attr_id' ];
                $resultAttr[ 'value' ] = $value[ 'attr_value' ];
                $result[ 'attributes' ][] = $resultAttr;
            }
        }

        if ( $history ) {
            if ( $principal->isAuthenticated() ) {
                $stateManager = new System_Api_StateManager();
                $stateManager->setIssueRead( $issue, $unread ? 0 : $issue[ 'stamp_id' ] );
            }

            $historyProvider = new System_Api_HistoryProvider();
            $historyProvider->setIssueId( $issueId );

            if ( $modifiedSince > 0 )
                $historyProvider->setModifiedSince( $modifiedSince );

            $connection = System_Core_Application::getInstance()->getConnection();

            $query = $historyProvider->generateSelectQuery( $filter );
            $page = $connection->queryPageArgs( $query, $historyProvider->getOrderBy( System_Api_HistoryProvider::Ascending ), System_Const::INT_MAX, 0, $historyProvider->getQueryArguments() );

            $localeHelper = new System_Web_LocaleHelper();

            $result[ 'history' ] = array();

            foreach ( $page as $item ) {
                $resultItem = array();

                $resultItem[ 'id' ] = $item[ 'change_id' ];
                $resultItem[ 'type' ] = $item[ 'change_type' ];
                $resultItem[ 'createdDate' ] = $formatter->formatDateTime( $item[ 'created_date' ], System_Api_Formatter::ToLocalTimeZone );
                $resultItem[ 'createdBy' ] = $item[ 'created_by' ];
                if ( ( $item[ 'modified_date' ] - $item[ 'created_date' ] ) > 180 || $item[ 'modified_user' ] != $item[ 'created_user' ] ) {
                    $resultItem[ 'modifiedDate' ] = $formatter->formatDateTime( $item[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
                    $resultItem[ 'modifiedBy' ] = $item[ 'modified_by' ];
                }

                if ( $item[ 'change_type' ] == System_Const::CommentAdded )
                    $resultItem[ 'text' ] = $this->convertText( $item[ 'comment_text' ], $html, $item[ 'comment_format' ] );

                if ( $item[ 'change_type' ] == System_Const::FileAdded ) {
                    $resultItem[ 'name' ] = $item[ 'file_name' ];
                    $resultItem[ 'description' ] = $this->convertText( $item[ 'file_descr' ], $html );
                    $resultItem[ 'size' ] = $localeHelper->formatFileSize( $item[ 'file_size' ] );
                }

                if ( $item[ 'change_type' ] == System_Const::IssueMoved ) {
                    $resultItem[ 'fromProject' ] = $item[ 'from_project_name' ];
                    $resultItem[ 'fromFolder' ] = $item[ 'from_folder_name' ];
                    $resultItem[ 'toProject' ] = $item[ 'to_project_name' ];
                    $resultItem[ 'toFolder' ] = $item[ 'to_folder_name' ];
                }

                if ( $item[ 'change_type' ] <= System_Const::ValueChanged ) {
                    if ( $item[ 'change_type' ] == System_Const::ValueChanged )
                        $resultItem[ 'name' ] = $item[ 'attr_name' ];

                    $newValue = $item[ 'value_new' ];
                    $oldValue = $item[ 'value_old' ];
                    if ( $item[ 'attr_def' ] != null ) {
                        $newValue = $formatter->convertAttributeValue( $item[ 'attr_def' ], $newValue );
                        $oldValue = $formatter->convertAttributeValue( $item[ 'attr_def' ], $oldValue );
                    }

                    $resultItem[ 'new' ] = $this->convertText( $newValue, $html );
                    $resultItem[ 'old' ] = $this->convertText( $oldValue, $html );

                    $resultItem[ 'ts' ] = $item[ 'created_date' ];
                    $resultItem[ 'uid' ] = $item[ 'created_user' ];
                }

                if ( $item[ 'change_type' ] == System_Const::CommentAdded || $item[ 'change_type' ] == System_Const::FileAdded )
                    $resultItem[ 'own' ] = $item[ 'created_user' ] == $principal->getUserId();

                $result[ 'history' ][] = $resultItem;
            }

            if ( $modifiedSince > 0 ) {
                $stubs = $issueManager->getChangeStubs( $issue, $modifiedSince );

                if ( !empty( $stubs ) ) {
                    $result[ 'stubs' ] = array();

                    foreach( $stubs as $stub )
                        $result[ 'stubs' ][] = $stub[ 'change_id' ];
                }
            }
        }

        return $result;
    }

    private function convertText( $text, $html, $format = System_Const::PlainText )
    {
        if ( $html ) {
            if ( $format == System_Const::TextWithMarkup )
                return System_Web_MarkupProcessor::convertToHtml( $text, $prettyPrint );
            else
                return System_Web_LinkLocator::convertToHtml( $text );
        } else {
            return $text;
        }
    }
}

System_Bootstrap::run( 'Server_Api_Application', 'Server_Api_Issues_Load' );
