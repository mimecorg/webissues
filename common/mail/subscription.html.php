<?php if ( !defined( 'WI_VERSION' ) ) die( -1 ); ?>

<?php if ( !empty( $hasInbox ) ): ?>
<p><?php echo $separator . ' ' . $this->t( 'prompt.IncludeResponseAbove' ) . ' ' . $separator ?></p>
<p><?php echo $this->t( 'prompt.EmailRespond', array( '[#' . $issueId . ']' ) ) ?></p>
<?php endif ?>

<h1>
<?php echo $this->mailLink( '/client/index.php#/issues/' . $issueId, $details[ 'issue_name' ] ); ?>
</h1>

<div class="sub-pane-wrapper">

<table class="sub-pane-layout">
<tr>
<td class="top-sub-pane"<?php if ( empty( $attributeValues ) ) echo ' colspan="2"' ?>>

<h3><?php echo $this->t( 'title.Properties' ) ?></h3>

<table class="info-list">
<tr>
<td><?php echo $this->t( 'label.ID' ) ?></td>
<td><?php echo $details[ 'issue_id' ] ?></td>
</tr>
<tr>
<td><?php echo $this->t( 'label.Type' ) ?></td>
<td><?php echo $details[ 'type_name' ] ?></td>
</tr>
<tr>
<td><?php echo $this->t( 'label.Location' ) ?></td>
<td><?php echo $details[ 'project_name' ] . ' &mdash; ' . $details[ 'folder_name' ] ?></td>
</tr>
<tr>
<td><?php echo $this->t( 'label.Created' ) ?></td>
<td><?php echo $details[ 'created_date' ] . ' &mdash; ' . $details[ 'created_by' ] ?></td>
</tr>
<tr>
<td><?php echo $this->t( 'label.LastModified' ) ?></td>
<td><?php echo $details[ 'modified_date' ] . ' &mdash; ' . $details[ 'modified_by' ] ?></td>
</tr>
</table>

</td>
<?php if ( !empty( $attributeValues ) ): ?>
<td class="top-sub-pane">

<h3><?php echo $this->t( 'title.Attributes' ) ?></h3>

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

<h3><?php echo $this->t( 'title.Description' ) ?></h3>

<div class="comment-text"><?php echo $descr[ 'descr_text' ] ?></div>

</td>
</tr>
<?php endif ?>

<?php if ( !empty( $history ) ): ?>
<tr>
<td colspan="2" class="bottom-sub-pane">

<h3><?php echo $this->t( 'title.IssueHistory' ) ?></h3>

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
        echo $this->t( 'label.Name' ) . ' "' . $change[ 'value_new' ] . '"';
        break;
    case System_Const::IssueRenamed:
        echo $this->t( 'label.Name' ) . ' "' . $change[ 'value_old' ] . '" &rarr; "' . $change[ 'value_new' ] . '"';
        break;
    case System_Const::ValueChanged:
        $from = ( $change[ 'value_old' ] == '' ) ? $this->t( 'text.empty' ) : '"' . $change[ 'value_old' ] . '"';
        $to = ( $change[ 'value_new' ] == '' ) ? $this->t( 'text.empty' ) : '"' . $change[ 'value_new' ] . '"';
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
    echo $this->mailLink( $this->appendQueryString( '/client/file.php', array( 'id' => $id ) ), $item[ 'file_name' ] ) . ' (' . $item[ 'file_size' ] . ')';
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
    $from = ( $item[ 'from_folder_name' ] == '' ) ? $this->t( 'unknown' ) : '"' . $item[ 'from_folder_name' ] . '"';
    $to = ( $item[ 'to_folder_name' ] == '' ) ? $this->t( 'unknown' ) : '"' . $item[ 'to_folder_name' ] . '"';
    echo $this->t( 'label.Location' ) . ' ' . $from . ' &rarr; ' . $to;
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

<p><?php echo $this->t( 'prompt.SubsriptionEmail' ) ?></p>
