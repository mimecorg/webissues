<?php

//  File Location: /system/api/tkldaphelper.inc.php

if ( !defined( 'WI_VERSION' ) ) die( -1 );

class System_Api_TkLDAPHelper
{
    private static $ldapDomain = '';           // set here your ldap domain
    private static $ldapHost = '';    // set here your ldap host
    private static $ldapPort = '';                      // ldap Port (default 389)
    private static $ldapUser  = '';                        // ldap User (rdn or dn)
    private static $ldapPassword = '';                     // ldap associated Password  
    
    public function __construct(  )
    {
    }
    
   /**
    * Validate given password against the stored hash.
    * @param $password The plain text password.
    * @param $storedHash The stored hash.
    * @return @c true if the password is valid, @c false otherwise.
    */
    public function checkPassword( $user, $password )
    {
        if ( $user == null )
            return false;

        if ( $password == null )
            return false;

        $serverManager = new System_Api_ServerManager();

        self::$ldapDomain = '@' . $serverManager->getSetting( 'ldap_domain' );
        self::$ldapHost = 'ldap://' . $serverManager->getSetting( 'ldap_host' );
        self::$ldapPort = $serverManager->getSetting( 'ldap_port' );

        $ldapConnection = ldap_connect(self::$ldapHost, self::$ldapPort);

        if ($ldapConnection) {
            self::$ldapUser = addslashes(trim($user));
            self::$ldapPassword = addslashes(trim($password));
            
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
            $ldapbind = @ldap_bind($ldapConnection, self::$ldapUser . self::$ldapDomain, self::$ldapPassword);

            // verify binding
            if ($ldapbind) {
                ldap_close($ldapConnection);    // close ldap connection
                return true;
            } 
        }
        return false;
    }
}
?>
