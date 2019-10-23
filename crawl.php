<?php

include('common.php');

// Fetch a publisher that has not been crawled yet.
function fetch_publisher($mysqli) {
    $sql = '
        SELECT * 
        FROM publishers
        WHERE last_crawled <= '.THIRTY_DAYS_AGO.'    
        AND status = 1
        AND id != 12
        AND id != 13
        ORDER BY RAND()
    ';
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return $row;
        } 
    }

    // If no pubs found.
    echo "\r\n All publishers have been crawled. Quitting. \r\n\r\n"; 
}

// Clean up the url that we got on the page.
function clean_up_url($website, $url) {   
    $website = trim(rtrim($website, "/"));
    $parsed_website = parse_url($website);
    $parsed_url = parse_url($url);

    // Make sure this is a crawlable page. Images, for example, are not crawlable.
    if (preg_match('/\.(jpg|jpeg|png|gif|ico|css|js|txt|pdf|rss)(?:[\?\#].*)?$/i', $url, $matches)) {
        echo "\r\n URL " . $url . " is not crawlable. Ignoring. \r\n\r\n";
        return false;
    }
        
    // Let's make sure the website is part of this url (that we're not crawling another site)
    if (isset($parsed_url['scheme']) && isset($parsed_url['host']) && stristr($url, $website) === FALSE) {
        echo "\r\n URL " . $url . " is not part of website " . $website . ". Ignoring. \r\n\r\n";
        return false;
    }
    
    // Strip the query string and return the cleaned up url.
    if (isset($parsed_url['scheme']) && isset($parsed_url['host'])) {        
        $url = $parsed_url['scheme'] . "://" . $parsed_url['host'];
        if (isset($parsed_url['path'])) {
            $url .= $parsed_url['path'];
        }
        
        // Let's just make sure this is cool.
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            echo "\r\n URL " . $url . " is an invalid url format. Ignoring. \r\n\r\n";
            return false;
        } else {
            return $url;
        }
    }   

    // Now what about "/food/recipes/applecrumblewithcust_81609" needing to match "http://www.bbc.co.uk/food"
    if (isset($parsed_website['path']) && isset($parsed_url['path'])) {
        if (substr($parsed_url['path'], 0, strlen($parsed_website['path'])) === $parsed_website['path']) {
            $url = $parsed_website['scheme'] . "://" . $parsed_website['host'];
            $url .= $parsed_url['path'];
                        
            // Let's just make sure this is cool.
            if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
                echo "\r\n URL " . $url . " is an invalid url format. Ignoring. \r\n\r\n";
                return false;
            } else {
                return $url;
            }
        }
    }
    
    // Match "/Recipes/65226-clay-pot-orange-chicken" ON CookLime.com at "http://cooklime.com/Recipes/65226-clay-pot-orange-chicken"
    if (!isset($parsed_url['host']) && isset($parsed_url['path'])) {
        $url = $parsed_website['scheme'] . "://" . $parsed_website['host'];
        $url .= $parsed_url['path'];

        // Let's just make sure this is cool.
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            echo "\r\n URL " . $url . " is an invalid url format. Ignoring. \r\n\r\n";
            return false;
        } else {
            return $url;
        }
    }

    // Or... return false if that failed.
    return false;
}

// Check to see if this page has already been added to the database
function does_page_exist($mysqli, $publisher_id, $url) {
    $sql = "
        SELECT * 
        FROM publisher_pages 
        WHERE url = '".$mysqli->real_escape_string($url)."' 
        AND publisher_id = '".$mysqli->real_escape_string($publisher_id)."'";
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return $row;
        }
    }
    return false;
}

// Check to see if this page has been crawled within the past month.
function has_page_been_crawled_in_past_month($mysqli, $publisher_id, $url) {
    $sql = "
        SELECT * 
        FROM publisher_pages 
        WHERE url = '".$mysqli->real_escape_string($url)."' 
        AND publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
        AND last_crawled >= ".THIRTY_DAYS_AGO;
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return $row;
        }
    }
    return false;  
}

// Store the URLs found on this page. 
function store_url($mysqli, $publisher, $url) {

    // Clean up this url
    if ($url = clean_up_url($publisher['website'], $url)) {

        // If the page already exists, skip to the next page
        if ($page = does_page_exist($mysqli, $publisher['id'], $url)) {
            return false;
        } 
        
        // If the page does not already exist, add it. Set last_crawled to 0 because it hasn't been crawled yet.
        else {
            $timestamp = time();
            $sql = "
                INSERT INTO publisher_pages (    
                    publisher_id, url, created, modified, last_crawled
                ) VALUES (
                    '".$mysqli->real_escape_string($publisher['id'])."',
                    '".$mysqli->real_escape_string($url)."',
                    '".$mysqli->real_escape_string($timestamp)."',
                    '".$mysqli->real_escape_string($timestamp)."',
                    '0')";
            $mysqli->query($sql);     
            return $url;   
        }
        
    }
    return false;
}

