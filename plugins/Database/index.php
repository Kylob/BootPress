<?php

extract($page->get('params'));

$page->load($plugin['uri'], 'Database.php', 'SQLite.php');

$export = false;

$query_builder = (isset($query_builder)) ? $query_builder : false;

$profile = (isset($profile) && $profile === false) ? false : true;

if (isset($mssql)) // MS SQL
{
	if ($export = ci_load_database('mssql', $mssql, $query_builder, $profile)) $export = new Database($export);
}
elseif (isset($mysql)) // MySQL
{
	if ($export = ci_load_database('mysqli', $mysql, $query_builder, $profile)) $export = new Database($export);
}
elseif (isset($oracle)) // Oracle
{
	if ($export = ci_load_database('oci8', $oracle, $query_builder, $profile)) $export = new Database($export);
}
elseif (isset($postgre)) // PostgreSQL
{
	if ($export = ci_load_database('postgre', $postgre, $query_builder, $profile)) $export = new Database($export);
}
elseif (isset($sqlite)) // SQLite 3
{
	$export = new SQLite($sqlite, $query_builder, $profile);
}
elseif (isset($fts))
{
	$export = array();
	list($search, $values) = each($fts);
	$db = new SQLite;
	$db->fts->create('results', 'search', 'porter');
	$db->fts->upsert('results', 'search', $values);
	$db->query('SELECT docid, search FROM results WHERE search MATCH ?', array($search));
	while (list($docid, $value) = $db->fetch('row')) $export[$docid] = $value;
	unset($db);
}

?>