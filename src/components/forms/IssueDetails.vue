<!--
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
-->

<template>
  <div class="container-fluid">

    <FormHeader v-bind:title="details.name" v-on:close="close">
      <button v-if="isAuthenticated" type="button" class="btn btn-primary" v-on:click="editIssue"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'IssueDetails.Edit' ) }}</button>
      <DropdownButton v-if="isAuthenticated" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'IssueDetails.More' )">
        <li><HyperLink v-on:click="cloneIssue"><span class="fa fa-clone" aria-hidden="true"></span> {{ $t( 'IssueDetails.CloneIssue' ) }}</HyperLink></li>
        <li v-if="canMoveDelete"><HyperLink><span class="fa fa-exchange" aria-hidden="true"></span> {{ $t( 'IssueDetails.MoveIssue' ) }}</HyperLink></li>
        <li v-if="canMoveDelete"><HyperLink><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'IssueDetails.DeleteIssue' ) }}</HyperLink></li>
        <li role="separator" class="divider"></li>
        <li v-if="unread"><HyperLink v-on:click="setUnread( false )"><span class="fa fa-check-circle-o" aria-hidden="true"></span> {{ $t( 'IssueDetails.MarkAsRead' ) }}</HyperLink></li>
        <li v-if="!unread"><HyperLink v-on:click="setUnread( true )"><span class="fa fa-check-circle" aria-hidden="true"></span> {{ $t( 'IssueDetails.MarkAsUnread' ) }}</HyperLink></li>
        <li role="separator" class="divider"></li>
        <li><HyperLink><span class="fa fa-envelope" aria-hidden="true"></span> {{ $t( 'IssueDetails.Subscribe' ) }}</HyperLink></li>
      </DropdownButton>
    </FormHeader>

    <div class="row">
      <div class="col-sm-4 col-sm-push-8">

        <div class="issue-details">
          <div class="issue-header">{{ $t( 'IssueDetails.Properties' ) }}</div>
          <div class="issue-details-title">{{ $t( 'IssueDetails.ID' ) }}</div>
          <div class="issue-details-value">#{{ details.id }}</div>
          <div class="issue-details-title">{{ $t( 'IssueDetails.Type' ) }}</div>
          <div class="issue-details-value">{{ details.type }}</div>
          <div class="issue-details-title">{{ $t( 'IssueDetails.Location' ) }}</div>
          <div class="issue-details-value">{{ details.project }} &mdash; {{ details.folder }}</div>
          <div class="issue-details-title">{{ $t( 'IssueDetails.Created' ) }}</div>
          <div class="issue-details-value">{{ details.createdDate }} &mdash; {{ details.createdBy }}</div>
          <div class="issue-details-title">{{ $t( 'IssueDetails.LastModified' ) }}</div>
          <div class="issue-details-value">{{ details.modifiedDate }} &mdash; {{ details.modifiedBy }}</div>
        </div>

        <div v-if="filteredAttributes.length > 0" class="issue-details">
          <div class="issue-header">{{ $t( 'IssueDetails.Attributes' ) }}</div>
          <template v-for="attribute in filteredAttributes">
            <div class="issue-details-title">{{ attribute.name }}</div>
            <div class="issue-details-value" v-html="attribute.value"></div>
          </template>
        </div>

      </div>
      <div class="col-sm-8 col-sm-pull-4">

        <div class="issue-group">
          <div class="issue-element issue-element-wide">
            <div class="issue-header">{{ $t( 'IssueDetails.Description' ) }}</div>
          </div>
          <div class="issue-element">
            <DropdownButton v-if="isAuthenticated && description != null" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'IssueDetails.Menu' )">
              <li><HyperLink v-on:click="replyDescription"><span class="fa fa-reply" aria-hidden="true"></span> {{ $t( 'IssueDetails.Reply' ) }}</HyperLink></li>
              <li v-if="canEditDescription"><HyperLink v-on:click="editDescription"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'IssueDetails.Edit' ) }}</HyperLink></li>
              <li v-if="canEditDescription"><HyperLink v-on:click="deleteDescription"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'IssueDetails.Delete' ) }}</HyperLink></li>
            </DropdownButton>
            <button v-else-if="canEditDescription" type="button" class="btn btn-default" v-on:click="addDescription">
              <span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'IssueDetails.Add' ) }}
            </button>
          </div>
        </div>

        <div v-if="description" class="issue-description">
          <div class="formatted-text" v-hljs="description.text"></div>
          <div v-if="description.modifiedDate" class="issue-last-edited">
            <span class="fa fa-pencil" aria-hidden="true"></span> {{ description.modifiedDate }} &mdash; {{ description.modifiedBy }}
          </div>
        </div>
        <div v-else class="alert alert-info">
          {{ $t( 'IssueDetails.NoDescription' ) }}
        </div>

        <div class="issue-group">
          <div class="issue-element issue-element-wide">
            <div class="issue-header">{{ getFilterText( filter ) }}</span></div>
          </div>
          <div class="issue-element">
            <button v-if="isAuthenticated" type="button" class="btn btn-success" v-on:click="addComment">
              <span class="fa fa-comment" aria-hidden="true"></span> {{ $t( 'IssueDetails.Add' ) }}
            </button>
            <button v-if="isAuthenticated" type="button" class="btn btn-default"><span class="fa fa-paperclip" aria-hidden="true"></span> <span class="hidden-xs auto-tooltip">{{ $t( 'IssueDetails.Attach' ) }}</span></button>
            <DropdownButton fa-class="fa-cog" menu-class="dropdown-menu-right" v-bind:title="$t( 'IssueDetails.Filter' )">
              <li v-for="item in allFilters" v-bind:class="{ active: filter == item }"><HyperLink v-on:click="setFilter( item )">{{ getFilterText( item ) }}</HyperLink></li>
            </DropdownButton>
          </div>
        </div>

        <div class="issue-history">
          <div v-for="item in processedHistory" v-bind:key="item.id" class="issue-history-item" v-bind:id="'item' + item.id">

            <div v-if="isCommentAdded( item ) || isFileAdded( item )" class="issue-group">
              <div class="issue-element issue-element-wide">
                <div class="issue-history-title">{{ item.createdDate }} &mdash; {{ item.createdBy }}</div>
              </div>
              <div class="issue-element">
                <a class="issue-history-id" v-bind:href="'#/item/' + item.id">#{{ item.id }}</a>
                <DropdownButton v-if="canReply( item ) || canEditItem( item )" fa-class="fa-ellipsis-v" menu-class="dropdown-menu-right" v-bind:title="$t( 'IssueDetails.Menu' )">
                  <li v-if="canReply( item )"><HyperLink v-on:click="replyComment( item.id )"><span class="fa fa-reply" aria-hidden="true"></span> {{ $t( 'IssueDetails.Reply' ) }}</HyperLink></li>
                  <li v-if="canEditItem( item )"><HyperLink v-on:click="editComment( item.id )"><span class="fa fa-pencil" aria-hidden="true"></span> {{ $t( 'IssueDetails.Edit' ) }}</HyperLink></li>
                  <li v-if="canEditItem( item )"><HyperLink v-on:click="deleteComment( item.id )"><span class="fa fa-trash" aria-hidden="true"></span> {{ $t( 'IssueDetails.Delete' ) }}</HyperLink></li>
                </DropdownButton>
              </div>
            </div>
            <div v-else class="issue-history-title">{{ item.createdDate }} &mdash; {{ item.createdBy }}</div>

            <div v-if="isCommentAdded( item )" class="issue-comment">
              <div class="formatted-text" v-hljs="item.text"></div>
              <div v-if="item.modifiedDate" class="issue-last-edited">
                <span class="fa fa-pencil" aria-hidden="true"></span> {{ item.modifiedDate }} &mdash; {{ item.modifiedBy }}
              </div>
            </div>
            <div v-else-if="isFileAdded( item )" class="issue-attachment">
              <span class="fa fa-paperclip" aria-hidden="true"></span> <HyperLink>{{ item.name }}</HyperLink> ({{ item.size }})
              <span v-if="item.description" v-html="'&mdash; ' + item.description"></span>
              <div v-if="item.modifiedDate" class="issue-last-edited">
                <span class="fa fa-pencil" aria-hidden="true"></span> {{ item.modifiedDate }} &mdash; {{ item.modifiedBy }}
              </div>
            </div>
            <ul v-else-if="isChangeList( item )" class="issue-history-list">
              <li v-for="change in item.changes" v-bind:key="change.id" v-html="formatChange( change )"></li>
            </ul>
            <ul v-else-if="isIssueMoved( item )" class="issue-history-list">
              <li v-html="formatIssueMoved( item )"></li>
            </ul>

          </div>
          <div v-if="processedHistory.length == 0" class="alert alert-info">
            {{ getNoItemsText( filter ) }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

import { Access, Change, History, KeyCode } from '@/constants'

export default {
  computed: {
    ...mapGetters( 'global', [ 'isAuthenticated' ] ),
    ...mapState( 'issue', [ 'issueId', 'filter', 'unread', 'details', 'description' ] ),
    ...mapGetters( 'issue', [ 'filteredAttributes', 'isItemInHistory', 'processedHistory' ] ),
    canMoveDelete() {
      return this.details.access == Access.AdministratorAccess;
    },
    canEditDescription() {
      return this.details.access == Access.AdministratorAccess || this.details.own;
    },
    allFilters() {
      return [
        History.AllHistory,
        History.Comments,
        History.Files,
        History.CommentsAndFiles
      ];
    }
  },

  methods: {
    canEditItem( item ) {
      return this.details.access == Access.AdministratorAccess || item.own;
    },
    canReply( item ) {
      return this.isAuthenticated && item.type == Change.CommentAdded;
    },

    isCommentAdded( item ) {
      return item.type == Change.CommentAdded;
    },
    isFileAdded( item ) {
      return item.type == Change.FileAdded;
    },
    isChangeList( item ) {
      return item.type == Change.IssueCreated || item.type == Change.IssueRenamed || item.type == Change.ValueChanged;
    },
    isIssueMoved( item ) {
      return item.type == Change.IssueMoved;
    },

    getFilterText( filter ) {
      switch( filter ) {
        case History.AllHistory:
          return this.$t( 'IssueDetails.History' );
        case History.Comments:
          return this.$t( 'IssueDetails.Comments' );
        case History.Files:
          return this.$t( 'IssueDetails.Files' );
        case History.CommentsAndFiles:
          return this.$t( 'IssueDetails.CommentsAndFiles' );
      }
    },
    getNoItemsText( filter ) {
      switch( filter ) {
        case History.Comments:
          return this.$t( 'IssueDetails.NoComments' );
        case History.Files:
          return this.$t( 'IssueDetails.NoFiles' );
        case History.CommentsAndFiles:
          return this.$t( 'IssueDetails.NoCommentsOrFiles' );
      }
    },

    formatChange( change ) {
      if ( change.type == Change.IssueCreated )
        return this.$t( 'IssueDetails.IssueCreated', [ this.formatValue( change.new ) ] );
      else if ( change.type == Change.IssueRenamed )
        return this.$t( 'IssueDetails.IssueRenamed', [ this.formatValue( change.old ), this.formatValue( change.new ) ] );
      else if ( change.type == Change.ValueChanged )
        return this.$t( 'IssueDetails.ValueChanged', [ escape( change.name ), this.formatValue( change.old ), this.formatValue( change.new ) ] );
    },
    formatValue( value ) {
      return value ? '&quot;' + value + '&quot;' : this.$t( 'IssueDetails.Empty' );
    },
    formatIssueMoved( item ) {
      return this.$t( 'IssueDetails.IssueMoved', [ this.formatLocation( item.fromProject, item.fromFolder ), this.formatLocation( item.toProject, item.toFolder ) ] );
    },
    formatLocation( project, folder ) {
      if ( project != null && folder != null )
        return '&quot;' + escape( project ) + '&quot; &mdash; &quot;' + escape( folder ) + '&quot;';
      else
        return this.$t( 'IssueDetails.UnknownFolder' );
    },

    editIssue() {
      this.$router.push( 'EditIssue', { issueId: this.issueId } );
    },
    cloneIssue() {
      this.$router.push( 'CloneIssue', { issueId: this.issueId } );
    },

    addDescription() {
      this.$router.push( 'AddDescription', { issueId: this.issueId } );
    },
    replyDescription() {
      this.$router.push( 'ReplyDescription', { issueId: this.issueId } );
    },
    editDescription() {
      this.$router.push( 'EditDescription', { issueId: this.issueId } );
    },
    deleteDescription() {
      this.$router.push( 'DeleteDescription', { issueId: this.issueId } );
    },

    addComment() {
      this.$router.push( 'AddComment', { issueId: this.issueId } );
    },
    replyComment( commentId ) {
      this.$router.push( 'ReplyComment', { issueId: this.issueId, commentId } );
    },
    editComment( commentId ) {
      this.$router.push( 'EditComment', { issueId: this.issueId, commentId } );
    },
    deleteComment( commentId ) {
      this.$router.push( 'DeleteComment', { issueId: this.issueId, commentId } );
    },

    setUnread( unread ) {
      this.$store.commit( 'issue/setUnread', unread );
      this.update();
    },
    setFilter( filter ) {
      this.$store.commit( 'issue/setFilter', filter );
      this.update();
    },
    update() {
      this.$emit( 'block' );
      this.$store.dispatch( 'issue/load' ).then( () => {
        this.$emit( 'unblock' );
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    close() {
      this.$emit( 'close' );
    },

    handleKeyDown( e ) {
      if ( e.keyCode == KeyCode.Esc )
        this.close();
    }
  },

  mounted() {
    const route = this.$router.route;
    if ( route != null && route.name == 'IssueItem' && route.params.issueId == this.issueId )
      this.$emit( 'scrollToAnchor', 'item' + route.params.itemId );

    document.addEventListener( 'keydown', this.handleKeyDown );
  },

  beforeDestroy() {
    document.removeEventListener( 'keydown', this.handleKeyDown );
  },

  routeChanged( route ) {
    if ( route != null && route.name == 'Item' && this.isItemInHistory( route.params.itemId ) ) {
      this.$router.replace( 'IssueItem', { issueId: this.issueId, itemId: route.params.itemId } );
      return true;
    } else if ( route != null && route.name == 'IssueItem' && route.params.issueId == this.issueId ) {
      this.$emit( 'scrollToAnchor', 'item' + route.params.itemId );
      return true;
    }
    return false;
  }
}

const Entities = {
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  '&': '&amp;'
};

function escape( text ) {
  return text.replace( /[<>"&]/g, ch => Entities[ ch ] || ch )
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.issue-group {
  .group();
  margin-top: 3px;
  margin-bottom: 3px;

  .issue-history-item & {
    margin-top: 8px;
  }
}

.issue-element {
  .element();
  vertical-align: bottom;
}

.issue-element-wide {
  .element-wide();
}

.issue-header {
  font-weight: bold;
  margin: 7px 0 6px 0;
  font-size: @issue-header-font-size;
}

.issue-details {
  margin: 10px 0 20px 0;
}

.issue-details-title {
  margin-top: 8px;
  text-transform: uppercase;
  font-size: @issue-details-title-font-size;
  color: @issue-details-title-color;
}

.issue-details-value {
  min-height: 20px;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.issue-history {
  margin-bottom: 20px;
}

.issue-history-title {
  margin: 15px 0 10px 0;
  font-weight: bold;

  .issue-group & {
    margin: 7px 0;
    white-space: normal;
  }
}

.issue-history-list {
  margin-bottom: 0;

  > li {
    word-wrap: break-word;
  }
}

.issue-history-id {
  color: @issue-history-id-color;
  padding-right: 5px;

  &:hover, &:focus {
    color: @issue-history-id-hover-color;
  }
}

.issue-description {
  padding: 10px;
  margin-bottom: 20px;
  border: 1px solid @issue-border-color;
  border-radius: @border-radius-base;
}

.issue-comment {
  padding: 10px;
  border: 1px solid @issue-border-color;
  border-radius: @border-radius-base;
}

.issue-attachment {
  padding: 10px;
  background-color: @issue-attachment-bg;
  border: 1px solid @issue-attachment-border;
  color: @issue-attachment-text;
  border-radius: @border-radius-base;
  word-wrap: break-word;
}

.issue-last-edited {
  color: @issue-last-edited-color;
  text-align: right;
  margin-top: 5px;

  .fa {
    margin-right: 3px;
  }

  .issue-attachment & {
    color: lighten( @issue-attachment-text, 10% );
  }
}
</style>
