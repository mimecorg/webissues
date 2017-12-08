<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->tr( 'Your email was successfully registered on the WebIssues Server as the following issue:' ) ?></p>

<ul>
<li><?php echo $this->tr( 'ID: %1', null, '#' . $issueId ) ?></li>
<li><?php echo $this->tr( 'Name: %1', null, $issueName ) ?></li>
</ul>

<p><?php echo $this->tr( 'You can add comments and attachments to this issue by responding to this email. Include %1 in the subject when sending emails regarding this issue.',
    null, '[#' . $issueId . ']' ) ?></p>

<?php if ( !empty( $subscribe ) ): ?>
<p><?php echo $this->tr( 'You will receive email notifications when someone else modifies this issue, adds a comment or attachment.' ) ?></p>
<?php endif ?>

<?php if ( !empty( $hasLinks ) ): ?>
<p><?php echo $this->tr( 'You can also %1 in the WebIssues Server.', null, $this->mailLink( $this->appendQueryString( '/client/index.php', array( 'issue' => $issueId ) ), $this->tr( 'view this issue' ) ) ) ?></p>
<?php endif ?>
