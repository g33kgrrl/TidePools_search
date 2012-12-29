<?php

/***************************************************************
 * search.php
 * 
 * Lisa J. Lovchik
 * exexpat2@gmail.com
 *
 * Implements TidePools search functionality using MongoDB
 *     Searches by name, type, description, or location
 *
 ***************************************************************/

    try
    {
        // sanitize text box input       
        $sanTerm = substr($_POST['searchTerm'], 0, 40);
        $sanTerm = strip_tags($sanTerm);

        // open connection to MongoDB server
        $conn = new Mongo('localhost');

        // access database
        $db = $conn-> $_POST['database'];

        // set search key field
        $key = $conn-> $_POST['searchKey'];

        // access collection
        // allows for future expansion with multiple cities
        $collection = $db-> $_POST['coll'];
    
        echo '<h1>TidePools search</h1>';

        // display and execute selected query       
        switch ($key)
        {
            case "name":
            case "description":
            case "type":
                // set up name, description, and type searches by keyword
                $query = array(
                    "$key" => new MongoRegex(
                        '/' . $sanTerm . '/i'
                    )
                );
                echo '<h3>Locations with "' . $sanTerm . '" in their ' . $key . '</span></h3>';
                break;
            case "loc":
                // get starting point and radius
                // starting point is hard-coded into search.html for now
                // later, this will be handled by the map interface
                $sanTerm = (float) $sanTerm;
                $distanceUnits = $_POST['distUnits'];
                
                if ($distanceUnits == 'mi') {
                    // ~ 69 mi per 1 degree latitude or longitude
                    $maxDistance = (float) $sanTerm / 69;
                }
                else {
                    $distanceUnits = "km";
                    // ~111 km per 1 degree latitude or longitude
                    $maxDistance = (float) $sanTerm / 111;                    
                }
                  
                $lon = (float) $_POST['lon'];
                $lat = (float) $_POST['lat']; 
                $lonlat = array($lon, $lat);                      
                
                echo '<h3>Locations within ' . $sanTerm . ' '. 
                    $distanceUnits . ' of ' . $lon .  ' longitude, ' . 
                    $lat . ' latitude</span><br />';
                
                // set up location search as geospatial indexing search
                $query = array(
                    "$key" => array(
                        '$near' => $lonlat,
                        '$maxDistance' => $maxDistance
                        )
                    ); 
                break;
            case "time":
                // to be added in the future
                break;
            default:
                echo "<h2>Error - invalid search type</h2>";  
        }
        
        $cursor = $collection->find( $query );
        echo '<h2>' . $cursor->count() . ' document(s) found. </h2>';


        /*
        // FOR TESTING PURPOSES
        echo '<h3>First, the raw data:</h3>';

        while ( $cursor->hasNext() )
        {
            var_dump( $cursor->getNext() );
            echo "<br /><br />";
        }
        echo '<h3>Now, a little neater:</h3>'; 
        */


        // iterate through the result set
        // print each document
        foreach ($cursor as $obj)
        {
        echo 'Name: ' . $obj['name'] . '<br />';
        echo 'Time: ' . $obj['stats']['time']['when'] . '<br />';
        echo 'Location: ' . $obj['loc'][0] . ', ' . $obj['loc'][1] . '<br />';
        echo 'Description: ' . $obj['description'] . '<br />';
        echo 'Type: ' . $obj['type'] . '<br />';
        echo '<br />';
        }

        // disconnect from server
        $conn->close();
    }

    // throw error message if connection to DB fails    
    catch (MongoConnectionException $e)
    {
        die('Error connecting to MongoDB server');
    }
    catch (MongoException $e)
    {
        die('Error: ' . $e->getMessage());
    }
?>
