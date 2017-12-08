<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'config_exists': ?>

<p class="error"><?php echo $this->tr( 'The configuration file of this WebIssues Server already exists.' ) ?></p>

<p><?php echo $this->tr( 'For security reasons, you must first delete the server configuration file and then run the %1 page again.', null,
    $this->link( WI_SCRIPT_URL, $this->tr( 'Server Configuration' ) ) ) ?></p>

<?php break;
case 'completed': ?>

<p><?php echo $this->tr( 'Installation of your WebIssues Server was successfully completed.' ) ?></p>

<p><?php echo $this->tr( 'Go to the %1 to continue the configuration of this server.', null,
    $this->link( '/admin/index.php', $this->tr( 'Administration Panel' ) ) ) ?></p>

<?php if ( !empty( $hasFileSystemFiles ) ): ?>
<p><?php echo $this->tr( 'Do not forget to copy attachments stored in the file system from the old version of the server.' ) ?></p>
<?php endif ?>

<?php break;
case 'failed': ?>

<p class="error"><?php echo $this->tr( 'Installation failed with the following fatal error:' ) ?></p>
<p><?php echo nl2br( $error ) ?></p>

<?php break;
default: ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'language': ?>

<p><?php echo $this->tr( 'Select language used during the installation.' ) ?></p>

<?php $form->renderSelect( $this->tr( 'Language:' ), 'language', $languageOptions, array( 'style' => 'width: 20em;' ) ) ?>

<?php break;
case 'site': ?>

<p><?php echo $this->tr( 'This wizard will help you configure the WebIssues Server.' ) ?></p>

<?php echo $site ?>

<?php break;
case 'connection': ?>

<p><?php echo $this->tr( 'Please enter information required to connect to the database.' ) ?></p>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Database Engine' ) ?></legend>

<p><?php echo $this->tr( 'Select the type of the database to use:' ) ?></p>

<?php $form->renderRadioGroup( 'engine', $engineOptions ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Connection Details' ) ?></legend>

<?php $form->renderText( $this->tr( 'Host name:' ), 'host', array( 'size' => 40 ) ) ?>
<?php $form->renderText( $this->tr( 'Database name:' ), 'database', array( 'size' => 40 ) ) ?>
<?php $form->renderText( $this->tr( 'User name:' ), 'user', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>

<?php $form->renderError( 'connection' ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Mode' ) ?></legend>

<p><?php echo $this->tr( 'Select if you want to install a new server or recreate the configuration file for an existing server:' ) ?></p>

<?php $form->renderRadioGroup( 'mode', $modeOptions ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Table Prefix' ) ?></legend>

<p><?php echo $this->tr( 'You can enter an optional prefix for table names. This allows installing multiple servers using the same database.' ) ?></p>

<?php $form->renderText( $this->tr( 'Table prefix:' ), 'prefix', array( 'size' => 40 ) ) ?>

</fieldset>

<?php break;
case 'server': ?>

<p><?php echo $this->tr( 'Please enter the parameters of the new server.' ) ?></p>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Server Information' ) ?></legend>

<p><?php echo $this->tr( 'Enter the name of this server.' ) ?></p>

<?php $form->renderText( $this->tr( 'Server name:' ), 'serverName', array( 'size' => 40 ) ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Administrator Account' ) ?></legend>

<p><?php echo $this->tr( 'The Administrator user account will be created allowing you to log in to the server and change its settings.' ) ?></p>

<?php $form->renderText( $this->tr( 'Login:' ), 'adminLogin', array( 'value' => 'admin', 'size' => 40, 'disabled' => true ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'adminPassword', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Confirm password:' ), 'adminConfirm', array( 'size' => 40 ) ) ?>

</fieldset>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Initial Configuration' ) ?></legend>

<p><?php echo $this->tr( 'Select the initial configuration of this server:' ) ?></p>

<?php $form->renderRadioGroup( 'initialData', $dataOptions ) ?>

</fieldset>

<?php break;
case 'new_site': ?>

<p><?php echo $this->tr( 'The new server will be installed in the selected database.' ) ?></p>

<fieldset class="form-fieldset">
<legend><?php echo $this->tr( 'Server Information' ) ?></legend>

<table class="info-list info-align">
<tr>
<td><?php echo $this->tr( 'Server name:' ) ?></td>
<td><?php echo $serverName ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Initial configuration:' ) ?></td>
<td><?php echo $initialData == 'default' ? $this->tr( 'Default issue types' ) : $this->tr( 'No issue types' ) ?></td>
</tr>
</table>

</fieldset>

<?php $this->insertComponent( 'Admin_Info_Database', array( $host, $database, $prefix ) ) ?>

<?php break;
case 'existing_site': ?>

<?php if ( $update ): ?>
<p><?php echo $this->tr( 'The database of this WebIssues Server will be updated to version %1.', null, WI_DATABASE_VERSION ) ?></p>
<?php else: ?>
<p><?php echo $this->tr( 'The server is already configured. It will not be modified during the installation.' ) ?></p>
<?php endif ?>

<?php $this->insertComponent( 'Admin_Info_Server' ) ?>

<?php $this->insertComponent( 'Admin_Info_Database', array( $host, $database, $prefix ) ) ?>

<?php break;
endswitch ?>

<div class="form-submit">
<?php
    if ( $showRefresh ):
        $form->renderSubmit( $this->tr( 'Refresh' ), 'refresh' );
    endif;
    if ( $showBack ):
        $form->renderSubmit( $this->tr( '&lt; Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->tr( 'Next &gt;' ), 'next', array( 'disabled' => !empty( $disableNext ) ) );
    endif;
    if ( $showInstall ):
        $form->renderSubmit( $this->tr( 'Install' ), 'install', array( 'disabled' => !empty( $disableInstall ) ) );
    endif;
?>
</div>

<?php $form->renderFormClose() ?>

<?php if ( $showInstall ): ?>
<div id="progress" style="display: none">
    <?php echo $this->image( '/common/images/throbber.gif', '', array( 'width' => 32, 'height' => 32 ) ) . $this->tr( 'Installation in progress...' ) ?>
</div>
<?php endif ?>

<?php endswitch ?>
