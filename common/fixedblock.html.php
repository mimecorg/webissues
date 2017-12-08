<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="fixed-block">

<?php if ( $this->hasSlot( 'float_links' ) ): ?>
<div style="float: right;">
<?php $this->insertSlot( 'float_links' ) ?>
</div>
<?php endif ?>

<h1<?php if ( $this->hasSlot( 'header_class' ) ): ?> class="<?php $this->insertSlot( 'header_class' ) ?>"<?php endif ?>><?php echo $header ?></h1>

<?php $this->insertContent() ?>

</div>
