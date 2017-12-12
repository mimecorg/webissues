<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php echo $pageTitle ?> | <?php echo $siteName ?></title>
  <link rel="shortcut icon" href="<?php echo $this->url( $icon ) ?>" type="image/vnd.microsoft.icon">
<?php foreach ( $cssFiles as $file ): ?>
  <link rel="stylesheet" href="<?php echo $this->url( $file ) ?>" type="text/css">
<?php endforeach ?>
<?php foreach ( $cssConditional as $condition => $file ): ?>
  <!--[if <?php echo $condition ?>]><link rel="stylesheet" href="<?php echo $this->url( $file ) ?>" type="text/css"><![endif]-->
<?php endforeach ?>
<?php foreach ( $scriptFiles as $file ): ?>
  <script type="text/javascript" src="<?php echo $this->url( $file ) ?>"></script>
<?php endforeach ?>
<?php if ( !empty( $inlineCode ) ): ?>
  <script type="text/javascript">
<?php echo $inlineCode ?>
  </script>
<?php endif ?>
</head>
<body>

<div id="header">
  <a href="<?php echo $this->url( $homeUrl ) ?>"><img id="site-logo" src="<?php echo $this->url( '/common/images/webissues-logo.png' ) ?>" alt="WebIssues"></a>
  <div id="site-name"><?php echo $this->link( $homeUrl, $siteName ) ?></div>
  <div id="header-right">
<?php
    echo $this->tr( 'WebIssues %1', null, WI_VERSION ) . ' | ';
    echo $this->link( $manualUrl, $this->tr( 'Manual' ), array( 'target' => '_blank' ) );
?>
  </div>
  <div id="infobar-left"><?php echo $pageTitle ?></div>
  <div id="infobar-right"></div>
</div>

<div id="body">
<?php $this->insertContent() ?>
</div>

<?php if ( !empty( $errors ) ): ?>
<div class="debug">
<ul>
<?php foreach ( $errors as $error ): ?>
<li><?php echo nl2br( $error ) ?></li>
<?php endforeach ?>
</ul>
</div>
<?php endif ?>

</body>
</html>
