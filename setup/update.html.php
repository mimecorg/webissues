<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'up_to_date': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'The database of this WebIssues Server is already up to date.' ) ?></p>
</div>

<?php break;
case 'completed': ?>

<div class="alert alert-success">
<p><?php echo $this->tr( 'Update of your WebIssues Server was successfully completed.' ) ?></p>
</div>

<div class="alert alert-default">
<p><?php echo $this->tr( 'Go to the %1 to continue the configuration of this server.', null,
    $this->link( '/client/index.php', $this->tr( 'Web Client' ) ) ) ?></p>
</div>

<?php break;
case 'failed': ?>

<div class="alert alert-danger">
<p><?php echo $this->tr( 'Update failed with the following fatal error:' ) ?></p>
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
<p><?php echo $this->tr( 'Log in as administrator in order to update the server.' ) ?></p>
</div>

<?php $form->renderText( $this->tr( 'Login:' ), 'login', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>

<?php break;
case 'update': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'The database of this WebIssues Server will be updated to version %1.', null, WI_DATABASE_VERSION ) ?></p>
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
    if ( $showBack ):
        $form->renderSubmit( '<span class="fa fa-caret-left" aria-hidden="true"></span> ' . $this->tr( 'Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->tr( 'Next' ) . ' <span class="fa fa-caret-right" aria-hidden="true"></span>', 'next', array( 'class' => 'btn btn-primary' ) );
    endif;
    if ( $showUpdate ):
        $form->renderSubmit( $this->tr( 'Update' ), 'update', array( 'class' => 'btn btn-primary' ) );
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
