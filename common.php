<?php

mb_internal_encoding("UTF-8");
ini_set('memory_limit','512M');

// MySQL Connection
$mysqli = mysqli_connect("localhost", "sandbox", "", "publisher_recipes");
if ($mysqli->connect_errno) echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;

// Definitions
define('SCRAPER_VERSION', 1.1);
define('THIRTY_DAYS_AGO', (time() - 2592000));

// Do a quick cleanup on the text from the suck-hrecipe and suck-schema scripts
function cleanup_text($text) {
    $text = preg_replace( "/\r|\n/", "", $text);
    $text = preg_replace( "/\t|\s+/", " ", $text);
    return trim($text);
}

/**
* Make sure this url isn't one of the urls we don't want to import. e.g. German / Dutch 
* recipes, tag pages, print pages, etc.
*
* @param    string  The $url for this recipe
* @return 	bool    return true if importable; false if not
*/
function is_recipe_in_supported_language($url) {

    // Ensure that the URL is a valid URL
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    
    // Ensure that the URI does not contain commonly known indicators that is in another language
    $invalid_uris = array(
        'allrecipes.co.in',
        'allrecipes.com.ar,',
        'allrecipes.com.br',                 
        'allrecipes.asia',
        'allrecipes.cn',
        'allrecipes.fr',
        'allrecipes.it',
        'allrecipes.jp',
        'allrecipes.kr',        
        'allrecipes.nl',
        'allrecipes.pl',
        'allrecipes.ru',
        'de.allrecipes.com',
        'qc.allrecipes.ca',

        'chocolateandzucchini.com/vf/',     // Any french page on chocolateandzucchini.com
        'jamieoliver.com/nl/',              // Jamie Oliver Dutch recipes
        'jamieoliver.com/de/',              // Jamie Oliver German recipes
    );
    foreach ($invalid_uris as $invalid_uri) {
        if (strstr($url, $invalid_uri)) {
            return false;
        }
    }    
    
    return true;
}

/**
* Make sure this url isn't one of the urls we don't want to import. e.g. German / Dutch 
* recipes, tag pages, print pages, etc.
*
* @param    string  The $url for this recipe
* @return 	bool    return true if importable; false if not
*/
function is_recipe_importable($url) {

    // Ensure that the URL is a valid URL
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    // Ensure that the URI does not contain commonly known indicators that this is 
    // a. not a recipe page
    // b. not a print or iframe page instead of the actual recipe page
    $invalid_uris = array(
        '/about',
        '/account/',
        '/archives/',                       // Archives page, e.g. http://www.davidlebovitz.com/archives/2008/10/
        '/articles/',                       // Articles page (mainly for sheknows.com)
        '/author/',                         // Any author tag page
        '/authors/',                        // Any author tag page
        '/baby-names/',                     // Baby names page (mainly for sheknows.com)
        '/beauty-and-style/',               // Beauty and style page (mainly for sheknows.com)
        '/category/',                       // Any category tag page
        '/collections/',                    // A collections page, e.g. http://www.bhg.com/collections/no-fail-fish-seafood-reicpes/
        'comment',                          // Any comment page, e.g. http://joythebaker.com/2014/12/grapefruit-...-cookies/comment-page-2/#comments
        '/email/',                          // Any email page, e.g. http://smittenkitchen.com/blog/2006/10/lemony-persnick/email/
        '/entertainment/',                  // Entertainment page, e.g. http://www.sheknows.com/entertainment/articles/1024211/miley-cyrus-home-burglarized
        'iframe',                           // Any iframe page
        '/img_',                            // And img page, .e.g. http://cookiesandcups.com/pumpkin-shaped-orange-velvet-whoopie-pies/img_4207/
        '/market',                          // A market page, .e.g. http://foodnessgracious.com/market/gourmet
        '/members/',                        // Members page, e.g. http://www.keyingredient.com/members/20028/followers/
        '/page/',                           // Any pagination page
        'print',                            // Printed recipe pages, e.g. http://www.tasteandtellblog.com/easyrecipe-print/768-0/
        '/product/',                        // Any products page
        '/products/',                       // Any products page
        'slideshow',                        // Any slideshow page
        '/tag/',                            // Any recipe tag page
        '/tags/',                           // Any recipe tag page                
    );
    foreach ($invalid_uris as $invalid_uri) {
        if (strstr($url, $invalid_uri)) {
            return false;
        }
    }    
    
    return true;
}

?>
