<?php
	// --------------------------------------------------------------------------------
	// github.com/morebrackets/php-mongodb-crud-example
	// No Copyright. Public Domain. Do anything you want with this code.
	// This example code will get you started with the basics of PHP & MongoDB
	// --------------------------------------------------------------------------------

	$dbHost = 'localhost'; // DB Server Host - This could also be 127.0.0.1 or a remore host/ip
	$dbPort = 27017; // DB Server Port
	$dbName = 'testdb'; // Database where we will work
	$dbColl = 'testcoll'; // Collection (like a table) where we will work

	error_reporting(-1); // display all errors (not required)

  	// To install the MongoDB PHP driver:
	// composer require mongodb/mongodb
	// add extension=mongodb.so to php.ini

	require_once 'vendor/autoload.php';  // Path to Composer's autoload which includes the MongoDB driver

	// CONNECT
	$mdbClient = new MongoDB\Client('mongodb://' . $dbHost . ':' . $dbPort);

	try {
	    $dbs = $mdbClient->listDatabases();
	} catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
	    echo "Error: PHP cannot connect to MongoDB<br>\n";
	    die();
	}

	// SELECT DB (shortcut for future reuse)
	$DB = $mdbClient->{$dbName};

	// DEFINE an INDEX (not required)
	$DB->{$dbColl}->createIndex(['name' => 1]);


	// CREATE (Insert one Document)
	// Note, this will fail and kill the entire script if an indexed unique field already exists. Use find first or upsert instead if possible.
	$result = $DB->{$dbColl}->insertOne(['name' => 'Peter Griffin', 'password' => 123, 'food' => 'Pizza', 'greeting' => 'Hello']);
	echo "INSERTed MongoID '" . $result->getInsertedId() . "' (1)<br>\n";

	$result = $DB->{$dbColl}->insertOne(['name' => 'Stewie Griffin', 'password' => 456, 'food' => 'Carrots', 'greeting' => 'Goodbye']);
	echo "INSERTed MongoID '" . $result->getInsertedId() . "' (2)<br>\n";
	

	// CREATE/UPDATE (Upsert one Document)
	$result = $DB->{$dbColl}->updateOne(
	    ['name' => 'Peter Griffin', 'password' => 123], // What document to find
	    [ 
	    	'$set' => ['name' => 'Peter Griffin', 'password' => 456, 'food' => 'Avocado'], // What document keys to update/insert
	    	'$unset' => ['a' => true,'b' => true], // Delete some keys
	    	'$inc' => ['u' => 1]  // Increment a key
	    ],
	    ['upsert' => true] // Enable upsert
	);

	if($result->getUpsertedCount()){
		// AN INSERT WAS PERFORMED (No existing document found)
		echo "Upsert: An INSERT was performed resulting in new MongoID '" . $result->getUpsertedId() . "'.<br>\n";
	}else{
		// AN UPDATE WAS PERFORMED (Document alreay existed)
		// Note: You can run a new find command here to find the _id or use findAndModify instead of upsert
		echo "Upsert: An UPDATE was performed.<br>\n";
	}


	// UPDATE (One Document)
	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
		['$set' => ['f' => 789]]
	);

	// Add to set (unique array)
	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
        	[ '$addToSet' => [ 'color' => 'blue' ] ]
    	);

	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
        	[ '$addToSet' => [ 'color' => 'green' ] ]
       );

	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
		[ '$addToSet' => [ 'color' => 'pink' ] ]
	);

	// Add array items to set
	$fruit = ['orange','grape','blueberry'];
	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
		[ '$addToSet' => [ 'color' => ['$each' => $fruit] ] ]
	);

	// Remove from array
	$DB->{$dbColl}->updateOne(
		['name' => 'Peter Griffin'],
		[ '$pull' => [ 'color' => 'green' ] ]
	);


	// Update Many
	$result = $collection->updateMany(
	    ["name" => ["$exists" => true] ],
	    ['$set' => ['hey' => 'you']]
	);

	echo "Update Many: ".$result->getModifiedCount()." document(s) modified.<br>\n";


	// READ (Find One Document)
	$doc = $DB->{$dbColl}->findOne(['name' => 'Peter Griffin']);

	$doc['_id'] = (string)$doc['_id']; // Convert MongoID Object to a string so that json_encode works

	echo "Read Document (1): " . json_encode($doc) . "<br>\n";

	// Validate MongoID (format)
	if(isValidMongoID($doc['_id'])){
		echo "MongoID '".$doc['_id']."' is valid.<br>\n";
	}else{
		echo "MongoID '".$doc['_id']."' is NOT valid.<br>\n";
	}


	// READ (Find One Documents by _id object)
	$doc = $DB->{$dbColl}->findOne(['_id' => $doc['_id'] ]);

	$doc['_id'] = (string)$doc['_id']; // Convert MongoID Object to a string so that json_encode works

	echo "Read Document (2): " . json_encode($doc) . "<br>\n";


	// READ (Find One Documents by _id string)
	$doc = $DB->{$dbColl}->findOne(['_id' => new MongoDB\BSON\ObjectId((string)$doc['_id']) ]);

	$doc['_id'] = (string)$doc['_id']; // Convert MongoID Object to a string so that json_encode works

	echo "Read Document (3): " . json_encode($doc) . "<br>\n";


	// READ (Find All Documents, don't return some fields, sort assending by n)
	$cursor = $DB->{$dbColl}->find(
		[],
		[
	        'projection' => [
	            'password' => 0, // skip this field
	            'ssn' => 0, // skip this field
	        ],
	        'sort' => ['name' => 1]
	    ]
	);

 	foreach ($cursor as $doc) {
		$doc['_id'] = (string)$doc['_id']; // Convert MongoID Object to a string so that json_encode works
		echo "Read Document (4): " . json_encode($doc) . "<br>\n";
	}

	// COUNT number of Documents
	$count = $DB->{$dbColl}->count();
	echo "Count of Documents: " . $count . "<br>\n";


	// DELETE One Document
	$DB->{$dbColl}->deleteOne(['name' => 'Peter Griffin']);


	/* Helper function to check if a string is a valid MongoID (24 char hex) */
	function isValidMongoID($id){
		if ($id instanceof \MongoDB\BSON\ObjectID || preg_match('/^[a-f\d]{24}$/i', $id)) {
			return true;
		}else{
			return false;
		}
	}
?>
