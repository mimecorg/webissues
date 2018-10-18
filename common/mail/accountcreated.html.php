<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->t( 'prompt.AccountCreated', array( '<strong>' . $userLogin . '</strong>' ) ) ?></p>

<p><?php echo $this->link( $invitationUrl, $invitationUrl ) ?></p>
