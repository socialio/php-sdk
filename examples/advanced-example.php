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

require 'socialio.php';

$socialio = new Socialio(array(
  'clientId' => 'YOUR CLIENT NAME',
  'password' => 'YOUR CLIENT PASSWORD',
  'appName' => 'YOUR Social.io APP NAME'
));

try {
  $socialio->connect();
} catch (SocialIoApiException $e) {
  echo("Connection refused.");
  echo("Error from Social.io: " . $e . " END OF RESPONSE");
  exit();
}

$friends = $socialio->getFriends("user_id,name,picture");
?>

<!doctype html>
<html>
  <head>
    <title>The Social.io Advanced Example</title>
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
    <script>

        function loadUserProfile(){
            SIO.api({'method':'user.profile','user_id':'<?php echo($socialio->getUserId()); ?>','fields':''}, function(obj){alert(printf(obj));});
        };

        function loadUserFriends(){
            SIO.api({'method':'user.friends','user_id':'<?php echo($socialio->getUserId()); ?>','fields':''}, function(obj){alert(printf(obj));});
        };

        function inviteFriends(){
            SIO.ui({'method':'friends.invite', 'title':'Invite your friends!', 'message':'Hello friends, check this out!', 'picture':'http://static-test.platogo.com/games/1/achievements/2038714.png'}, function(response){alert(response.status);});
        };

        function publishStream(){
           SIO.ui({'method':'feed.publish', 'title':'Check this out!',
               'name':'Christoph needs the finest chocolate for Andre!',
               'caption': 'Christoph needs gourmet chocolate for Andre\'s signature recipes!',
               'description':'This a description.',
               'message':'Bitte helft mir!',
               'picture':'http://static-test.platogo.com/games/1/achievements/2038714.png',
               'action_link':'Send them Chocolate',
               'params':[{'name':'trackCode', value:'abcde'}, {name:'someId', value:'1234'}]},
                 function(response){alert(response.status);});
        };

        function postToWall(){
            SIO.ui({'method':'wall.post', 'title':'Check this out!',
                'name':'Awesome Wall Post',
                'recipient_ids':['<?php echo($friends['friends'][0]['user_id']) ?>'],
                'caption': 'I\'m a caption',
                'description':'I am in good mode!',
                'message':'Hello!',
                'picture':'http://static-test.platogo.com/games/1/achievements/2038714.png',
                'action_link':'Click Me',
                'params':[{'name':'trackCode', value:'abcde'}, {name:'someId', value:'1234'}]},
                  function(response){alert(response.status);});
        };

        function sendRequestToAFriend(filters){
           var reqObj = {'method':'request.send', 'title':'Check this out!',
                         'name':'Awesome Gift Request',
                         'recipient_ids':['<?php echo($friends['friends'][0]['user_id']) ?>'],
                         'caption': 'I\'m a caption',
                         'description':'I am in good mode!',
                         'message':'Hello!',
                         'picture':'http://static-test.platogo.com/games/1/achievements/2038714.png',
                         'action_link':'Accept Gift',
                         'params':[{'name':'giftId', value:'abcde'}]};
            if (filters)
              reqObj['filters'] = filters;
            SIO.ui(reqObj, function(response){alert(response.status + ', recipients:' + printf(response.uris));});
        };

        function sendRequest(){
            SIO.ui({'method':'request.send', 'title':'Check this out!',
                'name':'Awesome Gift Request',
                'caption': 'I\'m a caption',
                'description':'I am in good mode!',
                'message':'Hello!',
                'picture':'http://static-test.platogo.com/games/1/achievements/2038714.png',
                'action_link':'Accept Gift',
                'params':[{'name':'giftId', value:'abcde'}]},
                  function(response){alert(response.status + ', recipients:' + printf(response.uris));});
        };

        function printf(obj){
            var output = '';
            for (property in obj) {
              output += property + ': ' + (typeof(obj[property]) == 'object' ? printf(obj[property]) : obj[property])+'; ';
            }
            return output;
        };
    </script>
  </head>
  <body>
  <h1>The Social.io Advanced Example</h1>
    <p>User ID: <?php echo($socialio->getUserId()); ?></p>
    <h3>JavaScript SDK</h3>
    <p>
        <div><a href="#" onclick="inviteFriends(); return false;">Invite Friends</a></div>
        <div><a href="#" onclick="publishStream(); return false;">Publish Stream</a></div>
        <div><a href="#" onclick="postToWall(); return false;">Post To A Friends Wall</a></div>
        <div><a href="#" onclick="sendRequestToAFriend(); return false;">Send Request to a Friend</a></div>
        <div><a href="#" onclick="sendRequestToAFriend(['app_non_users']); return false;">Send Request to a Non App Friend</a></div>
        <div><a href="#" onclick="sendRequest(); return false;">Send Request</a></div>
        <div><a href="#" onclick="loadUserProfile(); return false;">User Profile</a></div>
        <div><a href="#" onclick="loadUserFriends(); return false;">User Friends</a></div>
    </p>
    <p><h4>User Profile:</h4> <?php $profile = $socialio->getUserProfile("user_id,name,picture");
                            echo("<table border='1'>");
                            echo("<tr><td>name</td><td>".$profile["name"]."</td></tr>");
                            echo("<tr><td>picture</td><td>".$profile["picture"]."</td></tr>");
                            echo("</table>");
                            ?></p>

    <?php $params = $socialio->getUserParams();
                            if (!empty($params)) {
                                echo("<p><h4>User Params:</p>");
                                echo("<table border='1'>");
                                foreach ($params as $key => $value) {
                                  echo("<tr><td>".$key."</td><td>".$value."</td></tr>");
                                }
                                echo("</table>");
                                echo("</p>");
                            }?>

    <p><h4>User Friend:</h4> <?php
                            echo("<table border='1'>");
                            echo("<tr><td>name</td><td>".$friends["friends"][0]["name"]."</td></tr>");
                            echo("<tr><td>picture</td><td>".$friends["friends"][0]["picture"]."</td></tr>");
                            echo("</table>");
                            ?></p>
    <p><h4>User Requests:</h4> <?php $requests = $socialio->getUserRequests();
                            echo("<table border='1'>");
                                foreach ($requests["requests"] as $each) {
                                  echo("<tr><td>"."from"."</td><td>".$each["from"]."</td></tr>");
                                  echo("<tr><td>"."id"."</td><td>".$each["id"]."</td></tr>");
                                  foreach ($each["params"] as $key => $value) {
                                    echo("<tr><td>".$key."</td><td>".$value."</td></tr>");
                                  }
                                }
                            echo("</table>");
                            ?></p>
    <p style="width:600px; word-wrap: break-word;">
      Token: <?php echo($socialio->getUserToken()); ?>
    </p>

  <script type="text/javascript">
        window.sioAsyncInit = function() {
            SIO.init({user_token: '<?php echo($socialio->getUserToken()) ?>'});
        };
        (function() {
            var sio = document.createElement('script');
            sio.type = 'text/javascript';
            sio.async = true;
            sio.src = document.location.protocol + '//static.social.io/sio.js';
            var s = document.getElementsByTagName('script')[0];
            s.parentNode.insertBefore(sio, s);
        })();
   </script>
  </body>
</html>
