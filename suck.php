<?php

/************************************************************************************************************
 *
 * Run a number of scraper workers at a given time. Each worker will:
 *
 * 1. Grab any random page from publisher_pages with
 *    * publisher_pages:
 *        * publishers.status = 1
 *        * publisher_pages.invalid_recipe = 0
 *        * publisher_pages.hrecipe = 1 or datavobaularyorg = 1 or schemaorg = 1 AND
 *        * publisher_pages.hrecipe_last_scraped / datavocabularyorg_last_scraped / schemaorg_last_scraped = 0
 *    * publisher_recipes:
 *        * publishers.status = 1
 *        * publisher_recipes.scraper_version < current_scraper_version
 *
 * 2. Mark or delete invalid recipes
 *    * If page states it is in a particular schema, but we don't find recipe in that schema, mark hrecipe / datavocabularyorg / schemaorg = 0
 *    * If recipe is not a recipe based on its url, delete that recipe from publisher_recipes  
 *
 * 3. Scrape the page in each of the schema formats provided
 *    * 3.a. Attempt to scrape an hrecipe recipe
 *    * 3.b. Attempt to scrape a schema.org recipe
 *    * Create an $recipe array with the data that is scraped
 *    * If a valid recipe, insert / update the recipe in publisher_recipes
 *    * Store and upload the primary recipe image to S3 if found
 *    * If page states it is in a particular schema, but we don't find recipe in that schema, mark hrecipe / datavocabularyorg / schemaorg = 0
 *    * If recipe is in an unsupported language, update the record in publisher_recipes.unsupported_language = 1
 *
 * 4. Mark the page has having been scraped
 *
 * 5. Pause and grab another recipe
 *
 *
 *
 * TODO: Update script to handle datavocabularyorg pages
 * TODO: Store html in publisher_pages so that we don't have to keep scraping the live page
 * TODO: Download the best recipe image, not the featured recipe image
 * TODO: Determine how to handle a page with a given URL that does not match it's meta URL
 *
 ************************************************************************************************************/



namespace Foodgeeks;
require_once('./vendor/autoload.php');

require_once('./common.php');
require_once('./suck-schemaorg.php');
require_once('./suck-hrecipe.php');
require_once('./suck-amazons3.php');



/**
* Determine if there is already a recipe at this url
*
* @param    object  The $mysqli object
* @param    int     The $id of the publisher
* @param    string  The $url for this page
* @return 	array   If recipe found, return that recipe
*/
function get_recipe($mysqli, $publisher_id, $url) {
    $sql = "
        SELECT id 
        FROM publisher_recipes 
        WHERE url = '" . $mysqli->real_escape_string($url) . "'
        AND publisher_id =  '" . $mysqli->real_escape_string($publisher_id) . "'
    ";
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            if (isset($row['id']) && !empty($row['id'])) {
                return $row; // Found that the recipe already exists. 
            }
        }
    }
    return false;
}

