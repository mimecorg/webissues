<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php $this->insertSlot( 'subject' ) ?></title>
  <style type="text/css">
<?php readfile( WI_ROOT_DIR . '/common/theme/mail.css' ) ?>
  </style>
</head>
<body>

<?php $this->insertContent() ?>

</body>
</html>
