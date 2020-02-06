<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php $this->beginSlot( 'header' ) ?>
<a type="button" href="<?php echo WI_GUIDE_URL ?>" target="_blank" class="btn btn-default">
  <span aria-hidden="true" class="fa fa-question-circle"></span> <span class="hidden-xs"><?php echo $this->t( 'title.WebIssuesGuide' ) ?></span>
</a>
<?php $this->endSlot() ?>

<?php $form->renderFormOpen() ?>

<p><strong><?php echo $this->t( 'label.Version' ) ?> <?php echo WI_VERSION ?></strong></p>
<hr>
<p class="about-link"><span aria-hidden="true" class="fa fa-info-circle"></span> <a href="https://webissues.mimec.org" target="_blank">webissues.mimec.org</a></p>
<p class="about-link"><span aria-hidden="true" class="fa fa-github"></span> <a href="https://github.com/mimecorg/webissues" target="_blank">github.com/mimecorg/webissues</a></p>
<hr>
<p>Copyright &copy; 2006 Michał Męciński<br>Copyright &copy; 2007-2020 WebIssues Team</p>
<p><?php echo $this->t( 'text.License' ) ?></p>

<div class="form-buttons">
<?php $form->renderSubmit( $this->t( 'cmd.OK' ), 'ok', array( 'class' => 'btn btn-primary' ) ) ?>
</div>

<?php $form->renderFormClose() ?>