/**
* Insert a recipe into the publisher_recipes table
*
* @param    object  The $mysqli object
* @param    array   The $recipe array
* @return 	bool
*/
function insert_recipe($mysqli, $recipe) {
    if (isset($recipe['title']) && !empty($recipe['title']) && isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
        $sql_insert_additions = '';
        $sql_value_additions = '';
        if (isset($recipe['publisher_id']) && !empty($recipe['publisher_id'])) {
            $sql_insert_additions .= ', publisher_id';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['publisher_id'])."'";
        }
        if (isset($recipe['url']) && !empty($recipe['url'])) {
            $sql_insert_additions .= ', url';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['url'])."'";
        } 
        if (isset($recipe['yield']) && !empty($recipe['yield'])) {
            $sql_insert_additions .= ', yield';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['yield'])."'";
        } 
        if (isset($recipe['prep_time']) && !empty($recipe['prep_time'])) {
            $sql_insert_additions .= ', prep_time';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['prep_time'])."'";
        } 
        if (isset($recipe['cook_time']) && !empty($recipe['cook_time'])) {
            $sql_insert_additions .= ', cook_time';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['cook_time'])."'";
        } 
        if (isset($recipe['total_time']) && !empty($recipe['total_time'])) {
            $sql_insert_additions .= ', total_time';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['total_time'])."'";
        } 
        if (isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
            $sql_insert_additions .= ', ingredients';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['ingredients'])."'";
        } 
        if (isset($recipe['instructions']) && !empty($recipe['instructions'])) {
            $sql_insert_additions .= ', instructions';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['instructions'])."'";
        } 
        if (isset($recipe['cooking_method']) && !empty($recipe['cooking_method'])) {
            $sql_insert_additions .= ', cooking_method';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['cooking_method'])."'";
        } 
        if (isset($recipe['recipe_category']) && !empty($recipe['recipe_category'])) {
            $sql_insert_additions .= ', recipe_category';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['recipe_category'])."'";
        } 
        if (isset($recipe['recipe_cuisine']) && !empty($recipe['recipe_cuisine'])) {
            $sql_insert_additions .= ', recipe_cuisine';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['recipe_cuisine'])."'";
        } 
        if (isset($recipe['photo_url']) && !empty($recipe['photo_url'])) {
            $sql_insert_additions .= ', photo_url';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['photo_url'])."'";
        } 
        if (isset($recipe['created']) && !empty($recipe['created'])) {
            $sql_insert_additions .= ', created';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['created'])."'";
        } 
        if (isset($recipe['modified']) && !empty($recipe['modified'])) {
            $sql_insert_additions .= ', modified';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['modified'])."'";
        } 
        if (isset($recipe['scraper_version']) && !empty($recipe['scraper_version'])) {
            $sql_insert_additions .= ', scraper_version';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['scraper_version'])."'";
        } 
        if (isset($recipe['hrecipe']) && !empty($recipe['hrecipe'])) {
            $sql_insert_additions .= ', hrecipe';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['hrecipe'])."'";
        } 
        if (isset($recipe['schemaorg']) && !empty($recipe['schemaorg'])) {
            $sql_insert_additions .= ', schemaorg';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['schemaorg'])."'";
        } 
        if (isset($recipe['datavocabularyorg']) && !empty($recipe['datavocabularyorg'])) {
            $sql_insert_additions .= ', datavocabularyorg';
            $sql_value_additions .= " , '".$mysqli->real_escape_string($recipe['datavocabularyorg'])."'";
        }
        
        $sql = "
            INSERT INTO publisher_recipes (    
                title " . $sql_insert_additions . "
            ) VALUES (
                '".$mysqli->real_escape_string($recipe['title'])."' ". $sql_value_additions ."
            )
        ";

        $mysqli->query($sql);
        if (!empty($mysqli->error)) {
            echo $mysqli->error;
        } else {
            $recipe['id'] = $mysqli->insert_id;
            fetch_and_save_photo($recipe);
            echo "\r\n ************************************************************************ ";
            echo "\r\n *";
            echo "\r\n * Publisher Recipe ID# " . $recipe['id']  . " - ". $recipe['title'] . " INSERTED into the database from " . $recipe['url'];
            echo "\r\n *";            
            echo "\r\n ************************************************************************ \r\n\r\n";
            return true;            
        }
    } else {
        echo "Recipe schema found, but recipe title or ingredients are missing. Import failed. \r\n\r\n";
    }
    return false;
}

