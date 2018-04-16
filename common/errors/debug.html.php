<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'The following error(s) occured while processing the request:' ) ?></p>
</div>

<div class="alert alert-default">
<?php foreach ( $errors as $error ): ?>
<p><?php echo nl2br( $error ) ?></p>
<?php endforeach ?>
</div>
