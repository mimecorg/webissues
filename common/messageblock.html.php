<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div class="message-block">
<h1<?php if ( $this->hasSlot( 'header_class' ) ): ?> class="<?php $this->insertSlot( 'header_class' ) ?>"<?php endif ?>><?php echo $header ?></h1>

<?php $this->insertContent() ?>

</div>