/**
* Update a recipe in the publisher_recipes table
*
* @param    object  The $mysqli object
* @param    array   The $recipe array
* @return 	bool
*/
function update_recipe($mysqli, $recipe) {
    if (
        isset($recipe['title']) && !empty($recipe['title']) && 
        isset($recipe['ingredients']) && !empty($recipe['ingredients']) &&
        isset($recipe['publisher_recipe_id']) && !empty($recipe['publisher_recipe_id']) &&
        isset($recipe['publisher_id']) && !empty($recipe['publisher_id'])
    ) {
        $sql_additions = '';
        if (isset($recipe['publisher_id']) && !empty($recipe['publisher_id'])) {
            $sql_additions .= " , publisher_id = '".$mysqli->real_escape_string($recipe['publisher_id'])."'";
        }
        if (isset($recipe['url']) && !empty($recipe['url'])) {
            $sql_additions .= " , url = '".$mysqli->real_escape_string($recipe['url'])."'";
        } 
        if (isset($recipe['yield']) && !empty($recipe['yield'])) {
            $sql_additions .= " , yield = '".$mysqli->real_escape_string($recipe['yield'])."'";
        } 
        if (isset($recipe['prep_time']) && !empty($recipe['prep_time'])) {
            $sql_additions .= " , prep_time = '".$mysqli->real_escape_string($recipe['prep_time'])."'";
        } 
        if (isset($recipe['cook_time']) && !empty($recipe['cook_time'])) {
            $sql_additions .= " , cook_time = '".$mysqli->real_escape_string($recipe['cook_time'])."'";
        } 
        if (isset($recipe['total_time']) && !empty($recipe['total_time'])) {
            $sql_additions .= " , total_time = '".$mysqli->real_escape_string($recipe['total_time'])."'";
        } 
        if (isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
            $sql_additions .= " , ingredients = '".$mysqli->real_escape_string($recipe['ingredients'])."'";
        } 
        if (isset($recipe['instructions']) && !empty($recipe['instructions'])) {
            $sql_additions .= " , instructions = '".$mysqli->real_escape_string($recipe['instructions'])."'";
        } 
        if (isset($recipe['cooking_method']) && !empty($recipe['cooking_method'])) {
            $sql_additions .= " , cooking_method = '".$mysqli->real_escape_string($recipe['cooking_method'])."'";
        } 
        if (isset($recipe['recipe_category']) && !empty($recipe['recipe_category'])) {
            $sql_additions .= " , recipe_category = '".$mysqli->real_escape_string($recipe['recipe_category'])."'";
        } 
        if (isset($recipe['recipe_cuisine']) && !empty($recipe['recipe_cuisine'])) {
            $sql_additions .= " , recipe_cuisine = '".$mysqli->real_escape_string($recipe['recipe_cuisine'])."'";
        } 
        if (isset($recipe['photo_url']) && !empty($recipe['photo_url'])) {
            $sql_additions .= " , photo_url = '".$mysqli->real_escape_string($recipe['photo_url'])."'";
        } 
        if (isset($recipe['created']) && !empty($recipe['created'])) {
            $sql_additions .= " , created = '".$mysqli->real_escape_string($recipe['created'])."'";
        } 
        if (isset($recipe['modified']) && !empty($recipe['modified'])) {
            $sql_additions .= " , modified = '".$mysqli->real_escape_string($recipe['modified'])."'";
        } 
        if (isset($recipe['scraper_version']) && !empty($recipe['scraper_version'])) {
            $sql_additions .= " , scraper_version = '".$mysqli->real_escape_string($recipe['scraper_version'])."'";
        } 
        if (isset($recipe['hrecipe']) && !empty($recipe['hrecipe'])) {
            $sql_additions .= " , hrecipe = '".$mysqli->real_escape_string($recipe['hrecipe'])."'";
        } 
        if (isset($recipe['schemaorg']) && !empty($recipe['schemaorg'])) {
            $sql_additions .= " , schemaorg = '".$mysqli->real_escape_string($recipe['schemaorg'])."'";
        } 
        if (isset($recipe['datavocabularyorg']) && !empty($recipe['datavocabularyorg'])) {
            $sql_additions .= " , datavocabularyorg = '".$mysqli->real_escape_string($recipe['datavocabularyorg'])."'";
        }
                
        $sql = "
            UPDATE publisher_recipes
            SET 
                title = '".$mysqli->real_escape_string($recipe['title'])."'
                " . $sql_additions . "
            WHERE publisher_id = '".$mysqli->real_escape_string($recipe['publisher_id'])."'
            AND id = '".$mysqli->real_escape_string($recipe['publisher_recipe_id'])."'";

        $mysqli->query($sql);
        
        if (!empty($mysqli->error)) {
            echo $mysqli->error;
        } else {
            fetch_and_save_photo($recipe);
            echo "\r\n ************************************************************************ ";
            echo "\r\n *";
            echo "\r\n * Publisher Recipe ID# " . $recipe['publisher_recipe_id'] . " - ". $recipe['title'] . " UPDATED in the database from " . $recipe['url'];
            echo "\r\n *";            
            echo "\r\n ************************************************************************ \r\n\r\n";
            return true;
        }
    } else {
        echo "Recipe schema found, but recipe title or ingredients are missing. Import failed. \r\n\r\n";
    }
    return false;
}

