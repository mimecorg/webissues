<template>
  <div ref="overlay" id="overlay" tabindex="-1" v-bind:class="{ 'overlay-busy': busy }" v-on:click.self="close">
    <div id="overlay-window" v-bind:class="'overlay-' + size">
      <component v-if="childComponent != null" v-bind:is="childComponent" v-bind="childProps" v-on:close="close" v-on:block="block" v-on:unblock="unblock"></component>
      <busy-overlay v-if="busy"></busy-overlay>
    </div>
  </div>
</template>

<script>
import UnexpectedError from '@/components/forms/UnexpectedError'

export default {
  props: {
    route: Object
  },
  data() {
    return {
      childComponent: null,
      childProps: null,
      size: 'small',
      busy: true,
      cancellation: null
    };
  },
  watch: {
    route( value ) {
      this.cancelRoute();
      if ( value != null )
        this.handleRoute( value );
    }
  },
  methods: {
    handleRoute( route ) {
      if ( route.name != 'error' ) {
        let cancelled = false;
        this.cancellation = () => {
          cancelled = true;
        };
        route.handler( route.params ).then( ( { component, size = 'normal', ...props } ) => {
          if ( !cancelled ) {
            this.childComponent = component;
            this.childProps = props;
            this.size = size;
            this.busy = false;
            this.cancellation = null;
          }
        } ).catch( error => {
          if ( !cancelled ) {
            this.$emit( 'error', error.message );
            this.cancellation = null;
          }
        } );
      } else {
        this.childComponent = UnexpectedError;
        this.childProps = { error: route.message };
        this.size = 'small';
        this.busy = false;
      }
    },
    cancelRoute() {
      if ( this.cancellation != null ) {
        this.cancellation();
        this.cancellation = null;
      } else {
        this.childComponent = null;
        this.childProps = null;
        this.size = 'small';
        this.busy = true;
      }
    },
    close() {
      this.$emit( 'close' );
    },
    block() {
      this.busy = true;
    },
    unblock() {
      this.busy = false;
    },
    handleFocusIn( e ) {
      if ( e.target != document && e.target != this.$refs.overlay && !isChildElement( e.target, this.$refs.overlay ) )
        this.$refs.overlay.focus();
    }
  },
  mounted() {
    this.$refs.overlay.focus();
    document.addEventListener( 'focusin', this.handleFocusIn );
    if ( this.route != null )
      this.handleRoute( this.route );
  },
  beforeDestroy() {
    document.removeEventListener( 'focusin', this.handleFocusIn );
    this.cancelRoute();
  }
}

function isChildElement( element, parent ) {
  while ( element != null ) {
    if ( element.parentElement == parent )
      return true;
    element = element.parentElement;
  }
  return false;
}
</script>

<style lang="less">
@import "~@/styles/variables.less";
@import "~@/styles/mixins.less";

#overlay {
  position: absolute;
  left: 0; right: 0;
  top: 0; bottom: 0;
  background-color: rgba( 0, 0, 0, 0.5 );
  .touch-scroll();
  z-index: 10;
  outline: 0;

  &.overlay-busy {
    cursor: wait;
  }
}

#overlay-window {
  position: relative;
  width: auto;
  min-height: 66px;
  margin: 60px 10px 10px 10px;
  background-color: @window-bg;
  border: 1px solid @window-border-color;
  border-radius: @border-radius-large;
  .box-shadow( 0 3px 9px rgba( 0, 0, 0, 0.5 ) );

  @media ( min-width: @screen-sm-min ) {
    width: @screen-sm-min - 60px;
    margin: 60px auto 30px auto;
    .box-shadow( 0 5px 15px rgba( 0, 0, 0, 0.5 ) );

    &.overlay-small {
      width: 400px;
    }
  }

  @media ( min-width: @screen-md-min ) {
    &.overlay-large {
      width: @screen-md-min - 60px;
    }
  }

  @media ( min-width: @screen-lg-min ) {
    &.overlay-large {
      width: @screen-lg-min - 60px;
    }
  }
}
</style>
