<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'login': ?>

<?php $form->renderText( $this->tr( 'Login:' ), 'login', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>

<?php if ( !$toolBar->isEmpty() ): ?>
<p><?php $toolBar->render() ?></p>
<?php endif ?>

<div class="form-submit">
<?php $form->renderSubmit( $this->tr( 'Log in' ), 'login' ) ?>
</div>

<?php break;
case 'password': ?>

<p><?php echo $this->tr( 'You have to enter a new password in order to log in.' ) ?></p>
<?php $form->renderPassword( $this->tr( 'New password:' ), 'newPassword', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Confirm password:' ), 'newPasswordConfirm', array( 'size' => 40 ) ) ?>

<div class="form-submit">
<?php $form->renderSubmit( $this->tr( 'Log in' ), 'login' ) ?>
<?php $form->renderSubmit( $this->tr( 'Cancel' ), 'cancel' ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