/**
* Fetch the recipes that need to be re-scraped based on their version number
*
* @param    object  The $mysqli object
* @return 	array   An array of recipes
*/
function get_schemaorg_recipes_from_old_scraper_version($mysqli) {
    $sql = '
        SELECT pr.id, pr.publisher_id, pr.url
        FROM publisher_recipes pr
        WHERE pr.schemaorg = 1
        AND pr.scraper_version < ' . SCRAPER_VERSION . '
        ORDER BY RAND()
        LIMIT 10000
        ';
    if ($results = $mysqli->query($sql)) {
        $recipes = array();
        while ($row = $results->fetch_assoc()) {
            $recipes[] = $row;
        }
        return $recipes;
    }
    return false; 
}

/**
* Get a random publisher page.
*
* @todo     Pull datavocabularyorg pages once we have support for that schema type.
* 
* @param    object  The $mysqli object
* @return 	array   Return an array with the publisher page
*/
function get_a_publisher_page($mysqli) {
    $sql = '
        SELECT pp.* 
        FROM publisher_pages pp
        INNER JOIN publishers p ON p.id = pp.publisher_id
        WHERE 
            (
                (hrecipe = 1 AND hrecipe_last_scraped = 0) OR 
                (schemaorg = 1 AND schemaorg_last_scraped = 0)
            )
        AND p.status = 1        
        AND p.do_not_crawl_in_future = 0
        AND pp.invalid_recipe = 0
        ORDER BY RAND()
        LIMIT 1
    ';
    
    if ($results = $mysqli->query($sql)) {
        $pages = array();
        while ($row = $results->fetch_assoc()) {
            if (isset($row['id'])) {
                return $row;
            }
        }
    }
    return false;
}

/**
* Get a random publisher recipe.
*
* @param    object  The $mysqli object
* @return 	array   Return an array with the publisher recipe
*/
function get_a_publisher_recipe($mysqli) {
    $sql = '
        SELECT pr.*
        FROM publisher_recipes pr
        INNER JOIN publishers p ON p.id = pr.publisher_id
        WHERE 
            (
                (hrecipe = 1 AND scraper_version < ' . SCRAPER_VERSION . ') OR 
                (schemaorg = 1 AND scraper_version < ' . SCRAPER_VERSION . ')
            ) 
        AND pr.unsupported_language = 0            
        AND p.do_not_crawl_in_future = 0
        AND p.status = 1
        ORDER BY RAND()
        LIMIT 1
        ';
        
    if ($results = $mysqli->query($sql)) {
        $recipes = array();
        while ($row = $results->fetch_assoc()) {
            if (isset($row['id'])) {
                return $row;
            }
        }
        return $recipes;
    }
    return false;
}

/**
* Update the publisher_pages table to note that this page has been scraped.
*
* @param    object  The $mysqli object
* @param    int     The $id of the publisher that published the page
* @param    string  The $url of the publisher page
* @param    int     Is this an hrecipe page?
* @param    int     Is this a schema.org page?
* @return 	void
*/
function page_has_been_scraped($mysqli, $publisher_id, $url, $hrecipe = 0, $schemaorg = 0) {
    $sql = "
        UPDATE publisher_pages 
        SET 
            hrecipe = '".$mysqli->real_escape_string($hrecipe)."',
            schemaorg = '".$mysqli->real_escape_string($schemaorg)."',
            scraper_version = '".$mysqli->real_escape_string(SCRAPER_VERSION)."'
        WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
        AND url = '".$mysqli->real_escape_string($url)."'";
    $mysqli->query($sql);
    if (!empty($mysqli->error)) {
        echo $mysqli->error;
        exit;
    }
}

/**
* Mark a page as not having a valid recipe based on its url.
*
* @param    object  The $mysqli object
* @param    int     The $id of the publisher that published the page
* @param    string  The $url of the publisher page
* @return 	bool
*/
function can_publisher_page_be_imported($mysqli, $publisher_id, $url) {
    $page_is_importable = is_recipe_importable($url);
    
    if ($page_is_importable === true) {
        return true;
    }
    
    else {
        $sql = "
            UPDATE publisher_pages 
            SET invalid_recipe = 1
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'";
        $mysqli->query($sql);
        if (!empty($mysqli->error)) {
            echo $mysqli->error;
            exit;
        }
        
        $sql = "
            DELETE FROM publisher_recipes
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'";
        $mysqli->query($sql);
        if (!empty($mysqli->error)) {
            echo $mysqli->error;
            exit;
        }

        echo "\r\n ************************************** ";
        echo "\r\n * Recipe " . $url . " is not a valid recipe page based on its url.";
        echo "\r\n ************************************** \r\n";
        
        return false;
    }
}