// Fetch the links from a URL and store them in the publisher_pages table
function fetch_urls_from_a_page($mysqli, $publisher, $url) {
    echo "\r\n\r\n fetch_urls_from_a_page() - ".$url;

    // Clean up this url
    if ($url = clean_up_url($publisher['website'], $url)) {

        // Determine if this page has been crawled in the past month. If so, skip.
        if (has_page_been_crawled_in_past_month($mysqli, $publisher['id'], $url)) {
            echo "\r\n fetch_urls_from_a_page() - HAS BEEN CRAWLED IN PAST MONTH. SKIP. ".$url;
            return false;
        }
        
        // If this page has not been crawled in the past month, continue.
        else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERAGENT, 'Googlebot/2.1 (http://www.googlebot.com/bot.html)');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $html= curl_exec($ch);
        
            if (!$html) {
            	echo "\r\n cURL error number:" .curl_errno($ch);
            	echo "\r\n cURL error:" . curl_error($ch);
            	echo "\r\n error occurred trying to visit " . $url . "\r\n";
            } else {
                // Mark this page as having been crawled
                page_has_been_crawled($mysqli, $publisher['id'], $url);
                
                // Mark this page as having hrecipe, schema.org, or data-vocabulary.org markup
                page_is_hrecipe($mysqli, $publisher['id'], $url, $html);
                page_is_schemaorg($mysqli, $publisher['id'], $url, $html);
                page_is_datavocabularyorg($mysqli, $publisher['id'], $url, $html);
        
                // Parse the html into a DOMDocument
                $dom = new DOMDocument();
                @$dom->loadHTML($html);
        
                // grab all the on the page
                $xpath = new DOMXPath($dom);
                $hrefs = $xpath->evaluate("/html/body//a");
                echo "\r\n " . $hrefs->length . " links found. \r\n";
                        
                // Store all of the URLs in the publisher_pages table
                for ($i = 0; $i < $hrefs->length; $i++) {
                	$href = $hrefs->item($i);
                	$new_url = $href->getAttribute('href');
                	echo $new_url . "\r\n";
                    if ($new_url = store_url($mysqli, $publisher, $new_url)) { // Clean up and store the url
                        echo "\r\n";
                	    echo "Link stored: $new_url \r\n";
                	    echo "Page found on: $url \r\n";
            	    } else {
                	    // echo "\r\n " . $new_url . " was already saved once. Skipping. \r\n";
            	    }
                }
            }
        }
    } else {
    	echo "\r\n" . $url . " ain't from the " . $publisher['website'] . " website. Skipping. \r\n";
    }
}

// Fetch the links from a URL and store them in the publisher_pages table
function get_uncrawled_pages($mysqli, $publisher_id) {
    $sql = '
        SELECT * 
        FROM publisher_pages
        WHERE publisher_id = '.$mysqli->real_escape_string($publisher_id).'
        AND last_crawled <=  '.THIRTY_DAYS_AGO.'
        LIMIT 1000';
    if ($results = $mysqli->query($sql)) {
        $pages = array();
        while ($row = $results->fetch_assoc()) {
            $pages[] = $row;
        }
        return $pages;
    }
    return false;   
}

// Fetch the number of pages that have not been crawled yet on a publisher's site
function get_uncrawled_pages_count($mysqli, $publisher_id) {
    $sql = '
        SELECT COUNT(*) as count
        FROM publisher_pages
        WHERE publisher_id = '.$mysqli->real_escape_string($publisher_id).'
        AND last_crawled <=  '.THIRTY_DAYS_AGO;
    if ($results = $mysqli->query($sql)) {
        while ($row = $results->fetch_assoc()) {
            return $row['count'];
        }
    }
    return false;    
}

// Crawl all the pages that have not been crawled on a publisher's site
function crawl_uncrawled_pages($mysqli, $publisher) {
    if ($pages = get_uncrawled_pages($mysqli, $publisher['id'])) {
        foreach ($pages as $page) {
            fetch_urls_from_a_page($mysqli, $publisher, $page['url']);
            page_has_been_crawled($mysqli, $publisher['id'], $page['url']);
            sleep(3);
        }
        if (get_uncrawled_pages_count($mysqli, $publisher['id']) > 0) {
            crawl_uncrawled_pages($mysqli, $publisher); // re-run this function if there are no pages left.
        }
    } else {
        echo "\r\n There are no pages left to crawl on " . $publisher['website'] . "\r\n";
    }
}

