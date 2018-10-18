<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p class="header"><?php echo $this->t( 'prompt.Hello', array( $userName ) ) ?></p>

<?php $this->insertContent() ?>

<p class="footer"><?php echo $this->t( 'prompt.NotificationEmail' ) ?></p>
