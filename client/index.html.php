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
  <script type="text/javascript" src="<?php echo $this->url( $commonScriptUrl ) ?>"></script>
  <script type="text/javascript" src="<?php echo $this->url( $mainScriptUrl ) ?>"></script>
</head>
<body>
  <div id="application"></div>
  <script type="text/javascript">WebIssues_main.initialize(<?php echo $options ?>);</script>
</body>
</html>
