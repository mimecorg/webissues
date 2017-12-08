<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Server Information' ) ?></legend>

<?php if ( !empty( $toolBar ) ): ?>
<div style="float: right">
<?php $toolBar->render() ?>
</div>
<?php endif ?>

<table class="info-list info-align">
<tr>
<td><?php echo $this->tr( 'Database version:' ) ?></td>
<td><?php echo $server[ 'db_version' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Server name:' ) ?></td>
<td><?php echo $server[ 'server_name' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Unique ID:' ) ?></td>
<td><?php echo $server[ 'server_uuid' ] ?></td>
</tr>
</table>

</fieldset>
