<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $siteName ?></title>
  <link rel="shortcut icon" href="<?php echo $this->url( $icon ) ?>" type="image/vnd.microsoft.icon">
<?php if ( !empty( $styleUrl ) ): ?>
  <link rel="stylesheet" href="<?php echo $this->url( $styleUrl ) ?>" type="text/css">
<?php endif ?>
  <script type="text/javascript" src="<?php echo $this->url( $scriptUrl ) ?>"></script>
</head>
<body class="front-body">

<div id="navbar">
  <div class="container-fluid">
    <div class="navbar-group">
      <div class="navbar-element navbar-element-wide">
        <div class="navbar-brand">
          <div class="navbar-brand-logo">
            <a href="<?php echo $this->url( '/index.php' ) ?>">
              <div class="navbar-brand-img"></div>
            </a>
          </div>
          <div class="navbar-brand-name">
            <a href="<?php echo $this->url( '/index.php' ) ?>"><?php echo $siteName ?></a>
          </div>
        </div>
      </div>
      <div id="navbar-element-collapse" aria-expanded="false" class="navbar-element collapse">
        <div class="navbar-sub-group">
          <div class="navbar-sub-element navbar-sub-element-wide">
            <div class="navbar-version">WebIssues <?php echo WI_VERSION ?></div>
          </div>
          <div class="navbar-sub-element">
            <a type="button" title="<?php echo $this->tr( 'WebIssues Manual' ) ?>" href="<?php echo $this->url( $manualUrl ) ?>" target="_blank" class="btn btn-default">
              <span aria-hidden="true" class="fa fa-question-circle"></span>
            </a>
          </div>
        </div>
      </div>
      <div id="navbar-element-toggle" class="navbar-element">
        <button id="toggle-button" type="button" title="<?php echo $this->tr( 'Toggle Navigation' ) ?>" class="btn btn-default"><span aria-hidden="true" class="fa fa-bars"></span></button>
      </div>
    </div>
  </div>
</div>

<?php $this->insertContent() ?>

<script type="text/javascript">WebIssues_common.initialize();</script>

</body>
</html>
