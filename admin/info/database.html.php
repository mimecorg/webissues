<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Database Configuration' ) ?></legend>

<table class="info-list info-align">
<tr>
<td><?php echo $this->tr( 'Database server:' ) ?></td>
<td><?php echo $dbServer . ' ' . $dbVersion ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Host name:' ) ?></td>
<td><?php echo $dbHost ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Database name:' ) ?></td>
<td><?php echo $dbDatabase ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Table prefix:' ) ?></td>
<td><?php echo $dbPrefix ?></td>
</tr>
</table>

</fieldset>
