<?
echo "<p>Inserted ".count($distinct_tags)." distinct tags for ".count($raw_tags)." blog entries.</p>";
?>
<p>Distinct Tag Array:</p>
<?

echo "<pre>".print_r($distinct_tags,true)."</pre>";

?>
<p>By blog entry id:</p>
<?

echo "<pre>".print_r($raw_tags,true)."</pre>";