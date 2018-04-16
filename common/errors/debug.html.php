<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'The following error(s) occured while processing the request:' ) ?></p>
</div>

<ul class="front-error">
<?php foreach ( $errors as $error ): ?>
<li><?php echo nl2br( $error ) ?></li>
<?php endforeach ?>
</ul>
