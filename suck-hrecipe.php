<?php

namespace Foodgeeks;
require_once('/users/ryansnyder/vendor/autoload.php');
require_once('common.php');
use Mf2;

function fetch_hrecipe($publisher_id, $url) {

    echo "\r\n ************************************** ";
    echo "\r\n ** Fetching hrecipe markup for " . $url . " **";
    echo "\r\n ************************************** \r\n";

    $recipe = array(
        'publisher_id' => $publisher_id,
        'url' => $url,
        'title' => null,
        'yield' => null,
        'prep_time' => null,
        'cook_time' => null,
        'total_time' => null,    
        'ingredients' => null,
        'ingredients_array' => array(),
        'instructions' => null,
        'photo_url' => null,
        'cooking_method' => null,
        'recipe_category' => null,
        'recipe_cuisine' => null,
        'created' => time(),
        'modified' => time(),
        'scraper_version' => SCRAPER_VERSION,
        'hrecipe' => 1
    );
    
    // See if this is an hrecipe
    $mf = Mf2\fetch($url);
    if (isset($mf['items']) && !empty($mf['items'])) {
        $ingredients_found = false;

        foreach ($mf['items'] as $item) {
            if (isset($item['properties']) && $ingredients_found === false) {
                foreach ($item['properties'] as $key => $property) {
                            
                    if ($key == 'name') {
                        if (isset($property[0])) {
                            $recipe['title'] = $property[0];
                            if (mb_stristr($recipe['title'], "\t")) {
                                $recipe_title = explode("\t", $recipe['title']);
                                if (isset($recipe_title[0]) && !empty($recipe_title[0])){
                                    $recipe['title'] = cleanup_text($recipe_title[0]);        
                                }
                            }
                        }
                    }
                    
                    if ($key == 'yield') {
                        if (isset($property[0])) {
                            $recipe['yield'] = cleanup_text($property[0]);
                        }
                        
                    }
            
                    if ($key == 'ingredient') {
                        foreach ($property as $i) {
                            array_push($recipe['ingredients_array'], cleanup_text($i));
                            $ingredients_found = true;
                        }        
                    }
        
                    if ($key == 'preptime' || $key == 'prepTime') {
                        $recipe['prep_time'] = cleanup_text($property[0]);    
                    }
            
                    if ($key == 'duration' || $key == 'totalTime') {
                        $recipe['total_time'] = cleanup_text($property[0]);
                    }
                    
                    if ($key == 'instructions') {
                        foreach ($property as $p) {
                            if (isset($p['html']) && empty($recipe['instructions'])) {
                                $instructions = trim(html_entity_decode($p['html']));
                                $instructions = preg_replace('/(\s*)\<br(\s*)?\/?\>/i', "\n", $instructions);
                                $recipe['instructions'] = cleanup_text($instructions);
                            }
                        }
                    }
                    
                    if ($key == 'photo') {
                        if (isset($property[0])) {
                            $recipe['photo_url'] = $property[0];
                        }
                    }
                    
                }
            }
        }
        
        // Prep ingredients to put in straight up text format
        if (!empty($recipe['ingredients_array'])) {
            foreach ($recipe['ingredients_array'] as $ingredient) {
                if (!empty($recipe['ingredients'])) {
                    $recipe['ingredients'] .= "\r\n";
                }
                $recipe['ingredients'] .= $ingredient;
            }
        }   
    }
    
    if (isset($recipe['title']) && !empty($recipe['title']) && isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
        echo "\r\n ************************************** ";
        echo "\r\n ** Hrecipe markup for " . $url . " found and pulled in. **";
        echo "\r\n ************************************** \r\n";
        
        return $recipe;
    } else {
        echo "\r\n ************************************** ";
        echo "\r\n ** Invalid hrecipe markup found at " . $url . " **";
        echo "\r\n ************************************** \r\n";
        
        return false;
    }
    
}

?>
