<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'The following error(s) occured while processing the request:' ) ?></p>
<ul>
<?php foreach ( $errors as $error ): ?>
<li><?php echo nl2br( $error ) ?></li>
<?php endforeach ?>
</ul>
