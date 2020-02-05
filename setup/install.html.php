<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'config_exists': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ServerAlreadyConfigured' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->t( 'prompt.DeleteConfiguratioFile' ) ?></p>
</div>

<?php break;
case 'completed': ?>

<div class="alert alert-success">
<p><?php echo $this->t( 'prompt.InstallationCompleted' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->t( 'prompt.ContinueConfiguration', array( $this->link( '/client/index.php', $this->t( 'title.WebClient' ) ) ) ) ?></p>
</div>

<?php break;
case 'failed': ?>

<div class="alert alert-danger">
<p><?php echo $this->t( 'error.InstallationFailed' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo nl2br( $error ) ?></p>
</div>

<?php break;
default: ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'language': ?>

<?php $form->renderSelect( $this->t( 'label.Language' ), 'language', $languageOptions ) ?>

<?php break;
case 'site_error': ?>

<div class="alert alert-danger">
<p><?php echo $this->t( 'error.ConfigurationError' ) ?></p>
<p><?php echo $error ?></p>
</div>

<?php break;
case 'connection': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.DatabaseConfiguration' ) ?></p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.InstallationMode' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->t( 'prompt.InstallationMode' ) ?></p>

<?php $form->renderRadioGroup( $this->t( 'label.InstallationMode' ), 'mode', $modeOptions ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.ConnectionDetails' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderErrorMessage( 'connection' ) ?>

<?php $form->renderRadioGroup( $this->t( 'label.DatabaseType' ), 'engine', $engineOptions ) ?>

<?php $form->renderText( $this->t( 'label.HostName' ), 'host' ) ?>
<?php $form->renderText( $this->t( 'label.DatabaseName' ), 'database' ) ?>
<?php $form->renderText( $this->t( 'label.UserName' ), 'user' ) ?>
<?php $form->renderPassword( $this->t( 'label.Password' ), 'password' ) ?>

<p><?php echo $this->t( 'prompt.TablePrefix' ) ?></p>

<?php $form->renderText( $this->t( 'label.TablePrefix' ), 'prefix' ) ?>

</div>
</div>

<?php break;
case 'server': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ServerConfiguration' ) ?></p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.ServerInformation' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderText( $this->t( 'label.ServerName' ), 'serverName' ) ?>

<?php $form->renderRadioGroup( $this->t( 'label.InitialConfiguration' ), 'initialData', $dataOptions ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.AdministratorAccount' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderText( $this->t( 'label.Login' ), 'adminLogin', array( 'value' => 'admin', 'readonly' => true ) ) ?>
<?php $form->renderPassword( $this->t( 'label.Password' ), 'adminPassword' ) ?>
<?php $form->renderPassword( $this->t( 'label.ConfirmPassword' ), 'adminConfirm' ) ?>
<?php $form->renderText( $this->t( 'label.EmailAddress' ), 'adminEmail' ) ?>

</div>
</div>

<?php break;
case 'new_site':
case 'existing_site': ?>

<div class="alert alert-info">
<?php if ( $page == 'new_site' ): ?>
<p><?php echo $this->t( 'prompt.InstallNewServer' ) ?></p>
<?php elseif ( $update ): ?>
<p><?php echo $this->t( 'prompt.UpdateDatabase', array( WI_DATABASE_VERSION ) ) ?></p>
<?php else: ?>
<p><?php echo $this->t( 'prompt.InstallExistingServer' ) ?></p>
<?php endif ?>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.ServerInformation' ) ?></h2>
</div>
<div class="panel-body panel-table">

<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->t( 'label.ServerName' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $serverName ?></div>
</div>
<?php if ( $page == 'new_site' ): ?>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->t( 'label.InitialConfiguration' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $initialData == 'default' ? $this->t( 'text.DefaultIssueTypes' ) : $this->t( 'text.NoIssueTypes' ) ?></div>
</div>
<?php endif ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.DatabaseConfiguration' ) ?></h2>
</div>
<div class="panel-body panel-table">

<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->t( 'label.HostName' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $host ?></div>
</div>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->t( 'label.DatabaseName' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $database ?></div>
</div>
<div class="row">
  <div class="col-sm-4"><strong><?php echo $this->t( 'label.TablePrefix' ) ?></strong></div>
  <div class="col-sm-8"><?php echo $prefix ?></div>
</div>

</div>
</div>

<?php break;
endswitch ?>

<div class="form-buttons">
<?php
    if ( $showRefresh ):
        $form->renderSubmit( $this->t( 'cmd.Refresh' ), 'refresh', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showBack ):
        $form->renderSubmit( '<span class="fa fa-chevron-left" aria-hidden="true"></span> ' . $this->t( 'cmd.Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->t( 'cmd.Next' ) . ' <span class="fa fa-chevron-right" aria-hidden="true"></span>', 'next', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showInstall ):
        $form->renderSubmit( $this->t( 'cmd.Install' ), 'install', array( 'class' => 'btn btn-primary' ) );
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
