<?php

require_once('common.php');
require_once('microdata.php');


/**
* Parse the ingredients in a custom manner instead of via the generic parser.
*
* @param    array   The $recipe array
* @param    object  The $xpath of this page
* @return 	array   Return an array of ingredients
*/
function parse_ingredients($recipe, $xpath) {
    $ingredients_array = array();
    $els = $xpath->evaluate("/html/body//*");
    for ($i = 0; $i < $els->length; $i++) {
        $el = $els->item($i);
        $itemprop  = $el->getAttribute('itemprop');
        if (
            $itemprop == 'ingredients' || // Schema.org standard
            $itemprop == 'recipeIngredient' // NY Times
        ) {
            $ingredient = $el->nodeValue;
            $ingredient = cleanup_text($ingredient);
            array_push($ingredients_array, $ingredient);
        }
    }
    
    if (!empty($ingredients_array)) {
        $recipe['ingredients_array'] = $ingredients_array;
        foreach ($ingredients_array as $ingredient) {
            if (!empty($recipe['ingredients'])) {
                $recipe['ingredients'] .= "\r\n";
            }
            $recipe['ingredients'] .= cleanup_text($ingredient);                
        }
    }
    
    return $recipe;
}

/**
* Parse the yield in a custom manner instead of via the generic parser.
*
* @param    object  The $xpath of this page
* @return 	string  Return the $yield value if found
*/
function parse_yield($xpath) {
    $yield = null;
    $els = $xpath->evaluate("/html/body//*");
    for ($i = 0; $i < $els->length; $i++) {
        $el = $els->item($i);
        $itemprop = $el->getAttribute('itemprop');
        $class = $el->getAttribute('class');
    
        if ($itemprop == 'recipeYield') {
            if ($content = $el->getAttribute('content')) { // allrecipes - <meta ... content="4">
                $yield = $content;
            } elseif ($value = $el->nodeValue) { // nytimes - <span itemprop="recipeYield">4 servings</span>
                $yield = $el->nodeValue;
            }
        }
    
        if (
            (stristr($class, 'mslo-credits') && stristr($el->nodeValue, 'Servings')) || // martha stewart - <li class="mslo-credits"><span class="credit-label">Servings:</span>16</li>
            (stristr($class, 'rec-Servings') && stristr($el->nodeValue, 'Makes')) // tasteofhome - <span class="rec-Servings"><span>MAKES:</span><span>4 servings</span></span>
        ) { 
            $yield = $el->nodeValue;
        }
    }
    return cleanup_text($yield);
}


