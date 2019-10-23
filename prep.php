<?php

include('common.php');
mb_internal_encoding("UTF-8");
ini_set('memory_limit','512M');

/*******************************************************************
 *
 * This script will prepare recipes to be ready to import into the
 * recipes table in the foodgeeks_kohana database.
 *
 * This will not properly import images or match recipe types. It just 
 * normalizes recipe data.
 *
 *******************************************************************/ 
 

/**
* Insert a new publisher recipe, which has been prepped for import, into the
* recipes_prepped_for_import table.
*
* @param    object  The $mysql object
* @param    array   The newly prepped $recipe array
* @return 	void    
*/
function insert_recipe($mysqli, $recipe) {
    if (isset($recipe['title']) && !empty($recipe['title']) && isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
        if (!does_recipe_exist($mysqli, $recipe['publisher_recipe_id'])) {
            $sql = "
                INSERT INTO recipes_prepped_for_import (    
                    publisher_recipe_id,
                    publisher_id, 
                    title, 
                    teaser,
                    ingredients,
                    directions,
                    serving_qty,
                    serving_to_qty,
                    serving_type,
                    prep_time,
                    overall_time,
                    external_url,
                    external_photo_url,                
                    status,
                    created,
                    modified
                ) VALUES (
                    '".$mysqli->real_escape_string($recipe['publisher_recipe_id'])."',
                    '".$mysqli->real_escape_string($recipe['publisher_id'])."',
                    '".$mysqli->real_escape_string($recipe['title'])."',
                    '".$mysqli->real_escape_string($recipe['teaser'])."',
                    '".$mysqli->real_escape_string($recipe['ingredients'])."',
                    '".$mysqli->real_escape_string($recipe['directions'])."',
                    '".$mysqli->real_escape_string($recipe['serving_qty'])."',
                    '".$mysqli->real_escape_string($recipe['serving_to_qty'])."',
                    '".$mysqli->real_escape_string($recipe['serving_type'])."',
                    '".$mysqli->real_escape_string($recipe['prep_time'])."',
                    '".$mysqli->real_escape_string($recipe['overall_time'])."',                                                                                
                    '".$mysqli->real_escape_string($recipe['external_url'])."',  
                    '".$mysqli->real_escape_string($recipe['external_photo_url'])."',                                                                                                
                    '".$mysqli->real_escape_string($recipe['status'])."',                                                                                                                
                    '".$mysqli->real_escape_string($recipe['created'])."',
                    '".$mysqli->real_escape_string($recipe['modified'])."'                        
                )
            ";
            $mysqli->query($sql);
            if (!empty($mysqli->error)) {
                echo $mysqli->error;
            } else {
                $recipe['id'] = $mysqli->insert_id;            
                echo "\r\n *************************************";
                echo "\r\n * Publisher Recipe #" . $recipe['publisher_recipe_id']  . " - ". $recipe['title'] . " inserted into the database ";
                echo "\r\n * ";
                echo "\r\n * Original URL was " . $recipe['external_url'];
                echo "\r\n ************************************* \r\n";
            }
        } else {
            // recipe exists. update it.
            update_recipe($mysqli, $recipe);
        }
    } else {
        echo "\r\n *************************************";
        echo "\r\n * Recipe schema found for Publisher Recipe #" . $recipe['publisher_recipe_id'] . ", but recipe title or ingredients are missing. Import failed.";
        echo "\r\n ************************************* \r\n";
    }
}

/**
* Update an existing publisher recipe in the recipes_prepped_for_import table.
*
* @param    object  The $mysql object
* @param    array   The newly prepped $recipe array
* @return 	void
*/
function update_recipe($mysqli, $recipe) {
    $sql = "
        UPDATE recipes_prepped_for_import
        SET 
            title = '".$mysqli->real_escape_string($recipe['title'])."',
            ingredients = '".$mysqli->real_escape_string($recipe['ingredients'])."',
            directions = '".$mysqli->real_escape_string($recipe['directions'])."',
            serving_qty = '".$mysqli->real_escape_string($recipe['serving_qty'])."',
            serving_to_qty = '".$mysqli->real_escape_string($recipe['serving_to_qty'])."',
            serving_type = '".$mysqli->real_escape_string($recipe['serving_type'])."',
            prep_time = '".$mysqli->real_escape_string($recipe['prep_time'])."',
            overall_time = '".$mysqli->real_escape_string($recipe['overall_time'])."',
            external_url = '".$mysqli->real_escape_string($recipe['external_url'])."',
            external_photo_url = '".$mysqli->real_escape_string($recipe['external_photo_url'])."',
            modified = '".$mysqli->real_escape_string(time())."'
        WHERE publisher_recipe_id = '".$mysqli->real_escape_string($recipe['publisher_recipe_id'])."'
    ";
    $mysqli->query($sql);
    if (!empty($mysqli->error)) {
        echo $mysqli->error;
    } else {
        echo "\r\n *************************************";
        echo "\r\n * Publisher Recipe #" . $recipe['publisher_recipe_id']  . " - \"". $recipe['title'] . "\" updated in the database. ";
        echo "\r\n * ";
        echo "\r\n * Original URL was " . $recipe['external_url'];
        echo "\r\n ************************************* \r\n";
    }
}

/**
* Determine if a recipe with this URL already exists in the recipes_prepped_for_import table.
*
* @param    object  The $mysql object
* @param    string  The $url for this recipe
* @return 	bool    true if it is a duplicate; false if it is not a duplicate;
*/ 
function is_url_duplicate($mysqli, $url) {
    $sql = "
        SELECT publisher_recipe_id 
        FROM recipes_prepped_for_import 
        WHERE external_url  = '".$mysqli->real_escape_string($url)."'
    ";
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return true;
        }
    }
    return false;
}

