<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php if ( !empty( $userName ) ): ?>
<p class="header"><?php echo $this->t( 'prompt.HelloName', array( $userName ) ) ?></p>
<?php else: ?>
<p class="header"><?php echo $this->t( 'prompt.Hello' ) ?></p>
<?php endif ?>

<?php $this->insertContent() ?>

<p class="footer"><?php echo $this->t( 'prompt.NotificationEmail' ) ?></p>
