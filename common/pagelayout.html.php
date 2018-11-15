<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $siteName ?></title>
  <link rel="shortcut icon" href="<?php echo $this->url( $icon ) ?>">
  <link rel="apple-touch-icon" href="<?php echo $this->url( $touchIcon ) ?>">
<?php $assets->render() ?>
</head>
<body>

<div id="navbar">
  <div class="container-fluid">
    <div class="navbar-group">
      <div class="navbar-element navbar-element-wide">
        <div class="navbar-title">
          <a href="<?php echo $this->url( '/index.php' ) ?>"><?php echo $siteName ?></a>
        </div>
      </div>
      <div id="navbar-element-collapse" aria-expanded="false" class="navbar-element collapse">
        <div class="navbar-sub-group">
          <div class="navbar-sub-element navbar-sub-element-wide">
            <div class="navbar-brand-img"></div>
            <div class="navbar-brand-name">WebIssues <?php echo WI_VERSION ?></div>
          </div>
          <div class="navbar-sub-element">
            <a type="button" title="<?php echo $this->t( 'title.WebIssuesManual' ) ?>" href="<?php echo $this->url( $manualUrl ) ?>" target="_blank" class="btn btn-info">
              <span aria-hidden="true" class="fa fa-question-circle"></span>
            </a>
          </div>
        </div>
      </div>
      <div id="navbar-element-toggle" class="navbar-element">
        <button id="toggle-button" type="button" title="<?php echo $this->t( 'cmd.ToggleNavigation' ) ?>" class="btn btn-info"><span aria-hidden="true" class="fa fa-bars"></span></button>
      </div>
    </div>
  </div>
</div>

<?php $this->insertContent() ?>

<script type="text/javascript">WebIssues.initializePage();</script>

</body>
</html>
