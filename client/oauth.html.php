<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-default">
<?php if ( $isSuccessful ): ?>
  <p><?php echo $this->t( 'prompt.AuthenticationSucceeded' ) ?></p>
  <script>
    if ( window.opener != null )
      window.opener.postMessage( { authentication: 'refresh' } );
  </script>
<?php else: ?>
  <p><?php echo $this->t( 'prompt.AuthenticationFailed' ) ?></p>
<?php endif ?>
</div>
