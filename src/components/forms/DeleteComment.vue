<template>
  <div class="container-fluid">
    <FormHeader v-bind:title="$t( 'DeleteComment.DeleteComment' )" v-on:close="close"/>
    <Prompt path="DeleteComment.DeleteCommentPrompt"><strong>{{ '#' + commentId }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    mode: String,
    issueId: Number,
    commentId: Number
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/comment/delete.php', { commentId: this.commentId } ).then( () => {
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
  }
}
</script>
