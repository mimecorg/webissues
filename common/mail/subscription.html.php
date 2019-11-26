<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php if ( !empty( $hasInbox ) ): ?>
<p><?php echo $separator . ' ' . $this->t( 'prompt.IncludeResponseAbove' ) . ' ' . $separator ?></p>
<p><?php echo $this->t( 'prompt.EmailRespond', array( '[#' . $issueId . ']' ) ) ?></p>
<?php endif ?>

<h1>
<?php echo $this->mailLink( '/client/index.php#/issues/' . $issueId, $details[ 'issue_name' ] ); ?>
</h1>

<h3><?php echo $this->t( 'title.Properties' ) ?></h3>

<div class="issue-details-title"><?php echo $this->t( 'title.ID' ) ?></div>
<div class="issue-details-value"><?php echo $details[ 'issue_id' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Type' ) ?></div>
<div class="issue-details-value"><?php echo $details[ 'type_name' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Location' ) ?></div>
<div class="issue-details-value"><?php echo $details[ 'project_name' ] . ' &mdash; ' . $details[ 'folder_name' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Created' ) ?></div>
<div class="issue-details-value"><?php echo $details[ 'created_date' ] . ' &mdash; ' . $details[ 'created_by' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.LastModified' ) ?></div>
<div class="issue-details-value"><?php echo $details[ 'modified_date' ] . ' &mdash; ' . $details[ 'modified_by' ] ?></div>

<?php if ( !empty( $attributeValues ) ): ?>

<h3><?php echo $this->t( 'title.Attributes' ) ?></h3>

<?php foreach( $attributeValues as $value ): ?>
<div class="issue-details-title"><?php echo $value[ 'attr_name' ] ?></div>
<div class="issue-details-value"><?php echo $value[ 'attr_value' ] != '' ? $value[ 'attr_value' ] : '&nbsp;' ?></div>
<?php endforeach ?>

<?php endif ?>

<?php if ( !empty( $descr ) ): ?>

<h3><?php echo $this->t( 'title.Description' ) ?></h3>

<div class="description-panel">

<div class="formatted-text"><?php echo $descr[ 'descr_text' ] ?></div>

<?php if ( $descr[ 'is_modified' ] ): ?>
<div class="last-edited"><?php echo $descr[ 'modified_date' ] . ' &mdash; ' . $descr[ 'modified_by' ]; ?></div>
<?php endif ?>

</div>

<?php endif ?>

<?php if ( !empty( $history ) ): ?>

<h3><?php echo $this->t( 'title.History' ) ?></h3>

<?php foreach ( $history as $id => $item ): ?>

<h4><?php echo  $item[ 'created_date' ] . ' &mdash; ' . $item[ 'created_by' ] ?></h4>

<?php
    switch ( $item[ 'change_type' ] ):
    case System_Const::IssueCreated:
    case System_Const::IssueRenamed:
    case System_Const::ValueChanged:
?>

<ul class="issue-history-list">
<?php foreach ( $item[ 'changes' ] as $change ): ?>
<li>
  <span class="issue-history-label"><?php echo $change[ 'change_type' ] == System_Const::ValueChanged ? $change[ 'attr_name' ] : $this->t( 'title.Name' ) ?>:</span>
  <?php if ( $change[ 'change_type' ] != System_Const::IssueCreated && $change[ 'value_old' ] != '' ): ?>
  <span class="issue-history-value"><?php echo $change[ 'value_old' ]; ?></span> &rarr;
  <?php endif ?>
  <?php if ( $change[ 'value_new' ] != '' ): ?>
  <span class="issue-history-value"><?php echo $change[ 'value_new' ]; ?></span>
  <?php else: ?>
  <?php echo $this->t( 'text.empty' ); ?>
  <?php endif ?>
</li>
<?php endforeach ?>
</ul>

<?php
    break;
    case System_Const::CommentAdded:
?>

<div class="issue-comment">

<div class="formatted-text"><?php echo $item[ 'comment_text' ] ?></div>

<?php if ( $item[ 'is_modified' ] ): ?>
<div class="last-edited"><?php echo $item[ 'modified_date' ] . ' &mdash; ' . $item[ 'modified_by' ]; ?></div>
<?php endif ?>

</div>

<?php
    break;
    case System_Const::FileAdded:
?>

<div class="issue-attachment">
<?php
    echo $this->mailLink( $this->appendQueryString( '/client/file.php', array( 'id' => $id ) ), $item[ 'file_name' ] ) . ' (' . $item[ 'file_size' ] . ')';
    if ( $item[ 'file_descr' ] != '' ):
        echo ' &mdash; ' . $item[ 'file_descr' ];
    endif;
?>

<?php if ( $item[ 'is_modified' ] ): ?>
<div class="last-edited"><?php echo $item[ 'modified_date' ] . ' &mdash; ' . $item[ 'modified_by' ]; ?></div>
<?php endif ?>

</div>

<?php
    break;
    case System_Const::IssueMoved:
?>

<ul class="issue-history-list">
<li>
  <span class="issue-history-label"><?php echo $this->t( 'title.Location' ); ?>:</span>
  <?php if ( $item[ 'from_project_name' ] != '' ): ?>
  <span class="issue-history-value"><?php echo $item[ 'from_project_name' ] . ' &mdash; ' . $item[ 'from_folder_name' ]; ?></span>
  <?php else: ?>
  <?php echo $this->t( 'text.unknown' ); ?>
  <?php endif ?>
  &rarr;
  <?php if ( $item[ 'to_project_name' ] != '' ): ?>
  <span class="issue-history-value"><?php echo $item[ 'to_project_name' ] . ' &mdash; ' . $item[ 'to_folder_name' ]; ?></span>
  <?php else: ?>
  <?php echo $this->t( 'text.unknown' ); ?>
  <?php endif ?>
</li>
</ul>

<?php
    break;
    endswitch;
?>

<?php endforeach ?>

<?php endif ?>

<p class="footer"><?php echo $this->t( 'prompt.SubsriptionEmail' ) ?></p>
