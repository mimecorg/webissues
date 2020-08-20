<template>
  <DropdownScrollButton ref="dropdown" v-bind="$attrs" v-on:open="open" v-on:focus-filter="focusFilter">
    <template v-slot:filter>
      <div class="dropdown-menu-filter">
        <input ref="filter" type="text" class="form-control" v-bind:value="filter" v-on:input="setFilter( $event.target.value )">
      </div>
    </template>
    <slot/>
  </DropdownScrollButton>
</template>

<script>
export default {
  props: {
    filter: String,
    preserveOnOpen: { type: Boolean, default: false },
  },
  methods: {
    focus() {
      this.$refs.dropdown.focus();
    },
    expand() {
      this.$refs.dropdown.expand();
    },

    setFilter( text ) {
      this.$emit( 'update:filter', text );
      this.$nextTick( () => {
        this.$refs.dropdown.toggleShadow();
      } );
    },

    open() {
      if ( !this.preserveOnOpen )
        this.$emit( 'update:filter', '' );
      this.$nextTick( () => {
        this.$refs.filter.focus();
      } );
      this.$emit( 'open' );
    },

    focusFilter() {
      this.$refs.filter.focus();
    }
  }
}
</script>
