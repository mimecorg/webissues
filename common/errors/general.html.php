<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'An unexpected error occured while processing the request.' ) ?></p>
</div>

<?php if ( !empty( $errorMessage ) ): ?>
<p class="front-error"><?php echo $this->tr( 'Reason: %1.', null, $errorMessage ) ?></p>
<?php endif ?>
