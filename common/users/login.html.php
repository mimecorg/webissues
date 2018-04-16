<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'login': ?>

<?php $form->renderText( $this->tr( 'Login:' ), 'login', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>

<div class="front-login-buttons">
<?php $form->renderSubmit( '<span class="fa fa-sign-in" aria-hidden="true"></span> ' . $this->tr( 'Log in' ), 'login' ) ?>
<?php if ( $anonymousAccess ): ?>
<p>OR</p>
<?php echo $this->link( '/client/index.php', '<span class="fa fa-user-o" aria-hidden="true"></span> ' . $this->tr( 'Anonymous Access' ), array( 'class' => 'btn btn-default' ) ) ?>
<?php endif ?>
</div>

<?php if ( $selfRegister ): ?>
<div class="front-options">
<p><?php echo $this->link( '/register.php', '<span class="fa fa-user-plus" aria-hidden="true"></span> ' . $this->tr( 'Register New Account' ) ) ?></p>
</div>
<?php endif ?>

<?php break;
case 'password': ?>

<div class="alert alert-info">
<p><?php echo $this->tr( 'You have to enter a new password in order to log in.' ) ?></p>
</div>

<?php $form->renderPassword( $this->tr( 'New password:' ), 'newPassword', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Confirm password:' ), 'newPasswordConfirm', array( 'size' => 40 ) ) ?>

<div class="form-buttons">
<?php $form->renderSubmit( $this->tr( 'Log in' ), 'login' ) ?>
<?php $form->renderSubmit( $this->tr( 'Cancel' ), 'cancel' ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
