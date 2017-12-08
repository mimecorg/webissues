<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php switch ( $page ):
case 'up_to_date': ?>

<p class="error"><?php echo $this->tr( 'The database of this WebIssues Server is already up to date.' ) ?></p>

<?php break;
case 'completed': ?>

<p><?php echo $this->tr( 'Update of your WebIssues Server was successfully completed.' ) ?></p>

<p><?php echo $this->tr( 'Go to the %1 to continue the configuration of this server.', null,
    $this->link( '/admin/index.php', $this->tr( 'Administration Panel' ) ) ) ?></p>

<?php break;
case 'failed': ?>

<p class="error"><?php echo $this->tr( 'Update failed with the following fatal error:' ) ?></p>
<p><?php echo nl2br( $error ) ?></p>

<?php break;
default: ?>

<?php $form->renderFormOpen() ?>

<?php switch ( $page ):
case 'login': ?>

<p><?php echo $this->tr( 'Log in as administrator in order to update the server.' ) ?></p>

<?php $form->renderText( $this->tr( 'Login:' ), 'login', array( 'size' => 40 ) ) ?>
<?php $form->renderPassword( $this->tr( 'Password:' ), 'password', array( 'size' => 40 ) ) ?>

<?php break;
case 'update': ?>

<p><?php echo $this->tr( 'The database of this WebIssues Server will be updated to version %1.', null, WI_DATABASE_VERSION ) ?></p>

<?php $this->insertComponent( 'Admin_Info_Server' ) ?>

<?php $this->insertComponent( 'Admin_Info_Database' ) ?>

<?php break;
endswitch ?>

<div class="form-submit">
<?php
    if ( $showBack ):
        $form->renderSubmit( $this->tr( '&lt; Back' ), 'back' );
    endif;
    if ( $showNext ):
        $form->renderSubmit( $this->tr( 'Next &gt;' ), 'next' );
    endif;
    if ( $showUpdate ):
        $form->renderSubmit( $this->tr( 'Update' ), 'update' );
    endif;
?>
</div>

<?php $form->renderFormClose() ?>

<?php if ( $showUpdate ): ?>
<div id="progress" style="display: none">
    <?php echo $this->image( '/common/images/throbber.gif', '', array( 'width' => 32, 'height' => 32 ) ) . $this->tr( 'Update in progress...' ) ?>
</div>
<?php endif ?>

<?php endswitch ?>
