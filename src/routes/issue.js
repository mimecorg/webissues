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

import EditIssue from '@/components/forms/EditIssue.vue';
import GoToItem from '@/components/forms/GoToItem.vue';
import IssueDetails from '@/components/forms/IssueDetails.vue';

export default function makeIssueRoutes( ajax, store ) {
  return function issueRoutes( route ) {
    function loadIssueDetails( issueId ) {
      if ( store.state.issue.issueId != issueId ) {
        store.commit( 'issue/clear' );
        store.commit( 'issue/setIssueId', issueId );
      }
      return store.dispatch( 'issue/load' ).then( () => {
        return { component: IssueDetails, size: 'large' };
      } );
    }

    route( 'IssueDetails', '/issue/:issueId', ( { issueId } ) => {
      return loadIssueDetails( issueId );
    } );

    route( 'IssueItem', '/issue/:issueId/item/:itemId', ( { issueId, itemId } ) => {
      return loadIssueDetails( issueId );
    } );

    route( 'GoToItem', '/item/goto', () => {
      return Promise.resolve( { component: GoToItem } );
    } );

    route( 'Item', '/item/:itemId', ( { itemId } ) => {
      return ajax.post( '/server/api/issue/finditem.php', { itemId } ).then( issueId => {
        if ( itemId == issueId )
          return { replace: 'IssueDetails', issueId };
        else
          return { replace: 'IssueItem', issueId, itemId };
      } );
    } );

    route( 'EditIssue', '/issue/:issueId/edit', ( { issueId } ) => {
      return ajax.post( '/server/api/issue/load.php', { issueId } ).then( ( { details } ) => {
        return { component: EditIssue, issueId, name: details.name };
      } );
    } );
  }
}
