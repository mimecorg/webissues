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

h1, h2, h3, h4 {
  margin-top: 20px;
  margin-bottom: 10px;
  font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
  line-height: 1.42857143;
  color: #333;
}

h1 {
  margin-top: 0;
  margin-bottom: 20px;
  font-size: 18px;
}

h2 {
  font-size: 16px;
}

h3 {
  font-size: 15px;
}

h4 {
  font-size: 14px;
  margin-top: 10px;
}
</style>

<?php if ( $this->hasSlot( 'withCssGrid' ) ): ?>
<style type="text/css">
table.grid {
  width: 100%;
  margin: 0;
  padding: 0;
  border-collapse: collapse;
  border-spacing: 0;
}

table.grid th {
  padding: 5px 10px 3px 10px;
  background: #eee;
  color: #888;
  border-top: 1px solid #ddd;
  border-bottom: 1px solid #ddd;
  vertical-align: top;
  text-align: left;
  font-size: 12px;
  font-weight: normal;
  text-transform: uppercase;
}

table.grid td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
  vertical-align: top;
}
</style>
<?php endif; ?>

<?php if ( $this->hasSlot( 'withCssDetails' ) ): ?>
<style type="text/css">
.issue-details-title {
  margin-top: 8px;
  text-transform: uppercase;
  font-size: 12px;
  color: #777;
}

.issue-details-value {
  min-height: 20px;
  white-space: pre;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.description-panel, .issue-comment, .issue-attachment {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.last-edited {
  color: #777;
  text-align: right;
  margin-top: 5px;
}

ul.issue-history-list {
  margin-top: 0;
  margin-bottom: 0;

}

ul.issue-history-list li {
  color: #777;
  word-wrap: break-word;
}

.issue-history-label {
  text-transform: uppercase;
  font-size: 12px;
}

.issue-history-value {
  color: #333;
}

.formatted-text {
  white-space: pre;
  white-space: pre-wrap;
  word-wrap: break-word;
}

.formatted-text ul {
  margin-top: 0;
  margin-bottom: 0;
  white-space: normal;
}

.formatted-text .quote {
  margin-bottom: 5px;
  padding: 5px 10px;
  border-left: 2px solid #777;
}

.formatted-text .quote-title {
  margin-bottom: 5px;
  font-weight: bold;
}

.formatted-text pre, .formatted-text code {
  font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
  font-size: 13px;
}

.formatted-text pre {
  margin: 0 0 5px;
  padding: 5px 10px;
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ccc;
  border-radius: 4px;
  line-height: 1.42857143;
  word-wrap: break-word;
  overflow-x: auto;
}

.formatted-text code {
  padding: 2px 4px;
  color: #c7254e;
  background-color: #f9f2f5;
  border-radius: 4px;
}

.formatted-text .rtl {
  direction: rtl;
}
</style>
<?php endif; ?>
</head>
<body>

<?php $this->insertContent() ?>

</body>
</html>
