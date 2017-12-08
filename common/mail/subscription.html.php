<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php if ( !empty( $hasInbox ) ): ?>
<p><?php echo $separator . ' ' . $this->tr( 'Include your response above this line' ) . ' ' . $separator ?></p>
<p><?php echo $this->tr( 'You can add comments and attachments to this issue by responding to this email. Include %1 in the subject when sending emails regarding this issue.',
    null, '[#' . $issueId . ']' ) ?></p>
<?php endif ?>

<h1>
<?php echo $this->mailLink( $this->appendQueryString( '/client/index.php', array( 'issue' => $issueId ) ), $details[ 'issue_name' ] ); ?>
</h1>

<div class="sub-pane-wrapper">

<table class="sub-pane-layout">
<tr>
<td class="top-sub-pane"<?php if ( empty( $attributeValues ) ) echo ' colspan="2"' ?>>

<h3><?php echo $this->tr( 'Properties' ) ?></h3>

<table class="info-list">
<tr>
<td><?php echo $this->tr( 'ID:' ) ?></td>
<td><?php echo $details[ 'issue_id' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Type:' ) ?></td>
<td><?php echo $details[ 'type_name' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Location:' ) ?></td>
<td><?php echo $details[ 'project_name' ] . ' &mdash; ' . $details[ 'folder_name' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Created:' ) ?></td>
<td><?php echo $details[ 'created_date' ] . ' &mdash; ' . $details[ 'created_by' ] ?></td>
</tr>
<tr>
<td><?php echo $this->tr( 'Last Modified:' ) ?></td>
<td><?php echo $details[ 'modified_date' ] . ' &mdash; ' . $details[ 'modified_by' ] ?></td>
</tr>
</table>

</td>
<?php if ( !empty( $attributeValues ) ): ?>
<td class="top-sub-pane">

<h3><?php echo $this->tr( 'Attributes' ) ?></h3>

<table class="info-list">
<?php foreach( $attributeValues as $value ): ?>
<tr>
<td><?php echo $value[ 'attr_name' ] ?>:</td>
<td><?php echo $value[ 'attr_value' ] ?></td>
</tr>
<?php endforeach ?>
</table>

</td>
<?php endif ?>
</tr>

<?php if ( !empty( $descr ) ): ?>
<tr>
<td colspan="2" class="bottom-sub-pane">

<h3><?php echo $this->tr( 'Description' ) ?></h3>

<div class="comment-text"><?php echo $descr[ 'descr_text' ] ?></div>

</td>
</tr>
<?php endif ?>

<?php if ( !empty( $history ) ): ?>
<tr>
<td colspan="2" class="bottom-sub-pane">

<h3><?php echo $this->tr( 'Issue History' ) ?></h3>

<?php
    foreach ( $history as $id => $item ):
?>

<div class="history-item">

<h4>
<?php echo  $item[ 'created_date' ] . ' &mdash; ' . $item[ 'created_by' ] ?>
</h4>

<?php
    switch ( $item[ 'change_type' ] ):
    case System_Const::IssueCreated:
    case System_Const::IssueRenamed:
    case System_Const::ValueChanged:
?>

<ul class="changes">
<?php foreach ( $item[ 'changes' ] as $change ): ?>
<li>
<?php
    switch ( $change[ 'change_type' ] ):
    case System_Const::IssueCreated:
        echo $this->tr( 'Name' ) . ': "' . $change[ 'value_new' ] . '"';
        break;
    case System_Const::IssueRenamed:
        echo $this->tr( 'Name' ) . ': "' . $change[ 'value_old' ] . '" &rarr; "' . $change[ 'value_new' ] . '"';
        break;
    case System_Const::ValueChanged:
        $from = ( $change[ 'value_old' ] == '' ) ? $this->tr( 'empty' ) : '"' . $change[ 'value_old' ] . '"';
        $to = ( $change[ 'value_new' ] == '' ) ? $this->tr( 'empty' ) : '"' . $change[ 'value_new' ] . '"';
        echo $change[ 'attr_name' ] . ': ' . $from . ' &rarr; ' . $to;
        break;
    endswitch;
?>
</li>
<?php endforeach ?>
</ul>

<?php
    break;
    case System_Const::CommentAdded:
?>

<div class="comment-text"><?php echo $item[ 'comment_text' ] ?></div>

<?php
    break;
    case System_Const::FileAdded:
?>

<div class="attachment">
<?php
    echo $this->mailLink( $this->appendQueryString( '/client/issues/getattachment.php', array( 'id' => $id ) ), $item[ 'file_name' ] ) . ' (' . $item[ 'file_size' ] . ')';
    if ( $item[ 'file_descr' ] != '' ):
        echo ' &mdash; ' . $item[ 'file_descr' ];
    endif;
?>
</div>

<?php
    break;
    case System_Const::IssueMoved:
?>

<ul class="changes">
<li>
<?php
    $from = ( $item[ 'from_folder_name' ] == '' ) ? $this->tr( 'Unknown Folder' ) : '"' . $item[ 'from_folder_name' ] . '"';
    $to = ( $item[ 'to_folder_name' ] == '' ) ? $this->tr( 'Unknown Folder' ) : '"' . $item[ 'to_folder_name' ] . '"';
    echo $this->tr( 'Issue moved from %1 to %2', null, $from, $to );
?>
</li>
</ul>

<?php
    break;
    endswitch;
?>

</div>

<?php
    endforeach;
?>

</td>
</tr>
<?php endif ?>

</table>

</div>

<p><?php echo $this->tr( 'This is a subscription email from the WebIssues Server.' ) ?></p>
