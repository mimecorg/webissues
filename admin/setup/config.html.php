<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>
/**************************************************************************
* Database configuration file for WebIssues Server
*
* Site name: <?php echo $siteName . "\n" ?>
* Base URL address: <?php echo WI_BASE_URL . "/\n" ?>
*
* This file was generated on <?php echo $date ?> using version <?php echo WI_VERSION ?>.
*
* NOTE: Do not modify this file unless you know what you are doing.
* You can delete it and run the setup script to create a new configuration
* file.
**************************************************************************/

if ( !defined( 'WI_VERSION' ) ) die( -1 );

<?php foreach ( $config as $key => $value ): ?>
$config[ '<?php echo $key ?>' ] = '<?php echo $value ?>';
<?php endforeach ?>
