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

class Common_Mail_Subscription extends System_Web_Component
{
    private $subscription = null;
    private $issue = null;
    private $historyProvider = null;
    private $page = null;

    protected function __construct( $subscription )
    {
        parent::__construct();

        $this->subscription = $subscription;
    }

    public function prepare()
    {
        $issueManager = new System_Api_IssueManager();

        $this->issueId = $this->subscription[ 'issue_id' ];
        $this->issue = $issueManager->getIssue( $this->issueId );

        $sinceStamp = $this->subscription[ 'stamp_id' ];

        if ( $this->issue[ 'descr_id' ] > $sinceStamp ) {
            $descr = $issueManager->getDescription( $this->issue );
            if ( $descr[ 'modified_user' ] != $this->subscription[ 'user_id' ] )
                $this->descr = $descr;
        }

        $connection = System_Core_Application::getInstance()->getConnection();

        $this->historyProvider = new System_Api_HistoryProvider();

        $this->historyProvider->setIssueId( $this->issueId );
        $this->historyProvider->setSinceStamp( $sinceStamp );

        if ( $this->subscription[ 'user_id' ] != null )
            $this->historyProvider->setExceptOwnChanges();
        else
            $this->historyProvider->setExceptSubscriptionId( $this->subscription[ 'subscription_id' ] );

        $serverManager = new System_Api_ServerManager();
        $order = $serverManager->getSetting( 'history_order' );

        $query = $this->historyProvider->generateSelectQuery( System_Api_HistoryProvider::AllHistory );
        $this->page = $connection->queryPageArgs( $query, $this->historyProvider->getOrderBy( $order ), 1000, 0, $this->historyProvider->getQueryArguments() );

        if ( empty( $this->descr ) && empty( $this->page ) )
            return false;

        return true;
    }

    protected function execute()
    {
        $this->view->setDecoratorClass( 'Common_Mail_Layout' );

        $subject = '[#' . $this->issueId . '] ' . $this->issue[ 'issue_name' ];

        $this->view->setSlot( 'subject', $this->t( 'subject.Notification', array( $subject ) ) );

        $this->view->setSlot( 'withCssDetails', true );

        $formatter = new System_Api_Formatter();
        $localeHelper = new System_Web_LocaleHelper();

        $this->details = $this->issue;
        $this->details[ 'issue_id' ] = '#' . $this->details[ 'issue_id' ];
        $this->details[ 'created_date' ] = $formatter->formatDateTime( $this->details[ 'created_date' ], System_Api_Formatter::ToLocalTimeZone );
        $this->details[ 'modified_date' ] = $formatter->formatDateTime( $this->details[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );

        $serverManager = new System_Api_ServerManager();
        $hideEmpty = $serverManager->getSetting( 'hide_empty_values' );

        $issueManager = new System_Api_IssueManager();

        $attributeValues = $issueManager->getAllAttributeValuesForIssue( $this->issue, $hideEmpty == '1' ? System_Api_IssueManager::HideEmptyValues : 0 );

        foreach ( $attributeValues as &$value )
            $value[ 'attr_value' ] = $formatter->convertAttributeValue( $value[ 'attr_def' ], $value[ 'attr_value' ], System_Api_Formatter::MultiLine );

        $typeManager = new System_Api_TypeManager();
        $viewManager = new System_Api_ViewManager();

        $type = $typeManager->getIssueTypeForIssue( $this->issue );
        $this->attributeValues = $viewManager->sortByAttributeOrder( $type, $attributeValues );

        if ( !empty( $this->descr ) ) {
            $this->descr[ 'is_modified' ] = ( $this->descr[ 'modified_date' ] - $this->issue[ 'created_date' ] ) > 180 || $this->descr[ 'modified_user' ] != $this->issue[ 'created_user' ];
            $this->descr[ 'modified_date' ] = $formatter->formatDateTime( $this->descr[ 'modified_date' ], System_Api_Formatter::ToLocalTimeZone );
            if ( $this->descr[ 'descr_format' ] == System_Const::TextWithMarkup )
                $this->descr[ 'descr_text' ] = System_Web_MarkupProcessor::convertToRawHtml( $this->descr[ 'descr_text' ], $prettyPrint );
            else
                $this->descr[ 'descr_text' ] = System_Web_LinkLocator::convertToRawHtml( $this->descr[ 'descr_text' ] );
        }

        $this->history = $this->historyProvider->processPage( $this->page );

        foreach ( $this->history as $id => &$item ) {
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

        if ( $this->subscription[ 'inbox_id' ] != null ) {
            $this->hasInbox = true;
            $this->separator = str_repeat( '-', 11 );
        }
    }
}
