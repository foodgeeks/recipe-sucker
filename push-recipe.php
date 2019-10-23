<?php

/**** DATABASE ****/

// 'type'     => 'mysql',
// 'user'     => 'sandbox',
// 'pass'     => 'jj!1P9kTx!7',
// 'host'     => 'localhost',
// 'port'     => FALSE,
// 'socket'   => FALSE,
// 'database' => 'sandbox_foodgeeks_kohana'


$mysqli = mysqli_connect("localhost", "sandbox", "jj!1P9kTx!7", "publisher_recipes");
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
echo $mysqli->host_info . "\n";


$sql = "SELECT * FROM publisher_recipes.recipes WHERE id = 10 LIMIT 1";
$result = $mysqli->query($sql);
$row = mysqli_fetch_array($result);

$parameters = array(
   // 'user_id' => null,
   // 'publisher_id' => $row['publisher_id'],
   'title' => $row['title'],
   'teaser' => '',
   // 'recipe_type_id' => $row['recipe_type_id'],
   'url' => $row['url'],
   'yield' => $row['yield'],
   'prep_time' => $row['prep_time'],
   'cook_time' => $row['cook_time'],
   'total_time' => $row['total_time'],
   'ingredients' => $row['ingredients'],
   'instructions' => $row['instructions'],
   'photo_url' => $row['photo_url']
);

// $parameters = array(
//    'title' => 'taco',
//    'teaser' => '',
//    'url' => 'ulr tacoo',
//    'yield' => '', 
//    'prep_time' => '',
//    'cook_time' => '',
//    'total_time' => '',
//    'ingredients' => '',
//    'instructions' => '',
//    'photo_url' => ''
// );

//curl -v -X POST -d '{"url":"http://foodgeeks.com/more", "title":"TIGHTLE","teaser":"TEASER","yield":"YEELD","prep_time":"PREEEP TIME","cook_time":"COOOK TIMEEEE","total_time":"TOOOOOTAL TIME","ingredients":"INGRRREEDIENTS","instructions":"INSTRUXSHUNS","photo_url":"foo.com/photo"}' -H "Content-Type: application/json" 52.1.147.193:8080/recipes

include('./Fooddata.php');
$FD = new Fooddata;
// $response = $FD->recipePost($parameters);
$response =  $FD->recipeGet(8);
var_dump($response);

echo "PUSHED recipe number " . $row['id'] . " --> " . $row['title'];

exit;
