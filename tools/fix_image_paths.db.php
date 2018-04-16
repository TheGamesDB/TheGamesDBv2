<pre>
<?php

require_once __DIR__ . "/../include/db.config.php";

$qry = "Select id, filename FROM banners WHERE filename LIKE :name ";

$dbh = database::getInstance()->dbh;
$sth = $dbh->prepare($qry);

$sth->bindValue(':name', "%original%", PDO::PARAM_STR);

if($sth->execute())
{
	$res = $sth->fetchAll(PDO::FETCH_OBJ);
	$dbh->beginTransaction();
	foreach($res as $cover)
	{
		$cover->filename = str_replace("original/", "", $cover->filename);
		$sth = $dbh->prepare("UPDATE banners SET filename=:filename WHERE id=:id;");
		$sth->bindValue(':filename', $cover->filename, PDO::PARAM_STR);
		$sth->bindValue(':id', $cover->id, PDO::PARAM_INT);
		$sth->execute();
		echo "Processed $cover->id\n";
	}
	$dbh->commit();
}

?>
</pre>