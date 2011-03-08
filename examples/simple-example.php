<?php
/**
 *
 * Copyright 2011 Platogo Interactive Entertainment GmbH
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

require '../src/socialio.php';

$socialio = new Socialio(array(
	'clientId' => 'YOUR CLIENT NAME',
	'password' => 'YOUR CLIENT PASSWORD',
	'appId' => 'YOUR Social.io APP NAME',
	'incomingRequest' => $_REQUEST,
));

try {
  $socialio->connect();
} catch (SocialIoApiException $e) {
  echo("Connection refused.");
  echo("Error from Social.io: " . $e . " END OF RESPONSE");
  exit();
}

$friends = $socialio->getFriends();
?>

<!doctype html>
<html>
  <head>
    <title>The Social.io Simple Example</title>
    <style>
      body {
        font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
      }
      h1 a {
        text-decoration: none;
        color: #3b5998;
      }
      h1 a:hover {
        text-decoration: underline;
      }

      p {
        width: 700px;
      }

    </style>
  </head>
  <body>
    <h1>The Social.io Simple Example</h1>
    <p>User ID: <?php echo($socialio->getUserId()); ?></p>
    <p><h4>User Profile:</h4> <?php $profile = $socialio->getUserProfile();
                            echo("<table border='1'>");
                            echo("<tr><td>name</td><td>".$profile["name"]."</td></tr>");
                            echo("<tr><td>picture</td><td>".$profile["picture"]."</td></tr>");
                            echo("</table>");
                            ?></p>

    <p><h4>User Friend:</h4> <?php
                            echo("<table border='1'>");
                            echo("<tr><td>name</td><td>".$friends["friends"][0]["name"]."</td></tr>");
                            echo("<tr><td>picture</td><td>".$friends["friends"][0]["picture"]."</td></tr>");
                            echo("</table>");
                            ?></p>
    <p style="width:600px; word-wrap: break-word;">
      Token: <?php echo($socialio->getUserToken()); ?>
    </p>
  </body>
</html>

