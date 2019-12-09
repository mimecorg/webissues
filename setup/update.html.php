<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'up_to_date': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ServerAlreadyUpdated' ) ?></p>
</div>

<?php break;
case 'completed': ?>

<div class="alert alert-success">
<p><?php echo $this->t( 'prompt.UpdateCompleted' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->t( 'prompt.ContinueConfiguration', array( $this->link( '/client/index.php', $this->t( 'title.WebClient' ) ) ) ) ?></p>
</div>

<?php break;
case 'failed': ?>

<div class="alert alert-danger">
<p><?php echo $this->t( 'error.UpdateFailed' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo nl2br( $error ) ?></p>
</div>

<?php break;
default: ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'login': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.LogInToUpdate' ) ?></p>
</div>

<?php $form->renderText( $this->t( 'label.Login' ), 'login', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->t( 'label.Password' ), 'password', array( 'size' => 40 ) ) ?>

<?php break;
case 'update': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.UpdateDatabase', array( WI_DATABASE_VERSION ) ) ?></p>
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
    if ( $showBack ):
        $form->renderSubmit( '<span class="fa fa-chevron-left" aria-hidden="true"></span> ' . $this->t( 'cmd.Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->t( 'cmd.Next' ) . ' <span class="fa fa-chevron-right" aria-hidden="true"></span>', 'next', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showUpdate ):
        $form->renderSubmit( $this->t( 'cmd.Update' ), 'update', array( 'class' => 'btn btn-primary' ) );
    endif;
?>
</div>

<?php $form->renderFormClose() ?>

<?php if ( $showUpdate ): ?>
<div tabindex="-1" class="busy-overlay" style="display: none">
  <div class="busy-spinner">
    <span class="fa fa-spinner fa-spin" aria-hidden="true"></span>
  </div>
</div>
<?php endif ?>

<?php endswitch ?>
