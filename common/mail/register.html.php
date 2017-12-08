<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'Thank you for registering on the WebIssues Server.' ) ?></p>

<p><?php echo $this->tr( 'To activate your registration request, please visit the following URL:' ) ?></p>

<p><?php echo $this->link( $activationUrl, $activationUrl ) ?></p>

<ul>
<li><?php echo $this->tr( 'User name: %1', null, $userName ) ?></li>
<li><?php echo $this->tr( 'Login: %1', null, $login ) ?></li>
<li><?php echo $this->tr( 'Email address: %1', null, $email ) ?></li>
</ul>

<p><?php echo $this->tr( 'If you didn\'t register, please ignore this email.' ) ?></p>
