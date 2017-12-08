<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'Your registration request on the WebIssues Server was approved by the administrator.' ) ?></p>

<p><?php echo $this->tr( 'You can now %1 using your login and password.', null, $this->mailLink( '/index.php', $this->tr( 'log in to the server' ), array(), true ) ) ?></p>

<ul>
<li><?php echo $this->tr( 'User name: %1', null, $userName ) ?></li>
<li><?php echo $this->tr( 'Login: %1', null, $login ) ?></li>
<li><?php echo $this->tr( 'Email address: %1', null, $email ) ?></li>
</ul>
