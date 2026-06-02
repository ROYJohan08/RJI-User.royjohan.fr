<?php
    session_start();
	$libs = ["config","errors","connexion"];
	foreach($libs as $lib){
		if(file_exists(__DIR__ . "/$lib.php")){
			require_once(__DIR__ . "/$lib.php");
		}
	}

    class controller{
        private Config $config;
        private User $user;
        private Connexion $connexion;
        private PDO $database;

        public function __construct(){
            $this->config = new Config();
            $database = new PDO(
                "mysql:host=".$this->config->get("BDDHOST").";dbname=".$this->config->get("BDDBASE").";charset=utf8mb4",
                $this->config->get("BDDUSERNAME"),
                $this->config->get("BDDPASSWORD"),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false
                ]
            );
            $this->user = new User($database);
            $this->connexion = new Connexion($database, __DIR__ . $this->config->get("PRIVATEKEYPATH"), __DIR__ . $this->config->get("PUBLICKEYPATH"));
        }
        public function init($code){
            if(password_verify($code, $this->config->get("ADMINCODEHASH"))){
                $this->user->checkDatabase();
                $userCard = new UserCard([
                    "nom"=>"ROY",
                    "prenom"=>"Johan",
                    "telephone"=>"0768587684",
                    "email"=>"contact@royjohan.fr",
                    "adresse" => "1 grande rue",
                    "codepostal"=> "08300",
                    "ville"=>"SEUIL",
                    "role" => Role::ADMINISTRATEUR
                ]);
                $this->user->save($userCard, new UserCard([
                    "uid" => 1,
                    "role" => Role::ADMINISTRATEUR
                ]));
                $this->connexion->checkDatabase();
                $connexionCard = new ConnexionCard([
                    "uid" => 1,
                    "user" => $userCard,
                    "telephone" => "0768587684",
                    "hash" => $this->config->get("ADMINCODEHASH")
                ]);
                $this->connexion->save($connexionCard, new UserCard([
                    "uid" => 1,
                    "role" => Role::ADMINISTRATEUR
                ]));
            }
            else{
                Errors::add("Code administrateur incorrect", ErrorLevel::ERROR);
            }
        }
        public function connect($telOrToken, $password=null, $memorize=false): bool {
            $card = $this->connexion->get($telOrToken, $password);
            if ($card !== null) {
                $_SESSION['auth'] = [
                    'token' => $card->getToken(),
                    'tokenValidity' => $card->getTokenValidity()
                ];
                if($memorize){
                    $expire = time() + 60 * 60 * 24 * 30;
                    setcookie('auth[token]', $card->getToken(), [
                        'expires'  => $expire,
                        'path'     => '/',
                        'secure'   => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                    setcookie('auth[tokenValidity]', $card->getTokenValidity(), [
                        'expires'  => $expire,
                        'path'     => '/',
                        'secure'   => true,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);
                }
                Errors::add("Vous êtes connecté Mr. " . $card->getUser()->getPrenom(), ErrorLevel::SUCCESS);
                return true;
            }
            return false;
        }
    }      
?>