<?php

	require_once(__DIR__ . "/errors.php");
	require_once(__DIR__ . "/user.php");

	class ConnexionCard {
		private ?int      $cid           = null;
		private ?int      $uid           = null;
		private ?UserCard $user          = null;
		private ?string   $telephone     = null;
		private ?string   $token         = null;
		private ?string   $tokenValidity = null;
		private ?string   $tryHistory	 = null;
		private ?string   $hash		     = null;

		public function __construct(array $data=null){
			if($data!=null && !empty($data)) {	
				$this->hydrate($data);
			}
		}

		public function hydrate(array $data): void {
			foreach ($data as $key => $value) {
				$method = 'set' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
				if (method_exists($this, $method)) {
					$ok = $this->$method($value);
					if (!$ok) {
						Errors::add("Hydrate: impossible d’hydrater '$key' avec la valeur '" . print_r($value, true) . "'", ErrorLevel::WARNING);
					}
				}
				else {
					Errors::add("Hydrate: propriété inconnue '$key' ignorée", ErrorLevel::INFO);
				}
			}
		}

		public function getCid(): ?int {
			return $this->cid;
		}
		public function getUid(): ?int {
			return $this->uid;
		}
		public function getUser(): ?UserCard { 
			return $this->user;
		}
		public function getTelephone(): ?string { 
			return $this->telephone;
		}
		public function getToken(): ?string { 
			return $this->token;
		}
		public function getTokenValidity(): ?string { 
			return $this->tokenValidity;
		}
		public function getTryHistory(): ?string { 
			return $this->tryHistory;
		}
		public function getHash(): ?string { 
			return $this->hash;
		}
	
		public function setCid($v): bool {
			if ($v === null) { 
				$this->cid = null;
				return true;
			}
			if (!is_numeric($v)) {
				Errors::add("cid invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->cid = (int) $v;
			return true;
		}

		public function setUid($v): bool {
			if ($v === null) { 
				$this->uid = null; 
				return true; 
			}
			if (!is_numeric($v)) {
				Errors::add("uid invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->uid = (int) $v;
			return true;
		}

		public function setUser(UserCard $v): bool {
			if ($v === null) {
				$this->user = null;
				return true;
			}
			if (!($v instanceof UserCard)) {
				Errors::add("Format user invalide (UserCard attendu) : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->user = $v;
			$this->uid = $v->getUid();
			return true;
		}

		public function setTelephone($v): bool {
			if ($v === null) {
				$this->telephone = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("telephone invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			if (!preg_match('/^(?:\+33|0)[1-9](?:[ .-]?\d{2}){4}$/', $v)) {
				Errors::add("telephone invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$v = str_replace(['.', ' ', '-'], '', $v);
			$this->telephone = trim($v);
			return true;
		}

		public function setToken($v): bool {
			if ($v === null) {
				$this->token = null;
				return true;
			}

			if (!is_string($v)) {
				Errors::add("Format token invalide (string attendu) : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			$v = trim($v);
			if (!preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $v)) {
				Errors::add("JWT invalide : " . $v, ErrorLevel::ERROR);
				return false;
			}

			$this->token = $v;
			return true;
		}
		
		public function setTokenValidity($v): bool {
			if ($v === null) {
				$this->tokenValidity = null;
				return true;
			}
			if ($v instanceof \DateTimeInterface) {
				$this->tokenValidity = $v->format('Y-m-d H:i:s');
				return true;
			}
			if (is_int($v) || ctype_digit((string)$v)) {
				$this->tokenValidity = date('Y-m-d H:i:s', (int)$v);
				return true;
			}
			if (is_string($v)) {
				$date = date_create($v);
				if ($date === false) {
					Errors::add("Validité de token invalide : " . print_r($v, true), ErrorLevel::ERROR);
					return false;
				}
				$this->tokenValidity = $date->format('Y-m-d H:i:s');
				return true;
			}
			Errors::add("Format de validité de token invalide : " . print_r($v, true), ErrorLevel::ERROR);
			return false;
		}		 

		public function setTryHistory($v): bool {
			if ($v === null) {
				$this->tryHistory = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Format tryHistory invalide (string attendu) : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->tryHistory = $v;
			return true;
		}

		public function setHash($v): bool {
			if ($v === null) {
				$this->hash = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Format hash invalide (string attendu) : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->hash = password_hash($v, PASSWORD_DEFAULT);
			return true;
		}
	}


	class Connexion {

		public function __construct(private PDO $Database, private string $PrivateKeyPath, private string $PublicKeyPath){
			if (!file_exists($this->PrivateKeyPath) || !is_readable($this->PrivateKeyPath)) {
				Errors::add("Clé privée introuvable ou non lisible : " . $this->PrivateKeyPath, ErrorLevel::ERROR);
			}

			if (!file_exists($this->PublicKeyPath) || !is_readable($this->PublicKeyPath)) {
				Errors::add("Clé publique introuvable ou non lisible : " . $this->PublicKeyPath, ErrorLevel::ERROR);
				throw new \InvalidArgumentException('Public key file not found or not readable');
			}
		}

		public function get(string $telOrToken, string $password = null): ?ConnexionCard{
			if($password==null){
				if($this->isLocked()){
					Errors::add("Trop de tentatives échouées, veuillez réessayer plus tard", ErrorLevel::WARNING);
					return null;
				}
				$payload = $this->decodeJwt($telOrToken);
				if ($payload === null) {
					Errors::add("Token invalide ou expiré", ErrorLevel::ERROR);
					$this->addTry(null,"EmptyPayload");
					return null;
				}
				if (!isset($payload['uid'], $payload['cid'], $payload['exp'])) {
					Errors::add("Token incomplet", ErrorLevel::ERROR);
					$this->addTry(null,"IncorrectPayload");
					return null;
				}
				if ($payload['exp'] < time()) {
					Errors::add("Token expiré", ErrorLevel::ERROR);
					$this->addTry(null,"ExpiredToken");
					return null;
				}
				$uid = (int)$payload['uid'];
				$cid = (int)$payload['cid'];
				$stmt = $this->Database->prepare("SELECT cid, uid, token, telephone, tryHistory FROM user_connexion WHERE cid = :cid AND uid = :uid LIMIT 1 ");
				$stmt->execute([':cid' => $cid, ':uid' => $uid]);
				$row = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$row) {
					Errors::add("Utilisateur introuvable", ErrorLevel::ERROR);
					$this->addTry(null,"UnknownUser");
					return null;
				}
				if($this->isLocked($row['telephone'] ?? null)){
					Errors::add("Trop de tentatives échouées, veuillez réessayer plus tard", ErrorLevel::WARNING);
					$this->addTry($row['telephone'],"ForceLock");
					return null;
				}
				if($row['token']!=$telOrToken){
					Errors::add("Le token ne correspond pas à lutilisateur : " . $row['token'], ErrorLevel::WARNING);
					$this->addTry($row['telephone'],"OldToken");
					return null;
				}
				$user = (new User($this->Database))->get($uid);
				if ($user === null) {
					Errors::add("Utilisateur introuvable", ErrorLevel::ERROR);
					$this->addTry($row['telephone'],"UnknownUser");
					return null;
				}
				$card = new ConnexionCard([]);
				$card->setCid($cid);
				$card->setUid($uid);
				$card->setUser($user);
				$card->setTelephone($row['telephone']);
				$card->setToken($telOrToken);
				$card->setTokenValidity(date('Y-m-d H:i:s', $payload['exp']));
				$card->setTryHistory($row['tryHistory']);
				$_SESSION['try']=0;
				$stmt = $this->Database->prepare("UPDATE user_connexion SET lastConnexion = NOW() WHERE cid = :cid");
				$stmt->execute([':cid' => $connexionRow['cid']]);
				return $card;
			}
			else{
				$normalizedTelephone = $this->normalizeFrenchTelephone($telOrToken);
				if($this->isLocked($normalizedTelephone ?? null)){
					Errors::add("Trop de tentatives échouées, veuillez réessayer plus tard", ErrorLevel::WARNING);
					$this->addTry(null,"ForceLock");
					return null;
				}
				if ($normalizedTelephone === null) {
					Errors::add("Téléphone invalide, attendu un numéro français", ErrorLevel::ERROR);
					$this->addTry(null,"EmptyData");
					return null;
				}
				$stmt = $this->Database->prepare("SELECT cid, uid, telephone, hash, tryHistory FROM user_connexion WHERE telephone = :telephone LIMIT 1");
				$stmt->execute([':telephone' => $normalizedTelephone]);
				$connexionRow = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$connexionRow) {
					Errors::add("Connexion introuvable", ErrorLevel::ERROR);
					$this->addTry(null,"UnknownUser");
					return null;
				}
				$user = (new User($this->Database))->get($connexionRow['uid']);
				if ($user === null) {
					Errors::add("Utilisateur introuvable", ErrorLevel::ERROR);
					$this->addTry($row['telephone'],"UnknownUser");
					return null;
				}
				if (!password_verify($password, $connexionRow['hash'])) {
					$this->addTry($normalizedTelephone,"IncorrectPassword");
					Errors::add("Mot de passe incorrect", ErrorLevel::ERROR);
					return null;
				}
				$payload = [
					'uid'  => (int) $connexionRow['uid'],
					'cid'  => (int) $connexionRow['cid'],
					'tel'  => $connexionTelephone,
					'role' => $user->getRoles(),
					'iat'  => time(),
					'exp'  => time() + 86400 // 24h
				];
				$jwt = $this->generateJwt($payload);
				if (!$jwt) {
					Errors::add("Erreur lors de la génération du JWT", ErrorLevel::ERROR);
					return null;
				}
				$card = new ConnexionCard([]);
				$card->setCid($connexionRow['cid']);
				$card->setUid($connexionRow['uid']);
				$card->setUser($user);
				$card->setTelephone($normalizedTelephone);
				$card->setToken($jwt);
				$card->setTokenValidity(date('Y-m-d H:i:s', $payload['exp']));
				$card->setTryHistory($connexionRow['tryHistory']);
				$stmt = $this->Database->prepare("UPDATE user_connexion SET lastConnexion = NOW(), token=:token, tokenValidity=:tokenValidity WHERE cid = :cid");
				$stmt->execute([':cid' => $connexionRow['cid'],':token' => $jwt, ':tokenValidity' => date('Y-m-d H:i:s', $payload['exp'])]);
				$_SESSION['try']=0;
				return $card;
			}
		}

		public function save(ConnexionCard $cible, UserCard $modificateur): ?int {
			if ($modificateur->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return 0;
			}
			$isAdmin = (
				$modificateur->hasRole(Role::ADMINISTRATEUR) ||
				$modificateur->hasRole(Role::TECHNICIEN) ||
				$modificateur->hasRole(Role::COMMERCIAL)
			);
			$isCreation = ($cible->getCid() === null);
			if ($isCreation && !$isAdmin) {
				Errors::add("Droits insuffisants pour créer un utilisateur", ErrorLevel::ERROR);
				return 0;
			}
			if (!$isCreation && $modificateur->getUid() !== $cible->getUid() && !$isAdmin) {
				Errors::add("Droits insuffisants pour modifier cet utilisateur", ErrorLevel::ERROR);
				return 0;
			}
			try {
				if ($isCreation) {
					$stmt = $this->Database->prepare("INSERT INTO user_connexion (uid, token, tokenValidity, tryHistory, hash, telephone) VALUES (:uid, :token, :tokenValidity, :tryHistory, :hash, :telephone)");
					$stmt->execute([':uid' => $cible->getUid(),':token' => $cible->getToken(),':tokenValidity' => $cible->getTokenValidity(),':tryHistory' => "",':hash' => $cible->getHash(),':telephone' => $cible->getTelephone()]);
					$cid = (int)$this->Database->lastInsertId();
					$cible->setCid($cid);
					return $cid;
				}
				$setParts = [
					"uid = :uid",
					"token = :token",
					"tokenValidity = :tokenValidity",
					"tryHistory = :tryHistory"
				];
				$params = [
					':uid' => $cible->getUid(),
					':token' => $cible->getToken(),
					':tokenValidity' => $cible->getTokenValidity(),
					':tryHistory' => $cible->getTryHistory(),
					':cid' => $cible->getCid()
				];

				if ($cible->getHash() !== null) {
					$setParts[] = "hash = :hash";
					$params[':hash'] = $cible->getHash();
				}
				$sql = "UPDATE user_connexion SET " . implode(",\n\t\t\t\t", $setParts) . " WHERE cid = :cid";
				$stmt = $this->Database->prepare($sql);
				$stmt->execute($params);
				return $cible->getCid();
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return 0;
			}

		}

		public function del(ConnexionCard $cible, UserCard $modificateur): bool {
			if ($modificateur->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return false;
			}
			$isAdmin = (
				$modificateur->hasRole(Role::ADMINISTRATEUR) ||
				$modificateur->hasRole(Role::TECHNICIEN) ||
				$modificateur->hasRole(Role::COMMERCIAL)
			);
			if (!$isAdmin) {
				Errors::add("Droits insuffisants pour supprimer cet utilisateur", ErrorLevel::ERROR);
				return false;
			}
			try {
				$stmt = $this->Database->prepare("DELETE FROM user_connexion WHERE cid = :cid");
				$stmt->execute([':cid' => $cible->getCid()]);
				return true;
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return false;
			}
		}
		public function checkDatabase(): bool {
			$table = 'user_connexion';
			$stmt = $this->Database->query("SHOW TABLES LIKE '$table'");
			if ($stmt === false) {
				Errors::add("Impossible d'exécuter SHOW TABLES", ErrorLevel::ERROR);
				return false;
			}
			if ($stmt->rowCount() === 0) {
				Errors::add("La table '$table' est absente", ErrorLevel::WARNING);
				return $this->createDatabase();
			}
			$expectedColumns = ['cid', 'uid', 'telephone', 'hash', 'token','tokenValidity', 'tryHistory', 'lastConnexion'];
			$columns = $this->Database->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
			foreach ($expectedColumns as $col) {
				if (!in_array($col, $columns, true)) {
					Errors::add("Colonne manquante : $col", ErrorLevel::WARNING);
					return $this->createDatabase();
				}
			}
			return true;
		}

		private function normalizeFrenchTelephone(string $telephone): ?string {
			$clean = preg_replace('/\D+/', '', $telephone);

			if (str_starts_with($clean, '0033')) {
				$clean = '0' . substr($clean, 4);
			} elseif (str_starts_with($clean, '33')) {
				$clean = '0' . substr($clean, 2);
			}

			if (!preg_match('/^0[1-9]\d{8}$/', $clean)) {
				return null;
			}

			return $clean;
		}

		private function createDatabase(bool $forceDrop = true): bool {
			$table = 'user_connexion';
			if ($forceDrop) {
				$this->Database->exec("DROP TABLE IF EXISTS $table");
			}
			$sql = "
				CREATE TABLE IF NOT EXISTS $table ( cid INT PRIMARY KEY AUTO_INCREMENT, uid INT NOT NULL, telephone VARCHAR(20), hash VARCHAR(255), token VARCHAR(512) NULL, tokenValidity DATETIME NULL, tryHistory LONGTEXT NULL, lastConnexion DATETIME NULL ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

			try {
				$this->Database->exec($sql);
				return true;
			}
			catch (PDOException $e) {
				Errors::add("Erreur création base : " . $e->getMessage(), ErrorLevel::ERROR);
				return false;
			}
		}

		private function generateJwt(array $payload): string|false {
			$header = ['alg' => 'RS256','typ' => 'JWT'];
			$headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
			$payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
			$signatureInput = $headerEncoded . '.' . $payloadEncoded;
			$privateKey = openssl_pkey_get_private(file_get_contents($this->PrivateKeyPath));
			if (!$privateKey) {
				Errors::add("Impossible de charger la clé privée", ErrorLevel::ERROR);
				return false;
			}
			if (!openssl_sign($signatureInput, $signature, $privateKey, 'sha256')) {
				Errors::add("Erreur lors de la signature du JWT", ErrorLevel::ERROR);
				return false;
			}
			$signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
			return $signatureInput . '.' . $signatureEncoded;
		}

		private function decodeJwt(string $token): ?array {
			$parts = explode('.', $token);
			if (count($parts) !== 3) {
				Errors::add("Format de token invalide", ErrorLevel::WARNING);
				return null;
			}
			[$header64, $payload64, $signature64] = $parts;
			$signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $signature64));
			if ($signature === false) {
				Errors::add("Signature JWT invalide", ErrorLevel::WARNING);
				return null;
			}
			$dataToVerify = $header64 . '.' . $payload64;
			$publicKey = file_get_contents($this->PublicKeyPath);
			if ($publicKey === false) {
				Errors::add("Impossible de lire la clé publique", ErrorLevel::ERROR);
				return null;
			}
			$verified = openssl_verify($dataToVerify, $signature, $publicKey, OPENSSL_ALGO_SHA256);
			if ($verified !== 1) {
				Errors::add("Signature JWT invalide", ErrorLevel::WARNING);
				return null;
			}
			$payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload64));
			if ($payloadJson === false) {
				Errors::add("Payload JWT invalide", ErrorLevel::WARNING);
				return null;
			}
			$payload = json_decode($payloadJson, true);
			if (!is_array($payload)) {
				Errors::add("Payload JWT non décodable", ErrorLevel::WARNING);
				return null;
			}
			if (isset($payload['exp']) && (int)$payload['exp'] < time()) {
				Errors::add("Token expiré", ErrorLevel::WARNING);
				return null;
			}
			return $payload;
		}

		private function isLocked($telephone=null):bool {
			if(isset($_SESSION['try']) && $_SESSION['try']>=4){ return true; }
			if($telephone === null){ return false;}
			$stmt = $this->Database->prepare("SELECT tryHistory FROM user_connexion WHERE telephone = :telephone LIMIT 1");
			$stmt->execute([':telephone' => $telephone]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!$row) {
				Errors::add("Connexion introuvable pour le téléphone : $telephone", ErrorLevel::ERROR);
				return false;
			}
			if($row['tryHistory']) {
				$history = json_decode($row['tryHistory'], true) ?? [];
				$now = new \DateTime();
				$ago24h = (new \DateTime())->modify('-24 hours');
				$countLast24h = 0;
				foreach ($history as $attempt) {
					if (!isset($attempt['date'], $attempt['time'])) continue;
					try {
						$attemptTime = \DateTime::createFromFormat('Y-m-d H:i:s', $attempt['date'] . ' ' . $attempt['time']);
						if ($attemptTime && $attemptTime >= $ago24h) {
							$countLast24h++;
						}
					} catch (\Exception $e) {
						continue;
					}
				}
				if ($countLast24h >= 4) {
					return true;
				}
			}
			return false;
		}

		private function addTry($telephone, $value): bool {
			if (!$telephone) {
				if(!isset($_SESSION['try'])){
					$_SESSION['try'] = 0;
				}
				$_SESSION['try']++;
				return true;
			}
			$stmt = $this->Database->prepare("SELECT tryHistory FROM user_connexion WHERE telephone = :telephone LIMIT 1");
			$stmt->execute([':telephone' => $telephone]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$row) {
				Errors::add("Connexion Tel=$telephone introuvable", ErrorLevel::LOG);
				return false;
			}
			$history = [];
			if ($row['tryHistory']) {
				$history = json_decode($row['tryHistory'], true) ?? [];
			}
			$newAttempt = [
				'date'  => date('Y-m-d'),
				'time'  => date('H:i:s'),
				'type'  => $value ?? 'unknown',
				'ip'    => $_SERVER['REMOTE_ADDR'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
			];
			array_unshift($history, $newAttempt);
			$history = array_slice($history, 0, 10);
			$stmt = $this->Database->prepare("UPDATE user_connexion SET tryHistory = :tryHistory WHERE cid = :cid");
			$updated = $stmt->execute([
				':tryHistory' => json_encode($history),
				':telephone'        => $telephone
			]);
			if(!isset($_SESSION['try'])){
				$_SESSION['try'] = 0;
			}
			$_SESSION['try']++;
			return $updated ? true : false;
		}

	}
?>