<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'An unexpected error occured while processing the request.' ) ?></p>

<?php if ( !empty( $errorMessage ) ): ?>
<p><?php echo $this->tr( 'Reason: %1.', null, $errorMessage ) ?></p>
<?php endif ?>