/**
* Update publisher_recipes to note that language is not supported, if that's the case
*
* @param    object  The $mysqli object
* @param    int     The $id of the publisher that published the page
* @param    string  The $url of the publisher page
* @return 	bool
*/
function is_publisher_recipe_in_supported_language($mysqli, $publisher_id, $url) {
    $recipe_in_supported_language = is_recipe_in_supported_language($url);
    
    if ($recipe_in_supported_language === true) {
        return true;
    }

    else {
        if ($recipe_in_supported_language === false) {
            
            $sql = "
                UPDATE publisher_recipes
                SET unsupported_language = 1
                WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
                AND url = '".$mysqli->real_escape_string($url)."'";
            $mysqli->query($sql);
            if (!empty($mysqli->error)) {
                echo $mysqli->error;
                exit;
            }
            
            echo "\r\n ************************************** ";
            echo "\r\n * Recipe " . $url . " is not in a supported language based on its url.";
            echo "\r\n ************************************** \r\n";
        }
        
        return false;
    }
}




/****************
 * BEGIN SCRIPT *
 ***************/
 

$stop_at = 10;
for ($i = 0; $i <= $stop_at; $i++) {
    
    // 1. Either fetch a publisher page or fetch a publisher recipe
    $fetch_a = rand(1, 2);
    if ($fetch_a == 1)     $page = get_a_publisher_page($mysqli);
    elseif ($fetch_a == 2) $page = get_a_publisher_recipe($mysqli);

    if (isset($page) && !empty($page)) {
        $hrecipe = 0;
        $schemaorg = 0;
        
        // 2. Ensure the recipe is a valid url (not a tag, print, pagination, comments page, etc.)
        if ($recipe_is_importable = can_publisher_page_be_imported($mysqli, $page['publisher_id'], $page['url'])) {

            // 3. Scrape the recipe
            
            // 3.a. Grab the hrecipe schema if available
            if ($page['hrecipe'] == 1) {
                if ($recipe = fetch_hrecipe($page['publisher_id'], $page['url'])) {
                    $hrecipe = 1;

                    // Insert or update recipe in the publisher_recipes table
                    if ($publisher_recipe = get_recipe($mysqli, $page['publisher_id'], $page['url'])) {
                        $recipe['publisher_recipe_id'] = intval($publisher_recipe['id']);
                        update_recipe($mysqli, $recipe);
                    } else {
                        insert_recipe($mysqli, $recipe); 
                    }
                }
            }
            
            // 3.b. Grab the schema.org schema if available
            if ($page['schemaorg'] == 1) {
                if ($recipe = fetch_schemaorg($page['publisher_id'], $page['url'])) {
                    $schemaorg = 1;
                    
                    // Insert or update recipe in the publisher_recipes table
                    if ($publisher_recipe = get_recipe($mysqli, $page['publisher_id'], $page['url'])) {
                        $recipe['publisher_recipe_id'] = intval($publisher_recipe['id']);
                        update_recipe($mysqli, $recipe);
                    } else {
                        insert_recipe($mysqli, $recipe); 
                    }
                    
                }
            }

            // 3.c @TODO FUTURE Grab the datavocabulary.org schema if available
            if ($page ['datavocabularyorg'] == 1) {
                // @TODO ADD SUPPORT FOR THIS
            }

            // 3.x Check if this recipe is in a supported language, if not, update the publisher_recipes table accordingly
            $recipe_in_supported_language = is_publisher_recipe_in_supported_language($mysqli, $page['publisher_id'], $page['url']);
        }
        
        // 4. Mark the page has having been scraped    
        page_has_been_scraped($mysqli, $page['publisher_id'], $page['url'], $hrecipe, $schemaorg);

    } else {
        echo "\r\n ************************************** ";
        echo "\r\n * Sorry Joe, didn't find any scrapeable pages or recipes. ::: **";
        echo "\r\n ************************************** \r\n";
    }

    // Incrememnt $stop_at to ensure that we keep this script running at all times.
    $stop_at++; 

}

?>
