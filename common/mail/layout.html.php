<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title><?php $this->insertSlot( 'subject' ) ?></title>
  <style type="text/css">
body {
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  font-size: 14px;
  line-height: 1.42857143;
  color: #333;
}

p {
    margin: 0 0 10px;
}

a {
    color: #337ab7;
    text-decoration: none;
}

a:hover, a:focus {
    color: #23527c;
    text-decoration: underline;
}

p.header {
    margin: 0 0 20px;
}

p.footer {
  margin: 20px 0 10px;
  font-size: 12px;
  color: #777;
}
</style>
</head>
<body>

<?php $this->insertContent() ?>

</body>
</html>
