<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title><?php echo $siteName ?></title>
  <link rel="shortcut icon" href="<?php echo $this->url( $icon ) ?>">
  <link rel="apple-touch-icon" href="<?php echo $this->url( $touchIcon ) ?>">
<?php $assets->renderHeader() ?>
</head>
<body>

<div id="application">

<?php $this->insertComponent( 'Common_Navbar', $siteName ) ?>

<div id="application-toolbar"></div>

<div class="grid" id="application-grid">
  <div class="grid-header"></div>
  <div class="grid-body">
    <div ref="overlay" class="busy-overlay">
      <div class="busy-spinner">
        <span class="fa fa-spinner fa-spin" aria-hidden="true"></span>
      </div>
    </div>
  </div>
  <div class="grid-footer"></div>
</div>

</div>

<script>window.__WI_OPTIONS=<?php echo $options ?>;</script>
<?php $assets->renderBody() ?>

</body>
</html>
