<!DOCTYPE html>
<html>
<head>
  <title>Shutterstock API PHP Sample Code</title>
</head>
<body>

Search:
<form method="get">
  <input type="text" name="search_terms">
  <input type="hidden" name="api_username" value="<?php echo $_GET['api_username'] ?>">
  <input type="hidden" name="api_key" value="<?php echo $_GET['api_key'] ?>">
  <input type="submit">
</form>

<?php

class ShutterstockAPI {

  protected $ch;
  protected $username;
  protected $key;

  public function __construct($username, $key) {
    $this->username = $username;
    $this->key      = $key;
  }

  protected function getCurl($url) {
    if (is_null($this->ch)) {
      $ch = curl_init();
      curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
      curl_setopt( $ch, CURLOPT_USERPWD, $this->username . ':' . $this->key );
      curl_setopt( $ch, CURLOPT_HEADER, 0 );
      curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
      $this->ch = $ch;
    }
    curl_setopt( $this->ch, CURLOPT_URL, $url );
    return $this->ch;
  }

  public function search($search_terms, $type='images') {
    $search_terms_for_url = preg_replace('/ /', '+', $search_terms);
    $url                  = 'http://api.shutterstock.com/' . $type . '/search.json?searchterm=' . $search_terms_for_url;
    $username             = $this->username;
    $key                  = $this->key;
    $ch                   = $this->getCurl( $url );
    $json                 = curl_exec( $ch );
    return json_decode( $json );
  }
}

$api_username = $_GET['api_username']; // Insert your API username here instead of GETting it from browser
$api_key      = $_GET['api_key'];      // Insert your API key here instead of GETting it from browser
$search_terms = $_GET['search_terms']; // Add your own security checks to cleanse this input

$api          = new ShutterstockAPI($api_username, $api_key);
$images       = $api->search($search_terms);
$videos       = $api->search($search_terms, 'videos');

if ($images) {
  for ( $i = 0; $i < 3; $i++ ) {
    $description  = $images->results[$i]->description;
    $thumb        = $images->results[$i]->thumb_large->url;
    $thumb_width  = $images->results[$i]->thumb_large_width;
    $thumb_height = $images->results[$i]->thumb_large_height;
    echo '<div style="display:inline-block;width:' . $thumb_width . 'px; height:' . $thumb_height . 'px; overflow:hidden;">';
    echo '<img src="' . $thumb . '" alt="' . $description . '">' . "\n\n";
    echo '</div>';
    echo '<textarea rows="10" cols="80">' . "\n";
    var_dump($images->results[$i]);
    echo "</textarea><br><hr>\n\n";
  }
}

if ($videos) {
  for ( $i = 0; $i < 3; $i++ ) {
    $description   = $videos->results[$i]->description;
    $thumb_mp4     = $videos->results[$i]->sizes->thumb_video->mp4_url;
    $thumb_webm    = $videos->results[$i]->sizes->thumb_video->webm_url;
    $preview_image = $videos->results[$i]->sizes->preview_image->url;
    echo '<img src="' . $preview_image . '" alt="' . $description . '">' . "\n\n";
    echo '<video controls="controls">' . "\n";
    echo "\t" . '<source src="' . $thumb_mp4  . '" type="video/mp4">'  .  "\n";
    echo "\t" . '<source src="' . $thumb_webm . '" type="video/webm">' . "\n";
    echo "</video>\n\n";
    echo '<textarea rows="10" cols="80">' . "\n";
    var_dump($videos->results[$i]);
    echo "</textarea><br><hr>\n\n";
  }
}

?>
</body>
</html>
