<p>Inserted <?=count($distinct_tags)?> distinct tags for <?=count($raw_tags)?> blog entries.</p>

<p><?=lang('tag_array_label')?></p>

<pre><?=print_r($distinct_tags,true)?></pre>

<p><?=lang('blog_entries_label')?></p>

<pre><?=print_r($raw_tags,true)?></pre>