function fetch_schemaorg($publisher_id, $url) { 

    // $url = 'http://allrecipes.com/recipe/159622/chicken-pa-nang/';
    // $url = 'http://cooking.nytimes.com/recipes/1017712-pasta-with-bacon-cheese-lemon-and-pine-nuts'; // not getting ingredients
    // $url = 'http://www.foodgeeks.com/recipes/doubletree-hotel-chocolate-chip-cookies-18302';
    // $url = 'http://www.bonappetit.com/recipe/pies-n-thighs-apple-pie';
    // $url = 'http://pinchofyum.com/garlic-basil-chicken-with-tomato-butter-sauce';
    // $url = 'http://www.womansday.com/food-recipes/food-drinks/recipes/a22681/caramel-crunch-holiday-gifts-recipes/';
    // $url = 'http://www.marthastewart.com/349608/asparagus-flan';
    // $url = 'http://www.marthastewart.com/1080341/poached-salmon-potatoes-cucumber-and-buttermilk-dill-dressing';
    // $url = 'http://www.seriouseats.com/recipes/2015/09/soy-sauce-fall-mushrooms-with-chestnuts.html';
    // $url = 'http://www.tasteofhome.com/recipes/quick-almond-chicken-stir-fry';
    // $url = 'http://www.archanaskitchen.com/recipes/world-recipes/world-food/vegetarian-pasta-pizza-recipes/1925-skillet-pizza-with-roasted-vegetables-in-tomato-basil-sauce-no-yeast-dough';
    // $url = 'http://www.ohsweetbasil.com/panzanella-salad.html';
    // $url = 'http://leitesculinaria.com/4505/recipes-mocha-hazelnut-truffles.html';
    // $url = 'http://www.recipegirl.com/2015/04/27/classic-chicken-soup/';
    // $url = 'http://www.thekitchn.com/thekitchn/easy/recipe-pickletini-051094';
    // $url = 'http://madeleineshaw.com/recipes/healthy-cinnamon-rolls/';
    // $url = 'http://www.shugarysweets.com/2012/02/fluffernutter-caramel-corn';

    echo "\r\n ************************************** ";
    echo "\r\n ** Fetching schema.org markup for " . $url . " **";
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
        'schemaorg' => 1
    );
    
    $md = new Microdata($url);
    if ($items = $md->obj()) {
        $xpath = $md->dom->xpath();
        $ingredients_found = false; 

        $recipe = parse_ingredients($recipe, $xpath); 
        $recipe['yield'] = parse_yield($xpath);  
        
        foreach ($items as $item) {            
            
            // Because each wordpress page could have multiple schema.org arrays within it, assume the one that has ingredients in it is the recipe
            if ($ingredients_found === false) {
                            
                if (isset($item->properties['name'][0]) && !empty($item->properties['name'][0])) {
                
                    // Get the recipe title.
                    if (empty($recipe['title']) && isset($item->properties['name'][0]) && !empty($item->properties['name'][0]) && is_string($item->properties['name'][0])){
                        $recipe['title'] = cleanup_text($item->properties['name'][0]);
                    }

                    /* UGH. This has a ripple effect if we are tossing URLs out here. Commenting out until I can think it through. */ 
                
                    // Get the recipe url if available. This was originally implemented as a fix for Martha Stewart 
                    // recipes - basically, its the canonical url. The problem is that people use this for the /print page,
                    // the /comments page, etc. So, if this url is a valid url and does not match the given url, let's toss it out.
                    // if (isset($item->properties['url'][0]) && !empty($item->properties['url'][0])){
                    //     if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
                    //         if ($recipe['url'] == $item->properties['url'][0]) {
                    //             // cool, proceed
                    //         }
                    //         elseif (trim($item->properties['url'][0]) == '[acf2s_permalink]') {
                    //             // no worries, just keep $recipe['url']
                    //         }
                    //         else {
                    //             // buh-bye
                    //             echo "The given URL " . $recipe['url'] . " != the url in the html " . $item->properties['url'][0] . ". Skipping this recipe.\r\n";
                    //             unset($recipe);
                    //             return false;
                    //         }               
                    //     }
                    // }
                    
                    // Get the image URL.
                    if ((isset($item->properties['image'][0]) && !empty($item->properties['image'][0])) || (isset($item->properties['photo'][0]) && !empty($item->properties['photo'][0]))) {
                
                        if (isset($item->properties['image'][0]) && !empty($item->properties['image'][0])) {
                            $recipe['photo_url'] = $item->properties['image'][0];            
                        } else {
                            $recipe['photo_url'] = $item->properties['photo'][0];            
                        }
                
                        // make sure the url is a full url, and not an url like '/images/recipes/recipe123.jpg'
                        $parsed_photo_url = parse_url($recipe['photo_url']);
                        if (isset($parsed_photo_url['scheme']) && !empty($parsed_photo_url['scheme']) && isset($parsed_photo_url['host']) && !empty($parsed_photo_url['host'])) {
                            // cool, we're all set with a full url
                        } else {
                            // add the beginning of the url back to the url
                            $parsed_url = parse_url($recipe['url']);
                            $new_url = '';
                            if (isset($parsed_photo_url['scheme']) && !empty($parsed_photo_url['scheme'])) { /* Do nothing */ } else {
                                $new_url .= $parsed_url['scheme'] . "://";
                            }
                            if (isset($parsed_photo_url['host']) && !empty($parsed_photo_url['host'])) { /* Do nothing */ } else {
                                $new_url .= $parsed_url['host'];
                            }            
                            $recipe['photo_url'] = $new_url . $recipe['photo_url'];
                        }
                    }
                    
                    // Get the yield.
                    if (empty($recipe['yield']) && isset($item->properties['recipeYield'][0]) && !empty($item->properties['recipeYield'][0])) {
                        $recipe['yield'] = cleanup_text($item->properties['recipeYield'][0]);
                    }    
                    
                    // Get the prep time.
                    if (isset($item->properties['prepTime'][0]) && !empty($item->properties['prepTime'][0])) {
                        $recipe['prep_time'] = cleanup_text($item->properties['prepTime'][0]);
                    }
                
                    // Get the cook time.
                    if (isset($item->properties['cookTime'][0]) && !empty($item->properties['cookTime'][0])) {
                        $recipe['cook_time'] = cleanup_text($item->properties['cookTime'][0]);
                    }
                    
                    // Get the overall time.
                    if (isset($item->properties['totalTime'][0]) && !empty($item->properties['totalTime'][0])) {
                        $recipe['total_time'] = cleanup_text($item->properties['totalTime'][0]);
                    }
                    
                    // Get the ingredients.
                    if (empty($recipe['ingredients']) && isset($item->properties['ingredients']) && !empty($item->properties['ingredients'])) {
                
                        if (is_array($item->properties['ingredients'])) {
                            $recipe['ingredients_array'] = $item->properties['ingredients'];
                            foreach ($item->properties['ingredients'] as $ingredient) {
                                if (!empty($recipe['ingredients'])) {
                                    $recipe['ingredients'] .= "\r\n";
                                }
                                
                                if (isset($ingredient->properties['name'][0])) {
                                    if (isset($ingredient->properties['amount'][0])) {
                                        $recipe['ingredients'] .= cleanup_text($ingredient->properties['amount'][0]) . ' ' . cleanup_text($ingredient->properties['name'][0]);
                                    } else {
                                        $recipe['ingredients'] .= cleanup_text(ucfirst($ingredient->properties['name'][0]));
                                    }
                                } elseif (is_string($ingredient)) {
                                    $recipe['ingredients'] .= cleanup_text($ingredient);
                                }
                            }
                        }

                        if (!empty($recipe['ingredients'])){
                            $ingredients_found = true;
                        }
                    }
                
                    // Get the instructions.
                    if (isset($item->properties['recipeInstructions'][0]) && !empty($item->properties['recipeInstructions'][0])) {
                        $recipe['instructions'] = cleanup_text($item->properties['recipeInstructions'][0]);
                    }
                    
                    // Get the prep methods if available.
                    if (isset($item->properties['cookingMethod'][0]) && !empty($item->properties['cookingMethod'][0])) {
                        $recipe['cooking_method'] = cleanup_text($item->properties['cookingMethod'][0]);
                    }
                        
                    // Get the recipe category "recipe types" if available.
                    if (isset($item->properties['recipeCategory'][0]) && !empty($item->properties['recipeCategory'][0])) {
                        $recipe['recipe_category'] = cleanup_text($item->properties['recipeCategory'][0]);
                    }
                
                    // Get the recipe cuisines if available.
                    if (isset($item->properties['recipeCuisine'][0]) && !empty($item->properties['recipeCuisine'][0])) {
                        $recipe['recipe_cuisine'] = cleanup_text($item->properties['recipeCuisine'][0]);
                    }
                
                }
            }        
        } 
    }
    
    if (isset($recipe['title']) && !empty($recipe['title']) && isset($recipe['ingredients']) && !empty($recipe['ingredients'])) {
        echo "\r\n ************************************** ";
        echo "\r\n ** Schema.org markup for " . $url . " found and pulled in. **";
        echo "\r\n ************************************** \r\n";

        return $recipe;        
    } else {
        echo "\r\n ************************************** ";
        echo "\r\n ** Invalid schema.org recipe markup found at " . $url . " **";
        echo "\r\n ************************************** \r\n";
        
        return false;
    }

}

?>