/**
* Determine if a recipe exists in the recipes_prepped_for_import table.
*
* @param    object  The $mysql object
* @param    int     The $publisher_recipe_id for a recipe
* @return 	bool    Return true if this $publisher_recipe_id exists; false if not
*/ 
function does_recipe_exist($mysqli, $publisher_recipe_id) {
    $sql = "
        SELECT publisher_recipe_id 
        FROM recipes_prepped_for_import 
        WHERE publisher_recipe_id  = '".$mysqli->real_escape_string($publisher_recipe_id)."'
    ";
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return true;
        }
    }
    return false;
}

/**
* Get a specific publisher_recipe
*
* @param    object  The $mysql object
* @param    int     The $id for this publisher_recipe
* @return 	array   An array of recipes
*/ 
function get_publisher_recipe($mysqli, $publisher_recipe_id) {
    $sql = "
        SELECT *
        FROM publisher_recipes
        WHERE id = '".$mysqli->real_escape_string($publisher_recipe_id)."'";
    $results = $mysqli->query($sql);
    if ($results = $mysqli->query($sql)) {
        $recipes = array();
        while ($row = $results->fetch_assoc()) {
            return $row;
        }
    } 
        
    return false;
}

/**
* Get all of the publisher recipes that have not yet been prepped for import.
*
* @param    object  The $mysql object
* @return 	array   An array of recipes
*/ 
function get_publisher_recipes($mysqli) {
    $sql = '
        SELECT pr.id 
        FROM publisher_recipes pr
        LEFT JOIN publishers p ON p.id = pr.publisher_id
        WHERE p.status = 1
        AND (
            pr.prepped_for_import <= '.THIRTY_DAYS_AGO.' OR
            pr.scraper_version = '.SCRAPER_VERSION.'
        )
        AND pr.unsupported_language = 0 
        ORDER BY pr.id ASC
    ';
    $results = $mysqli->query($sql);
    if ($results = $mysqli->query($sql)) {
        $recipes = array();
        while ($row = $results->fetch_assoc()) {
            $recipes[] = $row;
        }
        return $recipes;
    } 
        
    return false;
}

// Prep the recipe array with all the things we can match really easily.
function prep_recipe($publisher_recipe) {
    $timestamp = time();

    $recipe = array(
        'publisher_recipe_id' => intval($publisher_recipe['id']),
        'publisher_id' => intval($publisher_recipe['publisher_id']),
        'title' => trim(strip_tags($publisher_recipe['title'])),
        'teaser' => trim(strip_tags($publisher_recipe['teaser'])),
        'ingredients' => trim($publisher_recipe['ingredients']),
        'directions' => trim(strip_tags($publisher_recipe['instructions'])),
        'serving_qty' => null,
        'serving_to_qty' => null,
        'serving_type' => null,
        'prep_day' => null,
        'prep_hour' => null,
        'prep_minute' => null,
        'prep_time' => null,
        'overall_day' => null,
        'overall_hour' => null,
        'overall_minute' => null,
        'overall_time' => null,
        'external_url' => $publisher_recipe['url'],
        'external_photo_url' => null,
        'status' => 1,
        'created' => $timestamp,
        'modified' => $timestamp        
    );

    return $recipe;
}

// Parse out the Foodgeeks recipe times from the ISO 8601 duration time found in the recipe.
function parse_iso_8601($iso_duration) {
    $times = array(
        'year' => 0,
        'month' => 0,
        'day' => 0,
        'hour' => 0,
        'minute' => 0,
        'second' => 0    
    );
    
    $matches = array();
    preg_match('/^(-|)?P([0-9]+Y|)?([0-9]+M|)?([0-9]+D|)?T?([0-9]+H|)?([0-9]+M|)?([0-9]+S|)?$/', $iso_duration, $matches);
    if (!empty($matches)){       

        // Strip all but digits and -
        foreach($matches as &$match){
            $match = preg_replace('/((?!([0-9]|-)).)*/', '', $match);
        }   

        // Fetch duration parts
        $times = array(
            'year' => intval(str_replace("Y", "", $matches[2])),
            'month' => intval(str_replace("M", "", $matches[3])),
            'day' => intval(str_replace("D", "", $matches[4])),
            'hour' => intval(str_replace("H", "", $matches[5])),
            'minute' => intval(str_replace("M", "", $matches[6])),
            'second' => intval(str_replace("S", "", $matches[7]))                        
        );
    } 

    // If they didn't use an 8601 time format, let's see if we can parse out the hour and minutes
    elseif (strstr($iso_duration, "hour") || strstr($iso_duration, "hr") || strstr($iso_duration, "minute") || strstr($iso_duration, "min")) {
        $pieces = explode(" ", $iso_duration);
        $previous_piece = '';
        foreach ($pieces as $piece) {
            if (!empty($previous_piece) && (strstr($piece, "day"))) {
                $times['day'] = $previous_piece;
            }
            if (!empty($previous_piece) && (strstr($piece, "hr") || strstr($piece, "hr"))) {
                $times['hour'] = $previous_piece;
            }
            else if (!empty($previous_piece) && (strstr($piece, "min") || strstr($piece, "mn"))) {
                $times['minute'] = $previous_piece;
            }
            
            $previous_piece = $pieces[0];
        }
    } 
    
    return $times;
}

