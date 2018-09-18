<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'login': ?>

<?php $form->renderText( $this->t( 'label.Login' ), 'login' ) ?>
<?php $form->renderPassword( $this->t( 'label.Password' ), 'password' ) ?>

<div class="front-login-buttons">
<?php $form->renderSubmit( '<span class="fa fa-sign-in" aria-hidden="true"></span> ' . $this->t( 'cmd.LogIn' ), 'login', array( 'class' => 'btn btn-primary' ) ) ?>
<?php if ( $anonymousAccess ): ?>
<p><?php echo $this->t( 'text.OR' ) ?></p>
<?php echo $this->link( '/client/index.php', '<span class="fa fa-user-o" aria-hidden="true"></span> ' . $this->t( 'cmd.AnonymousAccess' ), array( 'class' => 'btn btn-default' ) ) ?>
<?php endif ?>
</div>

<?php if ( $selfRegister ): ?>
<div class="form-options">
<p><?php echo $this->link( '/users/register.php', '<span class="fa fa-user-plus" aria-hidden="true"></span> ' . $this->t( 'cmd.RegisterNewAccount' ) ) ?></p>
</div>
<?php endif ?>

<?php break;
case 'password': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.EnterNewPassword' ) ?></p>
</div>

<?php $form->renderPassword( $this->t( 'label.NewPassword' ), 'newPassword' ) ?>
<?php $form->renderPassword( $this->t( 'label.ConfirmPassword' ), 'newPasswordConfirm' ) ?>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.LogIn' ), 'login', array( 'class' => 'btn btn-primary' ) ) ?>
<?php $form->renderSubmit( $this->t( 'cmd.Cancel' ), 'cancel' ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
