<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-danger">
<?php if ( !empty( $errorMessage ) ): ?>
<p><?php echo $errorMessage ?></p>
<?php else: ?>
<p><?php echo $this->t( 'error.UnexpectedError' ) ?></p>
<?php endif ?>
</div>