// Prepare the recipe duration times.
function prep_times($original_recipe, $new_recipe) {
    if (isset($original_recipe['prep_time']) && !empty($original_recipe['prep_time'])) {
        $times = parse_iso_8601($original_recipe['prep_time']);
        $new_recipe['prep_time'] = ($times['day'] * 24 * 60) + ($times['hour'] * 60) + ($times['minute'] * 1);
    }
    
    if (isset($original_recipe['cook_time']) && !empty($original_recipe['cook_time'])) {
        $times = parse_iso_8601($original_recipe['cook_time']);
        $new_recipe['cook_time'] = ($times['day'] * 24 * 60) + ($times['hour'] * 60) + ($times['minute'] * 1);
    }

    if (isset($original_recipe['total_time']) && !empty($original_recipe['total_time'])) {
        $times = parse_iso_8601($original_recipe['total_time']);
        $new_recipe['overall_time'] = ($times['day'] * 24 * 60) + ($times['hour'] * 60) + ($times['minute'] * 1);
    } else if ((isset($new_recipe['prep_time']) && !empty($new_recipe['prep_time'])) || (isset($new_recipe['cook_time']) && !empty($new_recipe['cook_time']))) {
        if (!empty($new_recipe['prep_time'])) {
            $new_recipe['overall_time'] = $new_recipe['overall_time'] + $new_recipe['prep_time'];
        }
        if (!empty($new_recipe['cook_time'])) {
            $new_recipe['overall_time'] = $new_recipe['overall_time'] + $new_recipe['cook_time'];
        }        
    }
    
    return $new_recipe;
}

// Fetch the available serving types
/*
mysql> select * from serving_types;
+----+-----------+------------+
| id | singular  | plural     |
+----+-----------+------------+
|  1 |           |            |
|  2 | appetizer | appetizers |
|  3 | batch     | batches    |
|  4 | cake      | cakes      |
|  5 | cup       | cups       |
|  6 | quart     | quarts     |
|  7 | dozen     | dozen      |
|  8 | gallon    | gallons    |
|  9 | jar       | jars       |
| 10 | liter     | liters     |
| 11 | loaf      | loaves     |
| 12 | N/A       | N/A        |
| 13 | package   | packages   |
| 14 | pie       | pies       |
| 15 | pint      | pints      |
| 16 | pound     | pounds     |
| 17 | quart     | quarts     |
| 18 | recipe    | recipes    |
| 19 | serving   | servings   |
| 20 | piece     | pieces     |
+----+-----------+------------+
*/
function get_serving_types($mysqli) {
    $sql = "SELECT * FROM sandbox_foodgeeks_kohana.serving_types ORDER BY id ASC";
    if ($results = $mysqli->query($sql)) {
        $serving_types = array();
        while ($row = $results->fetch_assoc()) {
            $serving_types[] = $row;
        }
        return $serving_types;
    }
    return false;
}

