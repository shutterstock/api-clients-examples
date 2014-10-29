<?php

// We use the session to persist our access token
session_start();

$client_id = ''; // Insert your CLIENT ID here.
$client_secret = ''; // Insert your CLIENT SECRET here.

// Set redirect uri to the current url
$redirect_uri = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . "{$_SERVER['HTTP_HOST']}" . strtok($_SERVER['REQUEST_URI'], '?');

// Retrieve access token if we have an access code
if(isset($_GET['code'])) {
  $url = 'https://accounts.shutterstock.com/oauth/access_token';

  $params = array(
      code => $_GET['code'],
      client_id => $client_id,
      client_secret => $client_secret,
      redirect_uri => $redirect_uri,
      grant_type => 'authorization_code'
  );

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
  curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

  $response = curl_exec($ch);
  curl_close($ch);

  $json = json_decode($response);

  if (json_last_error()) {
    echo '<span style="font-weight:bold;color:red;">Error: ' . $response . '</span>';
  } else {
    $_SESSION['access_token'] = $json->access_token;
  }
}

class ShutterstockAPI {
  protected $accessToken;

  public function __construct($accessToken) {
    $this->accessToken = $accessToken;
  }

  public function search($search_terms, $type = 'images') {
    $search_terms_for_url = preg_replace('/\s/', '+', $search_terms);
    $url = 'https://api.shutterstock.com/v2/' . $type . '/search?view=full&per_page=5&query=' . $search_terms_for_url;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->accessToken));
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
  }
}

?>

<!DOCTYPE html>
<html>
<head>
  <title>Shutterstock API v2 PHP Sample Code</title>
</head>
<body>

<h1>Step 1: Authenticate</h1>

<form method="get" action="https://accounts.shutterstock.com/oauth/authorize">
  <div>
    <b>Client ID:</b> <?php echo $client_id ?>
    <input type="text" name="client_id" value="<?php echo $client_id ?>" hidden/>
  </div>
  <div>
    <b>Client Secret:</b> <?php echo $client_secret ?>
    <input type="text" name="client_secret" value="<?php echo $client_secret ?>" hidden/>
  </div>
  <div>
    <b>Redirect URI:</b> <?php echo $redirect_uri ?>
    <input type="text" name="redirect_uri" value="<?php echo $redirect_uri ?>" hidden/>
  </div>
  <div>
    <b>Access Token:</b>
    <textarea type="text" name="access_token" rows="8" cols="40" disabled><?php echo $_SESSION['access_token'] ?></textarea>
  </div>

  <input type="submit" value="Get Access Token">
</form>

<h1>Step 2: Search</h1>

<form method="get">
  <label for="search_terms">Search Terms: </label>
  <input type="text" id="search_terms" name="search_terms" value="<?php echo $_GET['search_terms'] ?>"/>
  <input type="submit" value="Search" <?php if (!$_SESSION['access_token']) echo 'disabled' ?>>
</form>

<br/>

<?php

if (isset($_GET['search_terms'])) {
  $search_terms = $_GET['search_terms']; // Add your own security checks to cleanse this input

  $api = new ShutterstockAPI($_SESSION['access_token']);
  $images = $api->search($search_terms);
  $videos = $api->search($search_terms, 'videos');

  echo '<h1>Images</h1>';

  if ($images) {
    for ($i = 0; $i < 3; $i++) {
      $description  = $images->data[$i]->description;
      $thumb = $images->data[$i]->assets->large_thumb->url;
      $thumb_width = $images->data[$i]->assets->large_thumb->width;
      $thumb_height = $images->data[$i]->assets->large_thumb->height;

      echo '<div style="display:inline-block;width:' . $thumb_width . 'px; height:' . $thumb_height . 'px; overflow:hidden;">';
      echo '<img src="' . $thumb . '" alt="' . htmlspecialchars($description) . '">' . "\n\n";
      echo '</div>';

      echo '<textarea rows="10" cols="80">' . "\n";
      var_dump($images->data[$i]);
      echo "</textarea><br><hr>\n\n";
    }
  }

  echo '<h1>Videos</h1>';

  if ($videos) {
    for ($i = 0; $i < 3; $i++) {
      $description = $videos->data[$i]->description;
      $thumb_mp4 = $videos->data[$i]->assets->preview_mp4->url;
      $thumb_webm = $videos->data[$i]->assets->preview_webm->url;
      $preview_image = $videos->data[$i]->assets->preview_jpg->url;

      echo '<img src="' . $preview_image . '" alt="' . $description . '">' . "\n\n";

      echo '<video controls="controls">' . "\n";
      echo "\t" . '<source src="' . $thumb_mp4  . '" type="video/mp4">'  .  "\n";
      echo "\t" . '<source src="' . $thumb_webm . '" type="video/webm">' . "\n";
      echo "</video>\n\n";

      echo '<textarea rows="10" cols="80">' . "\n";
      var_dump($videos->data[$i]);
      echo "</textarea><br><hr>\n\n";
    }
  }
}

?>

</body>
</html>
