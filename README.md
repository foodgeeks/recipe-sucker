# recipe-sucker
Crawl, suck, and prep recipes from external recipe sites to be imported into Foodgeeks.

## How do I install this thing?
* Clone.
* Install Composer and services (see composer.json for requirements)

* run from mysql command line: 
```
CREATE DATABASE `publisher_recipes` /*!40100 DEFAULT CHARACTER SET utf8 */
GRANT SELECT, INSERT, UPDATE, DELETE ON publisher_recipes TO 'sandbox'@'localhost' IDENTIFIED BY '';
```

* run from command line: 
```
mysql -u root -h localhost publisher_recipes < publisher_recipes.sql
```

## 1. Crawl

First, we crawl any site that is in the publishers table that have not been crawled in the past 30 days. This will store all of the pages that are on a particular site, and will ignore any pages that are definitely not a recipe (e.g. files ending in .jpg, .gif, .pdf, etc.). We also make note if we recognize either hrecipe or schema.org microformats on the site so that suck.php knows which could be valid recipes to suck in.

Note: We crawl a page every 3 seconds in order to be good citizens and not DDOS a server.

```
php crawl.php
```

Eventually this will be updated to:
* crawl every home page nightly to get new blog posts & recipe entries
* recognize data-vocabulary microformats

## 2. Suck

Second, we suck in any pages that have an hrecipe = 1 or schemaorg = 1 flag in the `publisher_pages` table. If we suck in a valid recipe, we will store that in the `publisher_recipes` table. If we find a recipe photo within that markup, we suck it in and push it to amazon s3.

We're storing recipes in this table exactly how they are formatted in their schema markup. We prepare the data for import in the next step.

```
php suck.php
```

Eventually this will be updated to:
* suck in data-vocabulary schema markup

In 1 year this could be updated to recognize any recipe even without microformats. But that's not a priority right now. Now, we're focused on getting a shit-ton of data. In the long run, we'll focus on getting all the recipes in the world in any language.

## 3. Prep for Import

The goal for this process is to pull in to prepare the data in `publisher_recipes` to be normalized and formatted properly for the recipes table in Foodgeeks, and eventually for the recipe data service for Foodgeeks. We'll store these pre-formatted recipes in the `recipes_prepped_for_import`.

```
php prep.php
```

## 4. Data Import

Perform a mysqldump on the publisher_recipes.publishers table and the publisher_recipes.recipes_prepped_for_import table, scp them both to the server, and import them into the sandbox and production databases. (Ideally push a backup of these databases to S3 as well.)

In the foodgeeks.com/scripts directory, run the following 3 commands:

```
php cleanup-recipes-after-import.php
php import_publisher_recipes.php
php import_publisher_recipe_photos.php
```

```Import_publisher_recipes``` will automatically import recipes that are 100% matches for recipe types.

## 5. Import Recipes

This takes place in the Foodgeeks main site. Once you're logged in as an admin, visit the following page to import n number of recipes at a time and ensure that they are tagged with the correct recipe types:

https://foodgeeks.com/admin/import_recipes
