<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-danger">
<p><?php echo $this->t( 'error.UnexpectedError' ) ?></p>
</div>

<div class="alert alert-default">
<?php foreach ( $errors as $error ): ?>
<p><?php echo nl2br( $error ) ?></p>
<?php endforeach ?>
</div>
