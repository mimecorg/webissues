<!--
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
-->

<template>
  <BaseForm v-bind:title="$t( 'title.AboutWebIssues' )" auto-close with-buttons cancel-hidden v-on:ok="close">
    <template v-slot:header>
      <a v-if="isWeb" type="button" class="btn btn-default" v-bind:href="guideURL" target="_blank">
        <span class="fa fa-question-circle" aria-hidden="true"></span> <span class="hidden-xs">{{ $t( 'title.WebIssuesGuide' ) }}</span>
      </a>
      <button v-else type="button" class="btn btn-default" v-on:click="openGuide">
        <span class="fa fa-question-circle" aria-hidden="true"></span> <span class="hidden-xs">{{ $t( 'title.WebIssuesGuide' ) }}</span>
      </button>
    </template>
    <p><strong>{{ $t( 'label.Version' ) }} {{ version }}</strong></p>
    <p v-if="!isWeb && serverVersion != null">{{ $t( 'label.ServerVersion' ) }} {{ serverVersion }}</p>
    <p v-if="showLatestVersion">
      {{ $t( 'label.LatestVersion' ) }} {{ latestVersion }}
      &middot; <a v-bind:href="downloadUrl" target="_blank">{{ $t( 'title.Download' ) }}</a>
      &middot; <a v-bind:href="notesUrl" target="_blank">{{ $t( 'title.ReleaseNotes' ) }}</a>
    </p>
    <hr>
    <p class="about-link"><span class="fa fa-info-circle" aria-hidden="true"></span> <a href="https://webissues.mimec.org" target="_blank">webissues.mimec.org</a></p>
    <p class="about-link"><span class="fa fa-github" aria-hidden="true"></span> <a href="https://github.com/mimecorg/webissues" target="_blank">github.com/mimecorg/webissues</a></p>
    <hr>
    <p>Copyright &copy; 2006 Michał Męciński<br>Copyright &copy; 2007-2020 WebIssues Team</p>
    <p>{{ $t( 'text.License' ) }}</p>
  </BaseForm>
</template>

<script>
import compareVersions from 'compare-versions'

import { GuideURL, Access } from '@/constants'

export default {
  props: {
    serverVersion: String,
    latestVersion: String,
    notesUrl: String,
    downloadUrl: String
  },
  computed: {
    guideURL() {
      return GuideURL;
    },
    isWeb() {
      return process.env.TARGET == 'web';
    },
    version() {
      if ( process.env.TARGET == 'web' )
        return this.serverVersion;
      else
        return this.$client.version;
    },
    showLatestVersion() {
      if ( this.latestVersion != null ) {
        if ( this.$store.state.global.userAccess == Access.AdministratorAccess && compareVersions( this.serverVersion, this.latestVersion ) < 0 )
          return true;
        if ( process.env.TARGET == 'electron' && compareVersions( this.$client.version, this.latestVersion ) < 0 )
          return true;
      }
      return false;
    }
  },
  methods: {
    close() {
      this.$form.close();
    },
    openGuide() {
      if ( process.env.TARGET == 'electron' )
        this.$client.openURL( GuideURL );
    }
  }
}
</script>
