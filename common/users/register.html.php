<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'register': ?>

<div class="alert alert-info">
<p>
<?php
    echo $this->tr( 'Fill the information below to begin registration.' );
    if ( !$autoApprove ):
        echo ' ' . $this->tr( 'Note that administrator\'s approval is required before you can log in.' );
    endif
?>
</p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'User Name' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->tr( 'Enter the user name that will be visible to other users.' ) ?></p>

<?php $form->renderText( $this->tr( 'Name:' ), 'userName', array( 'size' => 40 ) ); ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Credentials' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->tr( 'Enter login and password for your new account.' ) ?></p>

<?php $form->renderText( $this->tr( 'Login:' ), 'login', array( 'size' => 40 ) ); ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Confirm password:' ), 'passwordConfirm', array( 'size' => 40 ) ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->tr( 'Email Address' ) ?></h2>
</div>
<div class="panel-body">

<?php $form->renderText( $this->tr( 'Email address:' ), 'email', array( 'size' => 40 ) ); ?>

<p><?php echo $this->tr( 'This address will only be visible to the system administrator. An activation email will be sent to this address.' ) ?></p>

</div>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->tr( 'Register' ), 'register', array( 'class' => 'btn btn-primary' ) ) ?>
<?php $form->renderSubmit( $this->tr( 'Cancel' ), 'cancel' ) ?>
</div>

<?php break;
case 'registered': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'Thank you for registering. You will receive an activation email shortly with instructions how to complete registration.' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->tr( 'OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
case 'activated': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'Your registration request was activated. You will receive a notification email when the administrator approves your request.' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->tr( 'OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
case 'approved': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'Your registration request was activated. You can now log in to the server using your login and password.' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->tr( 'OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
