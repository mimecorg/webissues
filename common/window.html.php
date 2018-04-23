<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<div id="window" class="window-<?php echo $size ?>">
<div class="container-fluid">

<div class="form-header">
  <h1><?php echo $header ?></h1>
</div>

<?php $this->insertContent() ?>

</div>
</div>