// Prepare the recipe duration times. This is what we're trying to normalize. Yaaaay.
/*
| 48                                                                |
| 1 cocktail                                                        |
| 20 to 24 bars                                                     |
| One loaf                                                          |
| One loaf (8 to 10 servings)                                       |
| 4 Servings                                                        |
| 6 to 8 servings                                                   |
| 6-8 servings                                                      |
| 24 cookies                                                        |
| 9 regular-sized muffins                                           |
| About 4 cups                                                      |
| 4 small burgers                                                   |
| 6 servings (4 shells per serving)                                 |
| 12 (1st course) or 6 (main course)                                |
| 4 servings (1 cup pasta and 3/4 cup sauce)                        |
| 14 servings (3 1/2 quarts)                                        |
| 6 servings (1 1/2 cups per serving)                               |
| 2 cups (10 to 12 servings)                                        |
| 28 cookies                                                        |
| Enough for 1 pound of pasta                                       |
| 12 servings (Serving size: 3 ounces pork, 1/4 cup sauce)          |
| 12 kebabs                                                         |
| 2 cups                                                            |
| 2 dozen sticky buns                                               |
| 1 cup                                                             |
| About 25 cookies                                                  |
| 1 1/2 cups (2 Tablespoons per serving)                            |
| between 3 and 4 quarts                                            |
| Makes 400ml                                                       |
| Makes 290ml/½ pint                                                |
| 4 servings (2 cups per serving)                                   |
*/
function prep_yield($mysqli, $yield, $new_recipe) {

    // get ride of white space and turn to lower case
    $yield = trim(strtolower($yield));

    // drop the period off the end of the string
    $yield = rtrim($yield, ".");

    // if it says "enough for", dump it. "Enough for 1 turkey" doesn't mean jack about the yield
    if (strstr($yield, "enough for")) {
        return $new_recipe;
    }
    
    // if it says "day", "hour" or "minute", wrong data! Dump it!
    $times = array("day", "hour", "minute");
    foreach ($times as $time) {
        if (strstr($yield, $time)) {
            return $new_recipe;
        }
    }
    
    // change numeric words to numbers, e.g. "Five to Six" -> "5 to 6"
    $numeric_words = array(
        'one' => 1, 
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
        'ten' => 10,
    );
    foreach ($numeric_words as $word => $number) {
        $yield = trim(str_replace($word, $number, $yield));        
    }
    
    // Update half pint wording so that it can be matched to its serving type.
    $half_pint_words = array("half pint");
    $yield = trim(str_replace($half_pint_words, "half-pint", $yield));
    
    // drop these words if you find them in the $yield string, 
    $drop_words = array(
        "about", "approx.", "approximately", "approx", "assorted", "+", "serves/makes:", "makes", 
        'as part of multicourse meal', 'as a main course', 'as a starter', 'as a side dish', 'as a main', 'as a side',
        "regular-sized", "small", "medium", "large", "smaller", "larger", "big",
        "small-ish", "medium-ish", "large-ish", "jumbo", "giant", "mini", "standard", "rectangular", "heaping", "individual",
        "glamorous", "auspicious", "generously", "generous",
        "guests", "children",
        "lumps of", "with loads of extra butter",
        "‘", "’"
    );
    $yield = trim(str_replace($drop_words, "", $yield));
    
    // Strip out all non-ascii characters
    $yield = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $yield);
    
    // Ditch everything after the first detected parenthesis "6 servings (1 cup per serving)" 
    if (strstr($yield, "(")) {
        $pieces = explode("(", $yield);
        $yield = trim($pieces[0]);
    }
    
    // Ditch everything after the first detected "or" e.g. "2 servings or 3 cups"
    if (strstr($yield, "(") || strstr($yield, " or ")) {
        $pieces = explode(" or ", $yield);
        $yield = trim($pieces[0]);
    }
    
    // Ditch everything after the first detected comma "or" e.g. "2 servings or 3 cups"
    if (strstr($yield, "(") || strstr($yield, " or ")) {
        $pieces = explode(" or ", $yield);
        $yield = trim($pieces[0]);
    }
    
    // Ditch everything after the first detected instance of "with"
    if (strstr($yield, "with")) {
        $pieces = explode(" with ", $yield);
        $yield = trim($pieces[0]);
    }
    
    // Change these words into "serving(s)"
    $servings_words = array(
        'biscotti', 'biscuit', 'blondie', 'bread roll', 'brownie', 'burrito', 'sticky bun', 'bun', 'burger', 'butterfly cake',
        'cake ball', 'cocktail', 'cookie', 'corn muffin', 'crepe',
        'donut', 'doughnut',
        'éclair', 'eclair',
        'fajita',
        'kebab', 
        'macaroon', 'madeleine', 'meatball', 'melt', 'meringue', 'muffin', 'munchie',
        'pancake', 'pastry', 'pastrie', 'pasty', 'pastie', 'pattie', 'patty', 'puff', 
        'quesadilla',
        'roll',
        'salmon cake', 'sandwich', 'scone', 'servinge', 'slice', 'smoothie', 'square',
        'taco', 'toffee apple', 'truffle', 'turnover',
        'walnut whip', 'whoopie pie'
    );
    $yield = trim(str_replace($servings_words, "serving", $yield));

    // if you see "serve" or "serves", drop it, and place "servings" on the end
    if (strstr($yield, "serves")) {
        $yield = trim(str_replace("serves", "", $yield)) . " servings";
    }
    
    // replace "n-n" and "n - n" with "n to n"
    $yield = preg_replace('/(?<=[0-9])-(?=[0-9])/i', ' to ', $yield);
    $yield = preg_replace('/(?<=[0-9]) - (?=[0-9])/i', ' to ', $yield);
    $yield = preg_replace('/(?<=[0-9])–(?=[0-9])/i', ' to ', $yield);
    $yield = trim($yield);

    // If this is an integer, just return that as x servings
    if (is_numeric($yield)) {
        $new_recipe['serving_qty'] = intval($yield);
        $new_recipe['serving_type'] = 19;  // serving_type 19 = 'serving
    }

    // separate "6 to 8 servings" into $serving_qty = 6 and $serving_to_qty = 8
    if (strstr($yield, " ")) {
        $pieces = explode(" ", $yield);
        if (is_numeric($pieces[0])) {
            $new_recipe['serving_qty'] = $pieces[0];
        }

        if (trim($pieces[1]) == 'to' && is_numeric($pieces[2])) {
            $new_recipe['serving_to_qty'] = $pieces[2];
        }
    }
        
    // match words with our serving types dictionary
    if (strstr($yield, 'serving')) {
        $new_recipe['serving_type'] = 19; // serving_type 19 = 'serving
    } else {
        $serving_types = get_serving_types($mysqli);
        foreach ($serving_types as $serving_type) {
            if (!empty($serving_type['singular']) && !empty($serving_type['plural'])) {
                if (strstr($yield, $serving_type['singular']) || strstr($yield, $serving_type['plural'])) {
                    $new_recipe['serving_type'] = $serving_type['id'];
                }
            }
        }        
    }

    return $new_recipe;
}

// $publisher_recipe['photo_url']
function prep_photo($new_recipe, $publisher_recipe_id, $external_photo_url=null) {
    if (isset($external_photo_url) && !empty($external_photo_url)) {
        $s3_file = $publisher_recipe_id . '.jpg';
        $s3_address = 'https://s3.amazonaws.com/foodgeeks/recipes/publishers/';
        $new_recipe['external_photo_url'] = $s3_address . $s3_file;
    }
    return $new_recipe;
}

