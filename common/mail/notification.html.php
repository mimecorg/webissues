<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<h1>
<?php
    if ( !empty( $folderName ) ):
        echo $this->mailLink( '/client/index.php#' . $viewUrl, $typeName . ' &mdash; ' . $viewName . ' ' . $this->t( 'text.in' ) . ' ' . $projectName . ' &mdash; ' . $folderName );
    elseif ( !empty( $projectName ) ):
        echo $this->mailLink( '/client/index.php#' . $viewUrl, $typeName . ' &mdash; ' . $viewName . ' ' . $this->t( 'text.in' ) . ' ' . $projectName );
    else:
        echo $this->mailLink( '/client/index.php#' . $viewUrl, $typeName . ' &mdash; ' . $viewName );
    endif
?>
</h1>

<table class="grid">
<tr>
<?php foreach ( $columns as $column => $name ): ?>
<th><?php echo $headers[ $column ] ?></th>
<?php endforeach ?>
</tr>
<?php foreach ( $issues as $issueId => $issue ): ?>
<tr>
<?php foreach ( $columns as $column => $name ): ?>
<td>
<?php
    if ( $column == System_Api_Column::Name ):
        echo $this->mailLink( '/client/index.php#/issues/' . $issueId, $issue[ $name ] );
    elseif ( $column == System_Api_Column::Location ):
        echo $issue[ 'project_name' ] . ' &mdash; ' . $issue[ 'folder_name' ];
    else:
        echo $issue[ $name ];
    endif;
?>
</td>
<?php endforeach ?>
</tr>
<?php endforeach ?>
</table>

<?php foreach ( $details as $issueId => $issue ): ?>

<h2>
<?php echo $this->mailLink( '/client/index.php#/issues/' . $issueId, $issue[ 'issue_name' ] ); ?>
</h2>

<h3><?php echo $this->t( 'title.Properties' ) ?></h3>

<div class="issue-details-title"><?php echo $this->t( 'title.ID' ) ?></div>
<div class="issue-details-value"><?php echo $issue[ 'issue_id' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Type' ) ?></div>
<div class="issue-details-value"><?php echo $issue[ 'type_name' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Location' ) ?></div>
<div class="issue-details-value"><?php echo $issue[ 'project_name' ] . ' &mdash; ' . $issue[ 'folder_name' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.Created' ) ?></div>
<div class="issue-details-value"><?php echo $issue[ 'created_date' ] . ' &mdash; ' . $issue[ 'created_by' ] ?></div>
<div class="issue-details-title"><?php echo $this->t( 'title.LastModified' ) ?></div>
<div class="issue-details-value"><?php echo $issue[ 'modified_date' ] . ' &mdash; ' . $issue[ 'modified_by' ] ?></div>

<?php if ( !empty( $issue[ 'attribute_values' ] ) ): ?>

<h3><?php echo $this->t( 'title.Attributes' ) ?></h3>

<?php foreach( $issue[ 'attribute_values' ] as $value ): ?>
<div class="issue-details-title"><?php echo $value[ 'attr_name' ] ?></div>
<div class="issue-details-value"><?php echo $value[ 'attr_value' ] != '' ? $value[ 'attr_value' ] : '&nbsp;' ?></div>
<?php endforeach ?>
</table>

<?php endif ?>

<?php if ( !empty( $issue[ 'description' ] ) ): ?>

<h3><?php echo $this->t( 'title.Description' ); ?></h3>

<div class="description-panel">

<div class="formatted-text"><?php echo $issue[ 'description' ][ 'descr_text' ] ?></div>

<?php if ( $issue[ 'description' ][ 'is_modified' ] ): ?>
<div class="last-edited"><?php echo $issue[ 'description' ][ 'modified_date' ] . ' &mdash; ' . $issue[ 'description' ][ 'modified_by' ]; ?></div>
<?php endif ?>

</div>

<?php endif ?>

<?php if ( !empty( $issue[ 'history' ] ) ): ?>

<h3><?php echo $this->t( 'title.History' ) ?></h3>

<?php foreach ( $issue[ 'history' ] as $id => $item ): ?>

<h4><?php echo  $item[ 'created_date' ] . ' &mdash; ' . $item[ 'created_by' ]; ?></h4>

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

<?php endforeach ?>

<p class="footer"><?php echo $alertType == System_Const::Alert ? $this->t( 'prompt.AlertEmail' ) : $this->t( 'prompt.ReportEmail' ); ?></p>
