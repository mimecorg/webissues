<template>
  <div class="container-fluid">
    <FormHeader v-bind:title="title" v-on:close="close"/>
    <Prompt v-if="mode == 'edit'" path="EditComment.EditCommentPrompt"><strong>{{ '#' + commentId }}</strong></Prompt>
    <Prompt v-else-if="mode == 'add'" path="EditComment.AddCommentPrompt"><strong>{{ name }}</strong></Prompt>
    <MarkupEditor ref="comment" id="comment" v-bind:label="$t( 'EditComment.Comment' )" v-bind:required="true" v-bind:error="commentError"
                  v-bind:format="selectedFormat" v-model="commentValue" v-on:select-format="selectFormat" v-on:error="error"/>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
  props: {
    mode: String,
    issueId: Number,
    commentId: Number,
    name: String,
    comment: String,
    commentFormat: Number
  },

  data() {
    return {
      commentValue: this.comment,
      selectedFormat: this.commentFormat,
      commentError: null
    };
  },

  computed: {
    ...mapState( 'global', [ 'settings' ] ),
    title() {
      if ( this.mode == 'edit' )
        return this.$t( 'EditComment.EditComment' );
      else if ( this.mode == 'add' )
        return this.$t( 'EditComment.AddComment' );
    }
  },

  methods: {
    selectFormat( format ) {
      this.selectedFormat = format;
    },

    submit() {
      this.commentError = null;

      const data = {};
      if ( this.mode == 'add' )
        data.issueId = this.issueId;
      else
        data.commentId = this.commentId;
      let modified = false;
      let valid = true;

      try {
        this.commentValue = this.$parser.normalizeString( this.commentValue, this.settings.commentMaxLength, { allowEmpty: false, multiLine: true } );
        if ( this.mode == 'add' || this.commentValue != this.comment ) {
          modified = true;
          data.comment = this.commentValue;
          data.commentFormat = this.selectedFormat;
        }
      } catch ( error ) {
        if ( error.reason == 'APIError' ) {
          this.commentError = this.$t( 'ErrorCode.' + error.errorCode );
          if ( valid )
            this.$refs.comment.focus();
          valid = false;
        } else {
          throw error;
        }
      }

      if ( !valid )
        return;

      if ( this.mode == 'edit' && !modified ) {
        this.returnToDetails();
        return;
      }

      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/comment/' + this.mode + '.php', data ).then( ( { stampId } ) => {
        if ( stampId != false )
          this.$store.commit( 'list/setDirty' );
        this.returnToDetails();
      } ).catch( error => {
        this.$emit( 'error', error );
      } );
    },

    cancel() {
      this.returnToDetails();
    },

    returnToDetails() {
      this.$router.push( 'IssueDetails', { issueId: this.issueId } );
    },

    close() {
      this.$emit( 'close' );
    },
    error( error ) {
      this.$emit( 'error', error );
    }
  },

  mounted() {
    this.$refs.comment.focus();
  }
}
</script>
