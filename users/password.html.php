<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'email': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ResetPasswordEmail' ) ?></p>
</div>

<?php $form->renderText( $this->t( 'label.EmailAddress' ), 'email' ); ?>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
<?php $form->renderSubmit( $this->t( 'cmd.Cancel' ), 'cancel' ) ?>
</div>

<?php break;
case 'sent': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ResetPasswordSent' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
case 'reset': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ResetPasswordEnter', array( '<strong>' . $userLogin . '</strong>' ) ) ?></p>
</div>

<?php $form->renderPassword( $this->t( 'label.Password' ), 'password' ) ?>
<?php $form->renderPassword( $this->t( 'label.ConfirmPassword' ), 'passwordConfirm' ) ?>


<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
<?php $form->renderSubmit( $this->t( 'cmd.Cancel' ), 'cancel' ) ?>
</div>

<?php break;
case 'done': ?>

<div class="alert alert-info">
<p><?php echo $this->t( 'prompt.ResetPasswordComplete' ) ?></p>
</div>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php break;
endswitch ?>

<?php $form->renderFormClose() ?>