/**
* Normalize a list of ingredients by standardizing the format of measurements.
*
* @param    string  The $text for this recipe or recipe type
* @return 	string  The normalized $text
*/
function _normalize_ingredients_measurements ($text) {
    
    $text = str_replace(
        array(
            " bn. ",  " bn.) ", " bn ",  " bn) ",            
            " c ",  " c. ", " C ", " C. ", " c) ", " c.) ", " C) ", " C.) ",
            " cn ",  " cn) ", " cn. ",  " cn.) ",
            " ctn ", " ctn) ", " ctn. ",  " ctn.) ",
            " dr ",  " dr) ", " dr. ",  " dr.) ",
            " ds ",  " ds) ", " ds. ",  " ds.) ",
            " ea ", " ea) ",
            " g ",  " g) ", " grams ",  " gram ", " grams) ", " gram) ", " gm ",  " gm) ", " gms ", " gms) ",
            " ga ", " ga. ", " ga) ", " ga.) ",
            " fl oz ", " fl ", " fl oz) ", " fl) ",    
            " liters ",  " liters)", " liter ",  " liter)", " l ",  " l)", 
            " lb ", " lb)", " lbs ", " lbs)", " pounds ", " pounds)", " pound ", " pound)",  
            " kg ", " kg)", " kgs. ", " kgs.)",  " kgs ", " kgs)",  
            " ml ", " ml)", " mls. ", " mls.)",  " mls ", " mls)", 
            " OZ ", " OZ)", " OZ. ", " OZ.)", " oz ", " oz)", " ounce ", " ounce)", " ounces ", " ounces)",
            " packages ", " packages) ", " package ", " package) ", " pk ", " pk) ", " pk. ", " pk.) ", " pkgs ", " pkgs) ", " pkgs. ", " pkgs.) ", 
            " packets ", " packets) ", " packet ", " packet) ", " pkt ", " pkt) ", " pkt. ", " pkt.) ", " pkts ", " pkts) ", " pkts. ", " pkts.) ",
            " pt ", " pt) ", " pt. ", " pt.) ", " pts ", " pts) ", " pts. ", " pts.) ",
            " qt ", " qt) ", " qts ", " qts) ", " quart ", " quart) ", " quarts ", " quarts) ", 
            " size ", " size) ",
            " sl ", " sl) ", " sl. ", " sl.) ",
            
            " T ", " T) ", " T. ", " T.) ", // This is the reason we can't do str_ireplace
            " Tablespoons ", " Tablespoons) ", " Tablespoon ", " Tablespoon) ",            
            " tablespoons ", " tablespoons) ", " tablespoon ", " tablespoon) ",            
            " Tb ", " Tb) ", " Tb. ", " Tb.) ",
            " tb ", " tb) ", " tb. ", " tb.) ",
            " Tbs ", " Tbs) ", " Tbs. ", " Tbs.) ", 
            " tbs ", " tbs) ", " tbs. ", " tbs.) ",          
            " Tbsp ", " Tbsp) ", " Tbsp. ", " Tbsp.) ",
            " Tbl ", " Tbl) ", " Tbl. ", " Tbl.) ",
            " tbl ", " tbl) ", " tbl. ", " tbl.) ",
            " Tbls ", " Tbls) ", " Tbls. ", " Tbls.) ",
            " tbls ", " tbls) ", " tbls. ", " tbls.) ",
            " Tblsp ", " Tblsp) ", " Tblsp. ", " Tblsp.) ",
            " tblsp ", " tblsp) ", " tblsp. ", " tblsp.) ",
            " tbsp ", " tbsp) ",            
            
            " t ", " t) ", " t. ", " t.) ", // This is the reason we can't do str_ireplace           
            " teas. ", " teas.)", " teas. ", " teas.) ", // Can't replace " tea " or " teas "
            " Teaspoons ", " Teaspoons) ", " Teaspoon ", " Teaspoon) ",
            " teaspoons ", " teaspoons) ", " teaspoon ", " teaspoon) ",
            " Ts ", " Ts) ", " Ts. ", " Ts.) ",
            " ts ", " ts) ", " Ts. ", " Ts.) ",            
            " Tsp ", " Tsp) ", " Tsp. ", " Tsp.) ",            
            " tsp ", " tsp) ",
            
            " tub ", " tub) ", " tubs ", " tubs) ",
            
            " x ", " x) ", " x. ", " x.) ",
        ), 
        array(
            " bunch ", " bunch) ", " bunch ", " bunch) ",
            " cup ", " cup ", " cup ", " cup ", " cup) ", " cup) ", " cup) ", " cup) ",
            " can ", " can) ", " can ",  " can) ",
            " carton ", " carton) ", " carton ",  " carton) ",
            " drop ",  " drop) ", " drop ",  " drop) ",
            " dash ",  " dash) ", " dash ",  " dash) ",            
            " ", ") ",            
            " g. ",  " g.) ", " g. ",  " g. ", " g.) ",  " g.) ", " g. ",  " g.) ", " g. ", " g.) ",
            " gal. ", " gal. ",  " gal.) ", " gal.) ", 
            " fl. oz. ", " fl. oz. ", " fl. oz.) ", " fl. oz.) ",
            " l. ",  " l.)", " l. ",  " l.)", " l. ",  " l.)",
            " lb. ", " lb.)", " lb. ", " lb.)", " lb. ", " lb.)", " lb. ", " lb.)", 
            " kg. ", " kg.)", " kg. ", " kg.)", " kg. ", " kg.)", 
            " ml. ", " ml.)", " ml. ", " ml.)", " ml. ", " ml.)",
            " oz. ", " oz.)", " oz. ", " oz.)", " oz. ", " oz.)", " oz. ", " oz.)", " oz. ", " oz.)", 
            " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ",
            " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ", " pkg. ", " pkg.) ",             
            " pint ", " pint) ", " pint ", " pint) ", " pints ", " pints) ", " pints ", " pints) ",
            " qt. ", " qt.) ", " qt. ", " qt.) ", " qt. ", " qt.) ", " qt. ", " qt.) ",
            " ", ") ",
            " slice ", " slice) ", " slices ", " slices) ",
                    
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ", " tbsp. ", " tbsp.) ",  
            " tbsp. ", " tbsp.) ",  
                                             
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ", " tsp. ", " tsp.) ",
            " tsp. ", " tsp.) ",
            
            " container ", " container) ", " containers ", " containers) ",            
            
            " ", ") ", " ", ") ",            
        ), 
        $text);
        
    return $text;
}

/**
* Normalize a list of ingredients by removing tags.
*
* @param    string  The $text for this recipe or recipe type
* @return 	string  The normalized $text
*/
function _normalize_ingredients_remove_tags ($text) {
    // Turn "</li><li>" into /r/n (Mainly for River Cottage)
    $text = str_replace("</li><li>", "\r\n", $text);
    
    // Strip the HTML tags
    $text = strip_tags($text);
    
    return $text;
}

