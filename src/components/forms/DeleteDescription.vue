<template>
  <div class="container-fluid">
    <FormHeader v-bind:title="$t( 'DeleteDescription.DeleteDescription' )" v-on:close="close"/>
    <Prompt path="DeleteDescription.DeleteDescriptionPrompt"><strong>{{ name }}</strong></Prompt>
    <FormButtons v-on:ok="submit" v-on:cancel="cancel"/>
  </div>
</template>

<script>
export default {
  props: {
    mode: String,
    issueId: Number,
    name: String
  },

  methods: {
    submit() {
      this.$emit( 'block' );

      this.$ajax.post( '/server/api/issue/description/delete.php', { issueId: this.issueId } ).then( () => {
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
