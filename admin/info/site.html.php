<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Site Configuration' ) ?></legend>

<table class="info-list info-align">
<tr>
<td><?php echo $this->tr( 'Site name:' ) ?></td>
<td><?php echo $siteName ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Base URL address:' ) ?></td>
<td><?php echo WI_BASE_URL . '/' ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Site directory:' ) ?></td>
<td><?php echo $siteDirectory ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Debugging log file:' ) ?></td>
<td><?php echo $debugLevel > 0 ? $debugFile : $this->tr( 'disabled', 'log file' ) ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Debugging information:' ) ?></td>
<td><?php echo $debugInfo ? $this->tr( 'enabled', 'debug info' ) : $this->tr( 'disabled', 'debug info' ) ?></td>
</tr>
</table>

<?php $form->renderError( 'site' ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Environment Information' ) ?></legend>

<table class="info-list info-align">
<tr>
<td><?php echo $this->tr( 'PHP version:' ) ?></td>
<td><?php echo $phpVersion ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Web server:' ) ?></td>
<td><?php echo $webServer ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Operating system:' ) ?></td>
<td><?php echo $osVersion ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Host name:' ) ?></td>
<td><?php echo $hostName ?></td>
</tr>
</table>

</fieldset>
