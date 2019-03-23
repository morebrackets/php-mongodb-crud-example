<?php
	// --------------------------------------------------------------------------------
	// github.com/morebrackets/php-mongodb-crud-example
	// No Copyright. Public Domain. Do anything you want with this code.
	// This example code will get you started with the basics of PHP & MongoDB
	// --------------------------------------------------------------------------------

	$dbHost='localhost'; // DB Server Host - This could also be 127.0.0.1 or a remore host/ip
	$dbPort=27017; // DB Server Port
	$dbName='testdb'; // Database where we will work
	$dbColl='testcoll'; // Collection (table) where we will work

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
	    echo 'Error: PHP cannot connect to MongoDB' . "<br>\n";
	    die();
	}

	// SELECT DB (shortcut for future reuse)
	$DB = $mdbClient->{$dbColl};


	// DEFINE an INDEX (not required)
	$DB->{$dbColl}->createIndex(array('n' => 1));


	// CREATE (Insert one record)
	// Note, this will fail and kill the entire script if an indexed unique field already exists. Use find first or upsert instead if possible.
	$result = $DB->{$dbColl}->insertOne(array('n' => 'Bob', 'p' => 123, 'f' => 'Pizza', 'a' => 'Hello'));
	echo "INSERTed MongoID '" . $result->getInsertedId() . "'.<br>\n";
	

	// CREATE/UPDATE (Upsert one record)
	$result = $DB->{$dbColl}->updateOne(
	    array('n' => 'Bob', 'p' => 123), // What document to find
	    array( 
	    	'$set' => array('n' => 'Bob', 'p' => 456, 'f' => 'Avocado'), // What document keys to update/insert
	    	'$unset' => array('a' => true,'b' => true), // Delete some keys
	    	'$inc' => array('u' => 1)  // Increment a key
	    ),
	    array('upsert' => true) // Enable upsert
	);

	if($result->getUpsertedCount()){
		// AN INSERT WAS PERFORMED (No existing document found)
		echo "An INSERT was performed resulting in new MongoID '" . $result->getUpsertedId() . "'.<br>\n";
	}else{
		// AN UPDATE WAS PERFORMED (Document alreay existed)
		// Note: You can do a new find command here to find the _id or use findAndModify instead of upsert
		echo "An UPDATE was performed.<br>\n";
	}


	// UPDATE (One Record)
	$DB->{$dbColl}->updateOne(
		array('n' => 'Bob' ),
		array('$set' => array('f' => 789))
	);

	// READ (Find One Record)
	$doc = $DB->{$dbColl}->findOne(array('n' => 'Bob'));

	$doc['_id'] = (string)$doc['_id']; // Convert MongoID Object to a string so that json_encode works :)

	echo "Read Document: " . json_encode($doc) . "<br>\n";

	// Check if it is a correctly formatted MongoID
	if(isValidMongoID($doc['_id'])){
		echo "MongoID '".$doc['_id']."' is valid.<br>\n";
	}else{
		echo "MongoID '".$doc['_id']."' is NOT valid.<br>\n";
	}

	// DELETE
	$DB->{$dbColl}->deleteOne(array('n' => 'Bob'));


	/* Helper function to check if a string is a valid MongoID (24 char hex) */
	function isValidMongoID($id){
		if ($id instanceof \MongoDB\BSON\ObjectID || preg_match('/^[a-f\d]{24}$/i', $id)) {
			return true;
		}else{
			return false;
		}
	}
?>
