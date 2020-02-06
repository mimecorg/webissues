<?php
/**************************************************************************
* This file is part of the WebIssues Server program
* Copyright (C) 2006 Michał Męciński
* Copyright (C) 2007-2020 WebIssues Team
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

class Common_Mail_Notification extends System_Web_Component
{
    private $alert = null;
    private $queryGenerator = null;
    private $page = null;

    protected function __construct( $alert )
    {
        parent::__construct();

        $this->alert = $alert;
    }

    public function prepare()
    {
        $this->typeId = $this->alert[ 'type_id' ];
        $this->viewId = $this->alert[ 'view_id' ];
        $this->projectId = $this->alert[ 'project_id' ];
        $this->folderId = $this->alert[ 'folder_id' ];
        $this->alertType = $this->alert[ 'alert_type' ];

        $typeManager = new System_Api_TypeManager();
        $projectManager = new System_Api_ProjectManager();
        $viewManager = new System_Api_ViewManager();

        $this->queryGenerator = new System_Api_QueryGenerator();

        $type = $typeManager->getIssueType( $this->typeId );
        $this->queryGenerator->setIssueType( $type );

        $this->typeName = $type[ 'type_name' ];

        if ( $this->folderId != null ) {
            $folder = $projectManager->getFolder( $this->folderId );
            $this->queryGenerator->setFolder( $folder );

            $this->projectName = $folder[ 'project_name' ];
            $this->folderName = $folder[ 'folder_name' ];
        } else if ( $this->projectId != null ) {
            $project = $projectManager->getProject( $this->projectId );
            $this->queryGenerator->setProject( $project );

            $this->projectName = $project[ 'project_name' ];
        }

        if ( $this->viewId != null ) {
            $view = $viewManager->getView( $this->viewId );
            $definition = $view[ 'view_def' ];
            $this->viewName = $view[ 'view_name' ];
        } else {
            $definition = $viewManager->getViewSetting( $type, 'default_view' );
            $this->viewName = $this->t( 'text.AllIssues' );
        }

        if ( $definition != null )
            $this->queryGenerator->setViewDefinition( $definition );

        if ( $this->alert[ 'alert_type' ] != System_Const::IssueReport ) {
            $this->queryGenerator->setSinceStamp( $this->alert[ 'stamp_id' ] );

            if ( $this->alert[ 'alert_type' ] == System_Const::Alert )
                $this->queryGenerator->setNoRead( true );
        }

        $connection = System_Core_Application::getInstance()->getConnection();

        $query = $this->queryGenerator->generateSelectQuery();
        $this->page = $connection->queryPageArgs( $query, $this->queryGenerator->getOrderBy(), 1000, 0, $this->queryGenerator->getQueryArguments() );

        if ( empty( $this->page ) )
            return false;

        return true;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Layout' );

        $subject = $this->typeName . ' - ' . $this->viewName;
        if ( !empty( $this->folderName ) )
            $subject .= ' ' . $this->t( 'text.in' ) . ' ' . $this->projectName . ' - ' . $this->folderName;
        else if ( !empty( $this->projectName ) )
            $subject .= ' ' . $this->t( 'text.in' ) . ' ' . $this->projectName;

        $this->view->setSlot( 'subject', $this->t( 'subject.Notification', array( $subject ) ) );

        $this->view->setSlot( 'withCssGrid', true );

        if ( $this->viewId != null ) {
            if ( $this->folderId != null )
                $this->viewUrl = '/views/' . $this->viewId . '/folders/' . $this->folderId . '/issues';
            else if ( $this->projectId != null )
                $this->viewUrl = '/views/' . $this->viewId . '/projects/' . $this->projectId . '/issues';
            else
                $this->viewUrl = '/views/' . $this->viewId . '/issues';
        } else {
            if ( $this->folderId != null )
                $this->viewUrl = '/folders/' . $this->folderId . '/issues';
            else if ( $this->projectId != null )
                $this->viewUrl = '/types/' . $this->typeId . '/projects/' . $this->projectId . '/issues';
            else
                $this->viewUrl = '/types/' . $this->typeId . '/issues';
        }

        $this->columns = $this->queryGenerator->getColumnNames();

        $serverManager = new System_Api_ServerManager();
        if ( $serverManager->getSetting( 'hide_id_column' ) == 1 )
            unset( $this->columns[ System_Api_Column::ID ] );

        $helper = new System_Web_ColumnHelper();
        $this->headers = $helper->getColumnHeaders() + $this->queryGenerator->getUserColumnHeaders();

        $formatter = new System_Api_Formatter();

        $this->issues = array();
        foreach ( $this->page as $row ) {
            $issue = array();
            foreach ( $this->columns as $column => $name ) {
                $value = $row[ $name ];

                switch ( $column ) {
                    case System_Api_Column::ID:
                        $value = '#' . $value;
                        break;
                    case System_Api_Column::ModifiedDate:
                    case System_Api_Column::CreatedDate:
                        $value = $formatter->formatDateTime( $value, System_Api_Formatter::ToLocalTimeZone );
                        break;
                    default:
                        if ( $column > System_Api_Column::UserDefined ) {
                            $attribute = $this->queryGenerator->getAttributeForColumn( $column );
                            $value = $formatter->convertAttributeValue( $attribute[ 'attr_def' ], $value );
                        }
                        break;
                }

                $issue[ $name ] = $this->truncate( $value, 60 );

                if ( $column == System_Api_Column::Location )
                    $issue[ 'project_name' ] = $row[ 'project_name' ];
            }
            $this->issues[ $row[ 'issue_id' ] ] = $issue;
        }

        $this->details = array();

        if ( $this->alert[ 'alert_type' ] != System_Const::IssueReport ) {
            $this->view->setSlot( 'withCssDetails', true );

            $serverManager = new System_Api_ServerManager();
            $issueManager = new System_Api_IssueManager();
            $typeManager = new System_Api_TypeManager();
            $viewManager = new System_Api_ViewManager();

            $connection = System_Core_Application::getInstance()->getConnection();

            $historyProvider = new System_Api_HistoryProvider();
            $localeHelper = new System_Web_LocaleHelper();

            $hideEmpty = $serverManager->getSetting( 'hide_empty_values' );

            foreach ( $this->page as $row ) {
                $issueId = $row[ 'issue_id' ];
                $issue = $issueManager->getIssue( $issueId );

                $detail = $issue;
                $detail[ 'issue_id' ] = '#' . $issue[ 'issue_id' ];
                $detail[ 'created_date' ] = $formatter->formatDateTime( $issue[ 'created_date' ], System_Api_Formatter::ToLocalTimeZone );
                $detail[ 'modified_date' ] = $formatter->formatDateTime( $issue[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );

                $attributeValues = $issueManager->getAllAttributeValuesForIssue( $issue, $hideEmpty == '1' ? System_Api_IssueManager::HideEmptyValues : 0 );

                foreach ( $attributeValues as &$value )
                    $value[ 'attr_value' ] = $formatter->convertAttributeValue( $value[ 'attr_def' ], $value[ 'attr_value' ], System_Api_Formatter::MultiLine );

                $type = $typeManager->getIssueTypeForIssue( $issue );
                $detail[ 'attribute_values' ] = $viewManager->sortByAttributeOrder( $type, $attributeValues );

                $sinceStamp = $this->alert[ 'stamp_id' ];

                if ( $this->alert[ 'alert_type' ] != System_Const::ChangeReport && $sinceStamp < $issue[ 'read_id' ] )
                    $sinceStamp = $issue[ 'read_id' ];

                if ( $issue[ 'descr_id' ] > $sinceStamp ) {
                    $descr = $issueManager->getDescription( $issue );
                    $descr[ 'is_modified' ] = ( $descr[ 'modified_date' ] - $issue[ 'created_date' ] ) > 180 || $descr[ 'modified_user' ] != $issue[ 'created_user' ];
                    $descr[ 'modified_date' ] = $formatter->formatDateTime( $descr[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
                    if ( $descr[ 'descr_format' ] == System_Const::TextWithMarkup )
                        $descr[ 'descr_text' ] = System_Web_MarkupProcessor::convertToRawHtml( $descr[ 'descr_text' ], $prettyPrint );
                    else
                        $descr[ 'descr_text' ] = System_Web_LinkLocator::convertToRawHtml( $descr[ 'descr_text' ] );
                    $detail[ 'description' ] = $descr;
                }

                if ( $issue[ 'stamp_id' ] > $sinceStamp ) {
                    $historyProvider->setIssueId( $issueId );
                    $historyProvider->setSinceStamp( $sinceStamp );

                    $order = $serverManager->getSetting( 'history_order' );

                    $query = $historyProvider->generateSelectQuery( System_Api_HistoryProvider::AllHistory );
                    $page = $connection->queryPageArgs( $query, $historyProvider->getOrderBy( $order ), 1000, 0, $historyProvider->getQueryArguments() );

                    $history = $historyProvider->processPage( $page );

                    foreach ( $history as $id => &$item ) {
                        $item[ 'change_id' ] = '#' . $item[ 'change_id' ];
                        $item[ 'is_modified' ] = ( $item[ 'modified_date' ] - $item[ 'created_date' ] ) > 180 || $item[ 'modified_user' ] != $item[ 'created_user' ];
                        $item[ 'created_date' ] = $formatter->formatDateTime( $item[ 'created_date' ], System_Api_Formatter::ToLocalTimeZone );
                        $item[ 'modified_date' ] = $formatter->formatDateTime( $item[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
                        if ( isset( $item[ 'comment_text' ] ) ) {
                            if ( $item[ 'comment_format' ] == System_Const::TextWithMarkup )
                                $item[ 'comment_text' ] = System_Web_MarkupProcessor::convertToRawHtml( $item[ 'comment_text' ], $prettyPrint );
                            else
                                $item[ 'comment_text' ] = System_Web_LinkLocator::convertToRawHtml( $item[ 'comment_text' ] );
                        }
                        if ( isset( $item[ 'file_size' ] ) )
                            $item[ 'file_size' ] = $localeHelper->formatFileSize( $item[ 'file_size' ] );
                        if ( isset( $item[ 'changes' ] ) ) {
                            foreach ( $item[ 'changes' ] as &$change ) {
                                if ( $change[ 'attr_def' ] != null ) {
                                    $change[ 'value_new' ] = $formatter->convertAttributeValue( $change[ 'attr_def' ], $change[ 'value_new' ] );
                                    $change[ 'value_old' ] = $formatter->convertAttributeValue( $change[ 'attr_def' ], $change[ 'value_old' ] );
                                }
                            }
                        }
                    }

                    $detail[ 'history' ] = $history;
                }

                $this->details[ $issueId ] = $detail;
            }
        }
    }
}
