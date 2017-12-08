<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $infoMessage ?></p>
<?php if ( !empty( $linkMessage ) ): ?>
<p><?php echo $this->getRawValue( 'linkMessage' ) ?></p>
<?php endif ?>