/**
* Normalize a list of ingredients by removing unwanted words.
*
* @param    string  The $text for this recipe or recipe type
* @return 	string  The normalized $text
*/
function _normalize_ingredients_remove_words ($text) {

    // Remove large, medium, small, extra large and extra small from ingredients
    $text = str_replace(
        array(
            " large ", " lg. ", " lg ",
            " medium ", " md. ", " md ", " med. ", " med ",
            " small ", " sm. ", " sm ", 
            " extra large ", " extra-large ", " x-lg. ", " xl ", 
            " extra small ", " extra-small ", " x-sm. ", " xs "
        ), " ", $text);    
    
    // Remove words that are ambiguous, lead to uncertainty, or refer to bits on the authoritative recipe but cannot be found here.
    $text = str_replace(
        array(
            "ingredients:", "ingredients",
            "about", "almost",
            "(see note)", "see note",
        ), 
        "", $text);
        
    // Clean up any remaining issues
    $text = str_replace(
        array(
            "( ", " & ",
        ),
        array (
            "(", " and ",
        ),
        $text);
        
    return $text;
}

/**
* Normalize a list of ingredients by removing unwanted words.
*
* @param    string  The $text for this recipe or recipe type
* @return 	string  The normalized $text
*/
function _normalize_ingredients_clean_up_fractions ($text) {
  
    // Replace all ¼ fractions with decimals to cleanse numbers for the db
    // Yes, the slashes in "1/8" are different than the slashes in "1/8" (Unicode) which is why they are repeated.
    $text = str_replace(array(" ⅛", "⅛", " 1/8", "-1/8", "1/8", " 1/8", "1/8", " 1⁄8", "1⁄8"), ".125", $text);    
    $text = str_replace(array(" ¼", "¼", " 1/4", "-1/4", "1/4", " 1/4", "1/4", " 1⁄4", "1⁄4"), ".25",  $text);
    $text = str_replace(array(" ⅓", "⅓", " 1/3", "-1/3", "1/3", " 1/3", "1/3", " 1⁄3", "1⁄3"), ".33",  $text);    
    $text = str_replace(array(" ⅜", "⅜", " 3/8", "-3/8", "3/8", " 3/8", "3/8", " 3⁄8", "3⁄8"), ".375", $text);    
    $text = str_replace(array(" ½", "½", " 1/2", "-1/2", "1/2", " 1/2", "1/2", " 1⁄2", "1⁄2"), ".5",   $text);
    $text = str_replace(array(" ⅝", "⅝", " 5/8", "-5/8", "5/8", " 5/8", "5/8", " 5⁄8", "5⁄8"), ".625", $text);    
    $text = str_replace(array(" ⅔", "⅔", " 2/3", "-2/3", "2/3", " 2/3", "2/3", " 2⁄3", "2⁄3"), ".67",  $text);    
    $text = str_replace(array(" ¾", "¾", " 3/4", "-3/4", "3/4", " 3/4", "3/4", " 3⁄4", "3⁄4"), ".75",  $text);
    $text = str_replace(array(" ⅞", "⅞", " 7/8", "-7/8", "7/8", " 7/8", "7/8", " 7⁄8", "7⁄8"), ".875", $text);    
    $text = str_replace("* ", "", $text);
    
    return $text;
}

// Clean up the ingredients
function prep_ingredients($mysqli, $new_recipe) {

    // Clean up fractions before proceeding
    $new_recipe['ingredients'] = _normalize_ingredients_clean_up_fractions($new_recipe['ingredients']);

    // BBC - Uses metric and imperial for each measurement ("900g/2lb fresh cockles")
    if ($new_recipe['publisher_id'] == 4) {
        $new_recipe['ingredients'] = str_ireplace(
            array("fl oz ", "lbs ", "lb ", "oz ", "pint ", "pints ", "ingredients"),
            "",
            $new_recipe['ingredients']
        );
        
        // 1) Turn "150ml/5½" into "150ml". 
        // 2) Turn "25g/1oz" into "25g". 
        // 3) Turn "130-175ml/4-6fl oz" into "130-175ml".
        $new_recipe['ingredients'] = preg_replace(
            array(
                "(\/(?:\d+)(?:[.-][0-9]{1,3})?)",
                "(\/(?:\.[0-9]{1,3})?)"
            ), " ", $new_recipe['ingredients']);
    }
    
    // Turn "90ml" to "90 ml", mainly for BBC recipes but might have applications elsewhere
    $new_recipe['ingredients'] = preg_replace('/(?<=[a-z])(?=\d)|(?<=\d)(?=[a-z])/i', ' ', $new_recipe['ingredients']);

    // Turn "{number}-{letter}" into "{number} {letter}"
    $new_recipe['ingredients'] = preg_replace('/(?<=[0-9])-(?=[a-z])/i', ' ', $new_recipe['ingredients']);
        
    // Remove / Change words from the ingredients list
    $new_recipe['ingredients'] = _normalize_ingredients_measurements($new_recipe['ingredients']);
    $new_recipe['ingredients'] = _normalize_ingredients_remove_tags($new_recipe['ingredients']);
    $new_recipe['ingredients'] = _normalize_ingredients_remove_words($new_recipe['ingredients']);
    $new_recipe['ingredients'] = _normalize_quotes($new_recipe['ingredients']);
    $new_recipe['ingredients'] = _normalize_spelling_changes($mysqli, $new_recipe['ingredients']);

    return $new_recipe;
}

// Mark the original publisher recipe in the publisher_recipes table as prepped for import
function update_publisher_recipe($mysqli, $id) {
    $sql = "
        UPDATE publisher_recipes 
        SET prepped_for_import = ".time()."
        WHERE id = '".$mysqli->real_escape_string($id)."'";
    $mysqli->query($sql);
    if (!empty($mysqli->error)) {
        echo $mysqli->error;
    }
}

