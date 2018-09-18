<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'register': ?>

<div class="alert alert-info">
<p>
<?php
    echo $this->t( 'prompt.RegisterNewAccount' );
    if ( !$autoApprove ):
        echo ' ' . $this->t( 'prompt.RegisterAdministratorApproval' );
    endif
?>
</p>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.UserName' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->t( 'prompt.UserName' ) ?></p>

<?php $form->renderText( $this->t( 'label.Name' ), 'userName' ); ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'Credentials' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->t( 'prompt.Credentials' ) ?></p>

<?php $form->renderText( $this->t( 'label.Login' ), 'login' ); ?>
<?php $form->renderPassword( $this->t( 'label.Password' ), 'password' ) ?>
<?php $form->renderPassword( $this->t( 'label.ConfirmPassword' ), 'passwordConfirm' ) ?>

</div>
</div>

<div class="panel panel-default">
<div class="panel-heading">
<h2 class="panel-title"><?php echo $this->t( 'title.EmailAddress' ) ?></h2>
</div>
<div class="panel-body">

<p><?php echo $this->t( 'prompt.EmailAddress' ) ?></p>

<?php $form->renderText( $this->t( 'label.EmailAddress' ), 'email' ); ?>

</div>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.Register' ), 'register', array( 'class' => 'btn btn-primary' ) ) ?>
<?php $form->renderSubmit( $this->t( 'cmd.Cancel' ), 'cancel' ) ?>
</div>

<?php break;
case 'registered': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.RegistrationComplete' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
case 'activated': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.RegistrationVerified' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
case 'approved': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.RegistrationAutoApproved' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
