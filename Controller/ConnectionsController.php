<?php

App::uses('ConnectionsAppController', 'Connections.Controller');

class ConnectionsController extends ConnectionsAppController {

    public $name = 'Connections';

    /*
     * You may activate email component here if you wish to add email authentication...
     * public $components = array(
     * 'Email',
     * );
     * 
     */

    public function authenticatewith() {

        require_once( WWW_ROOT . 'hybridauth/Hybrid/Auth.php' );

        $config = array(
            "base_url" => 'http://' . $_SERVER['HTTP_HOST'] . $this->base . "/hybridauth/", // well watev, set yours
            "providers" => array(
                "Twitter" => array(
                    "enabled" => true,
                    "keys" => array("key" => "YOUR_KEY_HERE", "secret" => "YOUR_SECRET_HERE")
                ),
                "Facebook" => array(
                    "enabled" => true,
                    "keys" => array("id" => "YOUR_ID_HERE", "secret" => "YOUR_SECRET_HERE"),
                    // A comma-separated list of permissions you want to request from the user. See the Facebook docs for a full list of available permissions: http://developers.facebook.com/docs/reference/api/permissions.
                    "scope" => "user_photos,user_videos,email,user_birthday,offline_access,publish_stream,status_update",
                    // The display context to show the authentication page. Options are: page, popup, iframe, touch and wap. Read the Facebook docs for more details: http://developers.facebook.com/docs/reference/dialogs#display. Default: page
                    "display" => "page"
                ),
                "Google" => array(
                    "enabled" => true,
                    "keys" => array("id" => "YOUR_ID_HERE", "secret" => "YOUR_SECRET_HERE"),
                    "scope" => ""
                ),
                "OpenID" => array(
                    "enabled" => true
                ),
                "Yahoo" => array(
                    "enabled" => true,
                    "keys" => array("id" => "YOUR_ID_HERE", "secret" => "YOUR_SECRET_HERE"),
                ),
                "AOL" => array(
                    "enabled" => true
                ),
            )
        );

        if (isset($this->request->params['named']['provider']) && $this->request->params['named']['provider']):
            
            /*Clean data*/
            
            
            App::uses('User', 'Users.Model');
            $this->User = new User();
            try {
                $hybridauth = new Hybrid_Auth($config);
                $provider = @ trim(strip_tags($this->request->params['named']['provider']));

                if ($provider == 'OpenID') {
                    $adapter = $hybridauth->authenticate("OpenID", array("openid_identifier" => "https://openid.stackexchange.com/"));
                } else {
                    $adapter = $hybridauth->authenticate($provider);
                }

                $user_profile = $adapter->getUserProfile();
                $this->set('user_profile', $user_profile);

                $identifier = $user_profile->identifier;

                if ($provider == 'Facebook' || $provider == 'Twitter') {
                    if ($provider == 'Facebook') {
                        $api = $adapter->api();
                        $access_token = $api->getAccessToken();
                        $secret = $api->getUser();
                    } elseif ($provider == 'Twitter') {
                        $session = $adapter->getAccessToken();
                        $access_token = $session['access_token'];
                        $secret = $session['access_token_secret'];
                    } else {
                        $access_token = NULL;
                        $secret = NULL;
                    }
                }

                $user = $this->User->find('first', array('conditions' => array('User.identifier' => $identifier)));

                if (!empty($user)) {
                    $this->Auth->fields = array('username' => 'username', 'password' => 'password');
                    $this->Auth->login($user['User']);
                    $this->redirect($this->Auth->redirect());
                } else {
                    if (!empty($user_profile->firstName)) {
                        $firstname = $user_profile->firstName;
                    } else {
                        $firstname = $user_profile->displayName;
                    }

                    if (!empty($user_profile->email)) {
                        $email = $user_profile->email;
                    } else {
                        $email = 'no@email.com';
                    }

                    $this->User->create();
                    $pass_key = $this->__random_password();
                    $user['User']['username'] = htmlspecialchars(str_replace(' ', '', $user_profile->displayName));
                    $user['User']['identifier'] = htmlspecialchars($user_profile->identifier);
                    $user['User']['role_id'] = 2;
                    $user['User']['name'] = htmlspecialchars($firstname);
                    $user['User']['lastname'] = htmlspecialchars($firstname);
                    $user['User']['email'] = $email;
                    $user['User']['password'] = AuthComponent::password($pass_key);
                    $user['User']['status'] = 1;

                    if ($this->User->save($user['User'])) {
                        Croogo::dispatchEvent('Controller.Users.registrationSuccessful', $this);
                        $user_details = $this->User->read(null, $this->User->getLastInsertId());

                        /*
                         * You can add sending email here if you like
                         */

                        Croogo::dispatchEvent('Controller.Users.beforeLogin', $this);

                        if ($this->Auth->login($user_details['User'])) {
                            Croogo::dispatchEvent('Controller.Users.loginSuccessful', $this);
                            $this->redirect($this->Auth->redirect());
                        } else {
                            Croogo::dispatchEvent('Controller.Users.loginFailure', $this);
                            $this->Session->setFlash($this->Auth->authError, 'default', array('class' => 'error'), 'auth');
                            $this->redirect($this->Auth->loginAction);
                        }
                    }
                }
            } catch (Exception $e) {

                switch ($e->getCode()) {
                    case 0 : $error = "Unspecified error.";
                        break;
                    case 1 : $error = "Hybriauth configuration error.";
                        break;
                    case 2 : $error = "Provider not properly configured.";
                        break;
                    case 3 : $error = "Unknown or disabled provider.";
                        break;
                    case 4 : $error = "Missing provider application credentials.";
                        break;
                    case 5 : $error = "Authentification failed. The user has canceled the authentication or the provider refused the connection.";
                        break;
                    case 6 : $error = "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.";
                        $adapter->logout();
                        break;
                    case 7 : $error = "User not connected to the provider.";
                        $adapter->logout();
                        break;
                }

                // well, basically your should not display this to the end user, just give him a hint and move on..
                $error .= "<br /><br /><b>Original error message:</b> " . $e->getMessage();
                $error .= "<hr /><pre>Trace:<br />" . $e->getTraceAsString() . "</pre>";
                $this->Session->setFlash($error, 'default', array('class' => 'error'));
                $this->redirect('/');
            }
        endif;
    }

    protected function __random_password($minlength = 20, $maxlength = 20, $useupper = true, $usespecial = false, $usenumbers = true) {
        $charset = "abcdefghijklmnopqrstuvwxyz";
        if ($useupper)
            $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        if ($usenumbers)
            $charset .= "0123456789";
        if ($usespecial)
            $charset .= "~@#$%^*()_+-={}|][";
        if ($minlength > $maxlength)
            $length = mt_rand($maxlength, $minlength);
        else
            $length = mt_rand($minlength, $maxlength);
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $charset[(mt_rand(0, (strlen($charset) - 1)))];
        }
        return $key;
    }

}

