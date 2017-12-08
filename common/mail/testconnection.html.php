<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'This email was sent by the WebIssues Server to test connection to the SMTP server.' ) ?></p>

<ul>
<li><?php echo $this->tr( 'Server name: %1', null, $smtpServer ) ?></li>
<li><?php echo $this->tr( 'Port: %1', null, $smtpPort ) ?></li>
</ul>
