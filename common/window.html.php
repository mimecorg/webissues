<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div id="window" class="window-<?php echo $size ?>">
<div class="container-fluid">

<div class="form-header">
  <div class="form-header-group">
    <div class="form-header-title">
      <h1><?php echo $header ?></h1>
    </div>
    <div class="form-header-buttons">
      <?php $this->insertSlot( 'header' ) ?>
    </div>
  </div>
</div>

<?php $this->insertContent() ?>

</div>
</div>
