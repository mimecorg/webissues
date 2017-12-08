<template>
  <div ref="overlay" tabindex="-1" class="busy-overlay">
    <div class="busy-spinner">
      <span class="fa fa-spinner fa-spin" aria-hidden="true"></span>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    handleFocusIn( e ) {
      if ( e.target != document && e.target != this.$refs.overlay )
        this.$refs.overlay.focus();
    }
  },
  mounted() {
    this.$refs.overlay.focus();
    document.addEventListener( 'focusin', this.handleFocusIn );
  },
  beforeDestroy() {
    document.removeEventListener( 'focusin', this.handleFocusIn );
  }
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

.busy-overlay {
  position: absolute;
  left: 0; right: 0;
  top: 0; bottom: 0;
  background-color: rgba( 255, 255, 255, 0.5 );
  z-index: 20;
  outline: 0;
  cursor: wait;

  #window > & {
    border-radius: @border-radius-large;
  }
}

.busy-spinner {
  position: absolute;
  left: 50%; top: 50%;
  margin: -21px 0 0 -15px;
  font-size: 30px;
}
</style>