// Mark the original recipe in the publisher_recipes table as not being supported
function mark_publisher_recipe_as_unsupported($mysqli, $id) {
    $sql = "
        UPDATE publisher_recipes 
        SET unsupported_language = 1
        WHERE id = '".$mysqli->real_escape_string($id)."'";
    $mysqli->query($sql);
    if (!empty($mysqli->error)) {
        echo $mysqli->error;
    }
}

/**
* Normalize any conjunctions found within a recipe title.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_title_conjunctions ($title) 
{
    $title = str_replace(
        array("·", "–", " & ",   " + ",   " 'n " ,  " 'n' ", " n' ",  " 'N " ,  " 'N' ", " N' ",  " And ", " AND ", " Or ", " OR ", " For ", " FOR ", " Yet ", " YET "),
        array("-", "-", " and ", " and ", " and " , " and ", " and ", " and ", " and " , " and ", " and ", " and ", " Or ", " or ", " for ", " for ", " yet ", " yet "), 
        $title);

    return $title;
}

/**
* Normalize the recipe title and the recipe type title.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_title_prepositions ($title) 
{
    // Convert particular words to lower case
    $title = str_replace(array(
            " A ", " About ", " ABOUT ", " Ala ", " A la ", " Alla ", " An ", " AN ", " As ", " AS ", " At ", " AT ", " Atop ", " ATOP ", " Avec ", " AVEC ",
            " But ", " BUT ", " By ", " BY ", 
            " Con ", " CON ", 
	        " De ", " DE ", " Di ", " DI ",
	        " E ", " El ", " EL ", " En ", " EN ",
            " For ", " FOR ", " From ", " FROM ", 
            " Gli ", " GLI ",
            " I ", " In ", " IN ", " Into ", " INTO ", 
            " Like ", " LIKE ",                 
            " O ", " o' ", " O' ", " Of ", " OF ", " Off ", " OFF ", " On ", " ON ", " Over ", " OVER ",
            " Sans ", " SANS ", " Senza ", " SENZA ", " Sin ", " SIN ",
            " The ", " THE ", " To ", " TO ", 
            " With ", " WITH ", " w/ ", " W/ ", 
            " Within ", " WITHIN ", " w/i ", " W/I ", " w/in ", " W/IN ", " W/In ", 
            " w/o ", " w/O ", " Without ", " WITHOUT ",
            " Y "
        ), array(
            " a ", " about ", " about ", " a la ", " a la ", " alla ", " an ", " an ", " as ", " as ", " at ", " at ", " atop ", " atop ", " avec ", " avec ", 
            " but ", " but ", " by ", " by ", 
            " con ", " con ", 
	        " de ", " de ", " di ", " di ",
	        " e ", " el ", " el ", " en ", " en ",
            " for ", " for ", " from ", " from ", 
            " gli ", " gli ",            
            " i ", " in ", " in ", " into ", " into ", 
            " like ", " like ",
            " o ", " of ", " of ", " of ", " of ", " off ", " off ", " on ", " on ", " over ", " over ",
            " sans ", " sans ", " senza ", " senza ", " sin ", " sin ", 
            " the ", " the ", " to ", " to ", 
            " with ", " with ", " with ", " with ", 
            " within ", " within ", " within ", " within ", " within ", " within ", " within ", 
            " without ", " without ", " without ", " without ",
            " y "
        ), $title);

    return $title;
}

/**
* Normalize any quotation marks found within a recipe title.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_quotes ($title) {
    $chr_map = array(
       // Windows codepage 1252
       "\xC2\x82" => "'", // U+0082⇒U+201A single low-9 quotation mark
       "\xC2\x84" => '"', // U+0084⇒U+201E double low-9 quotation mark
       "\xC2\x8B" => "'", // U+008B⇒U+2039 single left-pointing angle quotation mark
       "\xC2\x91" => "'", // U+0091⇒U+2018 left single quotation mark
       "\xC2\x92" => "'", // U+0092⇒U+2019 right single quotation mark
       "\xC2\x93" => '"', // U+0093⇒U+201C left double quotation mark
       "\xC2\x94" => '"', // U+0094⇒U+201D right double quotation mark
       "\xC2\x9B" => "'", // U+009B⇒U+203A single right-pointing angle quotation mark

       // Regular Unicode     // U+0022 quotation mark (")
                              // U+0027 apostrophe     (')
       "\xC2\xAB"     => '"', // U+00AB left-pointing double angle quotation mark
       "\xC2\xBB"     => '"', // U+00BB right-pointing double angle quotation mark
       "\xE2\x80\x98" => "'", // U+2018 left single quotation mark
       "\xE2\x80\x99" => "'", // U+2019 right single quotation mark
       "\xE2\x80\x9A" => "'", // U+201A single low-9 quotation mark
       "\xE2\x80\x9B" => "'", // U+201B single high-reversed-9 quotation mark
       "\xE2\x80\x9C" => '"', // U+201C left double quotation mark
       "\xE2\x80\x9D" => '"', // U+201D right double quotation mark
       "\xE2\x80\x9E" => '"', // U+201E double low-9 quotation mark
       "\xE2\x80\x9F" => '"', // U+201F double high-reversed-9 quotation mark
       "\xE2\x80\xB9" => "'", // U+2039 single left-pointing angle quotation mark
       "\xE2\x80\xBA" => "'", // U+203A single right-pointing angle quotation mark
    );

    $chr = array_keys  ($chr_map); // but: for efficiency you should
    $rpl = array_values($chr_map); // pre-calculate these two arrays

    $title = str_replace($chr, $rpl, html_entity_decode($title, ENT_QUOTES, "UTF-8"));    
    return $title;
}

/**
* Normalize a recipe title by removing any hashtags from the title.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_title_remove_hashtags ($title)
{
    return preg_replace('/#(?=[\w-]+)/', '', 
        preg_replace('/(?:#[\w-]+\s*)+$/', '', $title));
}

/**
* Normalize a title by removing unwanted words from titles.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_title_remove_words ($title) {
    $title = str_replace(
        array(
            "recipe", "recette", 
        ), 
        "", $title);
        
    return $title;
}

/**
* Normalize a title by upper-casing some letters.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_title_uppercase ($title) {
    $title = mb_convert_case($title, MB_CASE_TITLE, "UTF-8");
    $title = str_replace("Diy ", "DIY ", $title); // upper-case words like DIY
        
    return $title;
}

/**
* Normalize a title or list of ingredients by adding common spelling changes from the
* foodgeeks_kohana.ingredient_spellchanges table.
*
* @param    string  The $title for this recipe or recipe type
* @return 	string  The normalized $title
*/
function _normalize_spelling_changes ($mysqli, $text) {
    $sql = "
        SELECT * 
        FROM sandbox_foodgeeks_kohana.ingredient_spellchanges 
        ORDER BY original ASC
    ";
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            $text = str_ireplace($row['original'], $row['new'], $text);
        }
    }
    return $text;
}

