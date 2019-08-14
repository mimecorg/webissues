<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->t( 'prompt.PendingRegistrationRequests' ) ?></p>

<table class="grid">
<tr>
<th><?php echo $this->t( 'title.Name' ) ?></th>
<th><?php echo $this->t( 'title.Login' ) ?></th>
<th><?php echo $this->t( 'title.Email' ) ?></th>
<th><?php echo $this->t( 'title.Date' ) ?></th>
</tr>
<?php foreach ( $requests as $requestId => $request ): ?>
<tr>
<td><?php echo $request[ 'user_name' ] ?></td>
<td><?php echo $request[ 'user_login' ] ?></td>
<td><?php echo $request[ 'user_email' ] ?></td>
<td><?php echo $request[ 'date' ] ?></td>
</tr>
<?php endforeach ?>
</table>

<p class="footer"><?php echo $this->t( 'prompt.NotificationEmail' ) ?></p>