// Mark a page as being in hrecipe format
function page_is_hrecipe($mysqli, $publisher_id, $url, $html) {
    if (stripos($html, ' hrecipe') || stripos($html, '"hrecipe') || stripos($html, ' h-recipe') || stripos($html, '"h-recipe')) {
        $mysqli->query("
            UPDATE publisher_pages 
            SET hrecipe = 1
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'
        ");
    }
}

// Mark a page as being in schema.org format
function page_is_schemaorg($mysqli, $publisher_id, $url, $html) {
    if (stripos($html, 'schema.org/recipe')) {
        $mysqli->query("
            UPDATE publisher_pages 
            SET schemaorg = 1
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'
        ");
    } 
}

// Mark a page as being in schema.org format
function page_is_datavocabularyorg($mysqli, $publisher_id, $url, $html) {
    $hrecipe = false;
    if (stripos($html, 'data-vocabulary.org')) {
        $mysqli->query("
            UPDATE publisher_pages 
            SET datavocabularyorg = 1
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'
        ");
    }
}

// Mark a page as having been crawled
function page_has_been_crawled($mysqli, $publisher_id, $url) {
    if (does_page_exist($mysqli, $publisher_id, $url)) {        
        $sql = "
            UPDATE publisher_pages 
            SET last_crawled = " . time() . " 
            WHERE publisher_id = '".$mysqli->real_escape_string($publisher_id)."'
            AND url = '".$mysqli->real_escape_string($url)."'";
        $mysqli->query($sql);
        if (!empty($mysqli->error)) {
            echo $mysqli->error;
            exit;
        }
    } 
}

// Mark a site as having been crawled
function site_has_been_crawled($mysqli, $publisher_id) {
    $mysqli->query("
        UPDATE publishers 
        SET last_crawled = " . time() . " 
        WHERE id = '".$mysqli->real_escape_string($publisher_id)."'
    ");
}


/****************
 * BEGIN SCRIPT *
 ****************/
 
// 1. Fetch a publisher
if ($publisher = fetch_publisher($mysqli)) {
    echo "\r\n ********************************************************************** ";
    echo "\r\n ** Step 1 - Publisher " . $publisher['website'] . " fetched from DB ** ";
    echo "\r\n ********************************************************************** \r\n";

    // 2. Store the publisher's home page in the db
    store_url($mysqli, $publisher, $publisher['website']);
    echo "\r\n ********************************************************************** ";
    echo "\r\n ** Step 2 - Publisher " . $publisher['website'] . " homepage stored in publisher_pages ** ";
    echo "\r\n ********************************************************************** \r\n";

    // 3. Fetch this publisher's home page and parse all the links
    fetch_urls_from_a_page($mysqli, $publisher, $publisher['website']);
    echo "\r\n ********************************************************************** ";
    echo "\r\n ** Step 3 - Publisher " . $publisher['website'] . " urls fetched from homepage ** ";
    echo "\r\n ********************************************************************** \r\n";
    
    // 4. Fetch the number of pages from this publisher that have not been crawled yet / or in last month
    $num_pages = get_uncrawled_pages_count($mysqli, $publisher['id']);
    echo "\r\n ********************************************************************** ";    
    echo "\r\n ** Step 4 - Publisher " . $publisher['website'] . " number of urls to crawl ** ";
    echo "\r\n ********************************************************************** \r\n";

    // 5. Crawl all urls for this publisher
    for ($num_pages; $num_pages > 0; $num_pages = get_uncrawled_pages_count($mysqli, $publisher['id'])) {
        echo "\r\n ********************************************************************** ";    
        echo "\r\n ** Step 5 - Publisher " . $publisher['website'] . " crawling uncrawled pages (on repeat) ** ";
        echo "\r\n ********************************************************************** \r\n";
        crawl_uncrawled_pages($mysqli, $publisher);
    }

    // 6. Mark this publisher as having been crawled
    site_has_been_crawled($mysqli, $publisher['id']);    
    echo "\r\n ********************************************************************** ";    
    echo "\r\n ** FINISHED ** Step 6 - Publisher " . $publisher['website'] . " has been crawled ** ";
    echo "\r\n ********************************************************************** \r\n";
}

?>