// Clean up the title
function prep_title($mysqli, $new_recipe) {

    // For Testing, comment out
    // $new_recipe['title'] = "Strawberry–Nutella Pop Tarts";
    // echo $new_recipe['title'] . "\r\n"; 

    // Convert every word to lower case first
    $new_recipe['title'] = mb_strtolower($new_recipe['title']);

    // Normalize quotation marks and hashtags
    $new_recipe['title'] = _normalize_quotes($new_recipe['title']);
    $new_recipe['title'] = _normalize_title_remove_hashtags($new_recipe['title']);
    $new_recipe['title'] = _normalize_title_remove_words($new_recipe['title']);

    // Normalize spelling for words in title
    $new_recipe['title'] = _normalize_spelling_changes($mysqli, $new_recipe['title']);
    
    // Convert every word to upper case first
    $new_recipe['title'] = _normalize_title_uppercase($new_recipe['title']);
    
    // Normalize and lowercase unimportant words
    $new_recipe['title'] = _normalize_title_conjunctions($new_recipe['title']);
    $new_recipe['title'] = _normalize_title_prepositions($new_recipe['title']);    

    // Serious Eats & Foodie Crush - Remove prefixes from the titles 
    $publishers = array(24, 54);
    if (in_array($new_recipe['publisher_id'], $publishers) && strstr($new_recipe['title'], ":")) {
        $pieces = explode(":", $new_recipe['title']);
        $new_recipe['title'] = trim($pieces[1]);
    }
    
    // Oh Sweet Basil - Remove suffixes from the titles (e.g. "Asian Lettuce Wraps: by Jennifer")
    $publishers = array(66);
    if (strstr($new_recipe['title'], ":")) {
        $pieces = explode(":", $new_recipe['title']);
        $new_recipe['title'] = trim($pieces[0]);
    }
    
    $new_recipe['title'] = trim($new_recipe['title']);

    // For testing, comment out
    // echo $new_recipe['title'] . "\r\n"; exit;

    return $new_recipe;
}




/****************
 * BEGIN SCRIPT *
 ***************/



// 1. Fetch all of the recipes that have not been prepped for import.
if ($original_recipes = get_publisher_recipes($mysqli)) {
    
    echo "\r\n *************************************";
    echo "\r\n * Found " . count($original_recipes) . " recipes - proceeding... ";
    echo "\r\n ************************************* \r\n";
    sleep(3);
    
    foreach ($original_recipes as $or) {
        if ($original_recipe = get_publisher_recipe($mysqli, $or['id'])) {
        
            $recipe_is_importable = is_recipe_importable($original_recipe['url']);
            
            // 2. Ensure that the recipe is importable. If not, skip this recipe.
            if ($recipe_is_importable === false) {
                mark_publisher_recipe_as_unsupported($mysqli, $original_recipe['id']);
                
                echo "\r\n *************************************";
                echo "\r\n * Publisher Recipe #" . $original_recipe['id'] . " - \"" . $original_recipe['title'] . "\" is an unsupported url / language. SKIPPING. ";
                echo "\r\n * ";
                echo "\r\n * Original URL was " . $original_recipe['url'];
                echo "\r\n ************************************* \r\n";            
            } 
            
            else {
                $new_recipe = prep_recipe($original_recipe);
                $new_recipe = prep_times($original_recipe, $new_recipe);
                $new_recipe = prep_yield($mysqli, $original_recipe['yield'], $new_recipe);
                $new_recipe = prep_photo($new_recipe, $original_recipe['id'], $original_recipe['photo_url']);
                $new_recipe = prep_ingredients($mysqli, $new_recipe);
                $new_recipe = prep_title($mysqli, $new_recipe);
            
                // Insert or update recipe in recipes_prepped_for_import table
                $recipe_exists = does_recipe_exist($mysqli, $original_recipe['id']);
                if ($recipe_exists === true) {
                    update_recipe($mysqli, $new_recipe);                
                } else {
                    // Make sure this recipe has a unique url. If it doesn't, toss it. If it does, insert it.
                    $recipe_has_duplicate_url = is_url_duplicate($mysqli, $original_recipe['url']);
                    if ($recipe_has_duplicate_url === false) {                    
                        insert_recipe($mysqli, $new_recipe);
                    }
                }
                
                // 9. Update entry in publisher_recipes table
                update_publisher_recipe($mysqli, $original_recipe['id']);
            }
        }
    }    
    
} else {
    echo "\r\n ::: Sorry Joe, no recipes were found. ::: \r\n\r\n";
}
 
echo "\r\n\r\n\r\n";
echo "FINISHING prep.php";
echo "\r\n\r\n\r\n";

?>