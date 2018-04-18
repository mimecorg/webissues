<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="alert alert-<?php echo $alertClass ?>">
<p><?php echo $infoMessage ?></p>
</div>

<?php if ( !empty( $linkName ) ): ?>
<div class="form-options">
<p><?php echo $this->link( $linkUrl, '<span class="fa fa-wrench" aria-hidden="true"></span> ' . $linkName ) ?></p>
</div>
<?php endif ?>
