<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'You have the following new %1 on the WebIssues Server pending for approval:', null, $this->mailLink( '/admin/register/index.php', $this->tr( 'registration requests' ) ) ) ?></p>

<table class="grid">
<tr>
<th><?php echo $this->tr( 'Name' ) ?></th>
<th><?php echo $this->tr( 'Login' ) ?></th>
<th><?php echo $this->tr( 'Email' ) ?></th>
<th><?php echo $this->tr( 'Date' ) ?></th>
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
