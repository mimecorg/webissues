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
        $this->folderId = $this->alert[ 'folder_id' ];
        $this->typeId = $this->alert[ 'type_id' ];
        $this->viewId = $this->alert[ 'view_id' ];

        $typeManager = new System_Api_TypeManager();

        if ( $this->folderId != 0 ) {
            $projectManager = new System_Api_ProjectManager();
            $folder = $projectManager->getFolder( $this->folderId );

            $this->projectName = $folder[ 'project_name' ];
            $this->folderName = $folder[ 'folder_name' ];

            $type = $typeManager->getIssueTypeForFolder( $folder );
        } else {
            $typeManager = new System_Api_TypeManager();
            $type = $typeManager->getIssueType( $this->typeId );
            $folder = null;

            $this->typeName = $type[ 'type_name' ];
        }

        $this->queryGenerator = new System_Api_QueryGenerator();
        if ( $folder != null )
            $this->queryGenerator->setFolder( $folder );
        else
            $this->queryGenerator->setIssueType( $type );

        $viewManager = new System_Api_ViewManager();
        if ( $this->viewId ) {
            $view = $viewManager->getView( $this->viewId );
            $definition = $view[ 'view_def' ];
            $this->viewName = $view[ 'view_name' ];
        } else {
            $definition = $viewManager->getViewSetting( $type, 'default_view' );
            $this->viewName = $this->tr( 'All Issues' );
        }

        $initial = $viewManager->getViewSetting( $type, 'initial_view' );

        if ( $initial != '' && $initial != $this->viewId && !$viewManager->isPublicViewForIssueType( $type, $initial ) )
            $initial = '';

        if ( $this->viewId == $initial )
            $this->linkViewId = null;
        else if ( $this->viewId != null )
            $this->linkViewId = $this->viewId;
        else
            $this->linkViewId = 0;

        if ( $definition != null )
            $this->queryGenerator->setViewDefinition( $definition );

        if ( $this->alert[ 'alert_email' ] != System_Const::SummaryReportEmail ) {
            $this->queryGenerator->setSinceStamp( $this->alert[ 'stamp_id' ] );

            $preferencesManager = new System_Api_PreferencesManager();
            if ( $preferencesManager->getPreference( 'notify_no_read' ) == '1' )
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
        if ( !empty( $this->folderName ) )
            $this->view->setSlot( 'subject', $this->projectName . ' - ' . $this->folderName . ' - ' . $this->viewName );
        else
            $this->view->setSlot( 'subject', $this->typeName . ' - ' . $this->viewName );

        $serverManager = new System_Api_ServerManager();

        $this->columns = $this->queryGenerator->getColumnNames();

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

        $preferencesManager = new System_Api_PreferencesManager();

        if ( $preferencesManager->getPreference( 'notify_details' ) == '1' ) {
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

                foreach ( $attributeValues as &$value ) {
                    $text = $formatter->convertAttributeValue( $value[ 'attr_def' ], $value[ 'attr_value' ], System_Api_Formatter::MultiLine );
                    $value[ 'attr_value' ] = $this->convertToParagraphs( $text );
                }

                $type = $typeManager->getIssueTypeForIssue( $issue );
                $detail[ 'attribute_values' ] = $viewManager->sortByAttributeOrder( $type, $attributeValues );

                $sinceStamp = $this->alert[ 'stamp_id' ];

                if ( $this->alert[ 'alert_email' ] != System_Const::SummaryReportEmail ) {
                    if ( $preferencesManager->getPreference( 'notify_no_read' ) == '1' ) {
                        if ( $sinceStamp < $this->issue[ 'read_id' ] )
                            $sinceStamp = $this->issue[ 'read_id' ];
                    }
                }

                if ( $issue[ 'descr_id' ] > $sinceStamp ) {
                    $descr = $issueManager->getDescription( $issue );
                    $descr[ 'is_modified' ] = ( $descr[ 'modified_date' ] - $issue[ 'created_date' ] ) > 180 || $descr[ 'modified_user' ] != $issue[ 'created_user' ];
                    $descr[ 'modified_date' ] = $formatter->formatDateTime( $descr[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
                    if ( $descr[ 'descr_format' ] == System_Const::TextWithMarkup )
                        $text = System_Web_MarkupProcessor::convertToRawHtml( $descr[ 'descr_text' ], $prettyPrint );
                    else
                        $text = System_Web_LinkLocator::convertToRawHtml( $descr[ 'descr_text' ] );
                    $descr[ 'descr_text' ] = $this->convertToParagraphs( $text );
                    $detail[ 'description' ] = $descr;
                }

                if ( $issue[ 'stamp_id' ] > $sinceStamp ) {
                    $historyProvider->setIssueId( $issueId );
                    $historyProvider->setSinceStamp( $sinceStamp );
                    
                    $filter = $preferencesManager->getPreferenceOrSetting( 'history_filter' );
                    $order = $preferencesManager->getPreferenceOrSetting( 'history_order' );

                    $query = $historyProvider->generateSelectQuery( $filter );
                    $page = $connection->queryPageArgs( $query, $historyProvider->getOrderBy( $order ), 1000, 0, $historyProvider->getQueryArguments() );

                    $history = $historyProvider->processPage( $page );

                    foreach ( $history as $id => &$item ) {
                        $item[ 'change_id' ] = '#' . $item[ 'change_id' ];
                        $item[ 'is_modified' ] = ( $item[ 'modified_date' ] - $item[ 'created_date' ] ) > 180 || $item[ 'modified_user' ] != $item[ 'created_user' ];
                        $item[ 'created_date' ] = $formatter->formatDateTime( $item[ 'created_date' ], System_Api_Formatter::ToLocalTimeZone );
                        $item[ 'modified_date' ] = $formatter->formatDateTime( $item[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
                        if ( isset( $item[ 'comment_text' ] ) ) {
                            if ( $item[ 'comment_format' ] == System_Const::TextWithMarkup )
                                $text = System_Web_MarkupProcessor::convertToRawHtml( $item[ 'comment_text' ], $prettyPrint );
                            else
                                $text = System_Web_LinkLocator::convertToRawHtml( $item[ 'comment_text' ] );
                            $item[ 'comment_text' ] = $this->convertToParagraphs( $text );
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

    private function convertToParagraphs( $text )
    {
        $text = System_Web_Escaper::wrap( $text );
        $text = str_replace( "\n", "<br>", $text );
        $text = str_replace( "  ", "&nbsp; ", $text );
        $text = str_replace( "\t", "&nbsp; &nbsp; &nbsp; &nbsp; ", $text );
        return new System_Web_RawValue( $text );
    }
}
