<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'config_exists': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'This WebIssues Server is already configured.' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->tr( 'For security reasons you must delete the server configuration file before the installation.' ) ?></p>
</div>

<?php break;
case 'completed': ?>

<div class="alert alert-success">
<p><?php echo $this->tr( 'Installation of your WebIssues Server was successfully completed.' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->tr( 'Go to the %1 to continue the configuration of this server.', null,
    $this->link( '/client/index.php', $this->tr( 'Web Client' ) ) ) ?></p>
</div>

<?php break;
case 'failed': ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'Installation failed with the following fatal error:' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo nl2br( $error ) ?></p>
</div>

<?php break;
default: ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'language': ?>

<?php $form->renderSelect( $this->tr( 'Language:' ), 'language', $languageOptions ) ?>

<?php break;
case 'site_error': ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'The following configuration error was detected:' ) ?></p>
<p><?php echo $error ?></p>
</div>

<?php break;
case 'site': ?>

<p><?php echo $this->tr( 'This wizard will help you configure the WebIssues Server.' ) ?></p>

<?php echo $site ?>

<?php break;
case 'connection': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'Please enter information required to connect to the database.' ) ?></p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Installation Mode' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->tr( 'You can create database tables for a new server or recreate the configuration file for a previously configured server.' ) ?></p>

<?php $form->renderRadioGroup( $this->tr( 'Installation mode:' ), 'mode', $modeOptions ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Connection Details' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderErrorMessage( 'connection' ) ?>

<?php $form->renderRadioGroup( $this->tr( 'Database type:' ), 'engine', $engineOptions ) ?>

<?php $form->renderText( $this->tr( 'Host name:' ), 'host' ) ?>
<?php $form->renderText( $this->tr( 'Database name:' ), 'database' ) ?>
<?php $form->renderText( $this->tr( 'User name:' ), 'user' ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password' ) ?>

<p><?php echo $this->tr( 'You can enter an optional prefix for table names. This allows installing multiple servers using the same database.' ) ?></p>

<?php $form->renderText( $this->tr( 'Table prefix:' ), 'prefix' ) ?>

</div>
</div>

<?php break;
case 'server': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'Please enter the parameters of the new server.' ) ?></p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Server Information' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderText( $this->tr( 'Server name:' ), 'serverName' ) ?>

<?php $form->renderRadioGroup( $this->tr( 'Initial configuration:' ), 'initialData', $dataOptions ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Administrator Account' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderText( $this->tr( 'Login:' ), 'adminLogin', array( 'value' => 'admin', 'readonly' => true ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'adminPassword' ) ?>
<?php $form->renderPassword( $this->tr( 'Confirm password:' ), 'adminConfirm' ) ?>

</div>
</div>

<?php break;
case 'new_site':
case 'existing_site': ?>

<div class="alert alert-info">
<?php if ( $page == 'new_site' ): ?>
<p><?php echo $this->tr( 'The new server will be installed in the selected database.' ) ?></p>
<?php elseif ( $update ): ?>
<p><?php echo $this->tr( 'The database of this WebIssues Server will be updated to version %1.', null, WI_DATABASE_VERSION ) ?></p>
<?php else: ?>
<p><?php echo $this->tr( 'The server is already configured. It will not be modified during the installation.' ) ?></p>
<?php endif ?>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Server Information' ) ?></h2>
</div>
<div class="panel-body panel-table">

<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->tr( 'Server name:' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $serverName ?></div>
</div>
<?php if ( $page == 'new_site' ): ?>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->tr( 'Initial configuration:' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $initialData == 'default' ? $this->tr( 'Default issue types' ) : $this->tr( 'No issue types' ) ?></div>
</div>
<?php endif ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Database Configuration' ) ?></h2>
</div>
<div class="panel-body panel-table">

<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->tr( 'Host name:' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $host ?></div>
</div>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->tr( 'Database name:' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $database ?></div>
</div>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->tr( 'Table prefix:' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $prefix ?></div>
</div>

</div>
</div>

<?php break;
endswitch ?>

<div class="form-buttons">
<?php
    if ( $showRefresh ):
        $form->renderSubmit( $this->tr( 'Refresh' ), 'refresh', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showBack ):
        $form->renderSubmit( '<span class="fa fa-caret-left" aria-hidden="true"></span> ' . $this->tr( 'Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->tr( 'Next' ) . ' <span class="fa fa-caret-right" aria-hidden="true"></span>', 'next', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showInstall ):
        $form->renderSubmit( $this->tr( 'Install' ), 'install', array( 'class' => 'btn btn-primary' ) );
    endif;
?>
</div>

<?php $form->renderFormClose() ?>

<?php if ( $showInstall ): ?>
<div tabindex="-1" class="busy-overlay" style="display: none">
  <div class="busy-spinner">
    <span class="fa fa-spinner fa-spin" aria-hidden="true"></span>
  </div>
</div>
<?php endif ?>

<?php endswitch ?>
