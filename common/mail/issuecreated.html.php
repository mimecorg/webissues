<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<p><?php echo $this->t( 'prompt.EmailRegistered', array( '#' . $issueId ) ) ?></p>

<p><?php echo $this->t( 'prompt.EmailRespond', array( '[#' . $issueId . ']' ) ) ?></p>

<?php if ( !empty( $subscribe ) ): ?>
<p><?php echo $this->t( 'prompt.EmailSubscribed' ) ?></p>
<?php endif ?>

<?php if ( !empty( $linkUrl ) ): ?>
<p><?php echo $this->t( 'prompt.EmailIssueLink' ) ?></p>
<p><?php echo $this->link( $linkUrl, $linkUrl ) ?></p>
<?php endif ?>
