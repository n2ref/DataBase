DataBase
========

Class for using the database, with the ability to use different adapters such as 'Mysqli', 'PostgreSQL', 'Mssql' and some other



Simple example, initialize and select 

```php
require_once 'class.database.php';
$db = new DB('PDO_Mysql', 'localhost', 'workbase', 'user_name', 'user_pass');

$result_array = $db->fetchRow("
		SELECT title, 
			   description,
			   data
		FROM   table_name
		WHERE  id = ?
", $id);

// also exists methods fetchAll, fetchOne
```

Slightly more complex example of using a method 'query'

```php
$db = new DB('PDO_Mysql', 'localhost', 'workbase', 'user_name', 'user_pass');

// here is screened and paste parameters in the query

$is_add = $db->query("
		INSERT INTO table_items (
			title, 
			preview, 
			content,
			category_id, 
			user_id, 
			published,
			last_modified
		) VALUE (
			:title,
			:preview,
			:content,
			:category_id,
			:user_id,
			'1',
			NOW()
		)
	", array(
		'title' 	  => $item['title'], 
		'preview' 	  => $item['preview'], 
		'content' 	  => $item['content'], 
		'category_id' => $item['category_id'], 
		'user_id' 	  => $item['user_id']
	)
);

if ($is_add) {
	$item_id = $db->lastInsertId();
}

$db->close();

```
