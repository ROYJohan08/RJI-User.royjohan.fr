<?php
	/**
	 * Gestion des connexions utilisateurs.
	 *
	 * Ce fichier définit les classes de connexion et les règles
	 * de validation des données de connexion.
	 *
	 * @package ROYJohanInfo
	 */

	require_once(__DIR__ . "/errors.php");
	require_once(__DIR__ . "/user.php");


	/**
	 * Représente une fiche de connexion pour un utilisateur.
	 */
	class ConnexionCard {
		private ?int    $cid           = null;
		private ?int    $uid           = null;
		private ?string $telephone     = null;
		private ?string $token         = null;
		private ?string $tokenValidity = null;
		private ?bool   $locked        = null;
		private ?string $try           = null;
		
		/**
		 * Constructeur.
		 *
		 * @param array $data Données initiales à hydrater
		 */
		public function __construct(array $data){
			$this->hydrate($data);
		}

		/**
		 * Hydrate l'objet à partir d'un tableau associatif.
		 *
		 * @param array $data Données à affecter aux propriétés
		 * @return void
		 */
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
		
		public function getCid(): ?int              { return $this->cid; }
		public function getUid(): ?int              { return $this->uid; }
		public function getTelephone(): ?string     { return $this->telephone; }
		public function getToken(): ?string         { return $this->token; }
		public function getTokenValidity(): ?string { return $this->tokenValidity; }
		public function getLocked(): ?bool          { return $this->locked;}
		public function getTry(): ?string           { return $this->try;}
	
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

		/**
		 * Définit le numéro de téléphone.
		 *
		 * @param mixed $v Numéro de téléphone en clair
		 * @return bool True si le numéro est valide et assigné
		 */
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

		/**
		 * Définit le token JWT brut.
		 *
		 * Accepte uniquement :
		 * - null  : efface le token
		 * - string : chaîne JWT (format header.payload.signature)
		 *	* La validité (date) est gérée séparément via `setTokenValidity()`.
		 *
		 * @param mixed $v JWT string ou null
		 * @return bool
		 */
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
		
		/**
		 * Définit la date de validité du token.
		 *
		 * Accepte : null, timestamp (int), chaîne de date lisible, ou DateTimeInterface.
		 * Stocke la date au format 'Y-m-d H:i:s'.
		 *
		 * @param mixed $v
		 * @return bool
		 */
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

		/**
		 * Définit l'état de verrouillage.
		 *
		 * @param bool $v True pour verrouiller, false pour déverrouiller
		 * @return bool L'état verrouillé actuel
		 */
		public function setLocked(bool $v): bool {
			$this->locked = $v;
			return $this->locked;
		}

		/**
		 * Définit l'historique des essais infructueux.
		 *
		 * Accepte une chaîne JSON ou un tableau, ou null pour effacer.
		 *
		 * @param mixed $v Chaîne JSON, tableau, ou null
		 * @return bool
		 */
		public function setTry($v): bool {
			if ($v === null) {
				$this->try = null;
				return true;
			}

			if (is_string($v)) {
				$decoded = json_decode($v, true);
				if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
					$this->try = $v; // Stocker le JSON brut
					return true;
				}
				Errors::add("try JSON invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			if (is_array($v)) {
				$json = json_encode($v, JSON_UNESCAPED_UNICODE);
				if (json_last_error() !== JSON_ERROR_NONE) {
					Errors::add("try tableau non convertible en JSON : " . print_r($v, true), ErrorLevel::ERROR);
					return false;
				}
				$this->try = $json;
				return true;
			}

			Errors::add("Format try invalide : " . print_r($v, true), ErrorLevel::ERROR);
			return false;
		}
	
	}


	/**
	 * Classe de gestion des connexions.
	 *
	 * Cette classe pourra être étendue pour stocker les règles de
	 * gestion des sessions et des tokens de connexion.
	 */
	class Connexion {
		/**
		 * @param PDO $Database Connexion PDO à la base de données
		 * @param string $PrivateKeyPath Chemin vers la clé privée (PEM)
		 * @param string $PublicKeyPath  Chemin vers la clé publiqu	e (PEM)
		 */
		public function __construct(private PDO $Database, private string $PrivateKeyPath, private string $PublicKeyPath){
			// Vérification de la présence et de la lisibilité des fichiers de clés
			if (!file_exists($this->PrivateKeyPath) || !is_readable($this->PrivateKeyPath)) {
				Errors::add("Clé privée introuvable ou non lisible : " . $this->PrivateKeyPath, ErrorLevel::ERROR);
				throw new \InvalidArgumentException('Private key file not found or not readable');
			}

			if (!file_exists($this->PublicKeyPath) || !is_readable($this->PublicKeyPath)) {
				Errors::add("Clé publique introuvable ou non lisible : " . $this->PublicKeyPath, ErrorLevel::ERROR);
				throw new \InvalidArgumentException('Public key file not found or not readable');
			}
		}

		public function connect(string $telephone, string $password): ?ConnexionCard {

			// Normalisation du téléphone
			$telephone = str_replace(['.', ' ', '-'], '', $telephone);

			// Étape 1 : Récupérer l'utilisateur
			$user = (new User($this->Database))->get(null, $telephone);
			if ($user === null) {
				Errors::add("Utilisateur introuvable pour le téléphone : " . $telephone, ErrorLevel::ERROR);
				return null;
			}

			// Préparer la ConnexionCard
			$card = new ConnexionCard();
			$card->setUid($user->getUid());
			$card->setTelephone($telephone);

			// Vérifier si le compte est verrouillé
			if ($user->getLocked()) {
				$this->updateTry($card, ['type' => 'locked', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
				Errors::add("Compte utilisateur verrouillé.", ErrorLevel::WARNING);
				return null;
			}

			// Étape 2 : Récupérer la ligne user_connexion
			$stmt = $this->Database->prepare("
				SELECT cid, uid, telephone, password
				FROM user_connexion
				WHERE uid = :uid
				LIMIT 1
			");
			$stmt->execute([':uid' => $user->getUid()]);
			$connexionRow = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$connexionRow) {
				$this->updateTry($card, ['type' => 'failed', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
				Errors::add("Connexion introuvable", ErrorLevel::ERROR);
				return null;
			}

			// Vérifier correspondance téléphone
			$connexionTelephone = str_replace(['.', ' ', '-'], '', $connexionRow['telephone']);
			if ($connexionTelephone !== $telephone) {
				$this->updateTry($card, ['type' => 'failed', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
				Errors::add("Téléphone ne correspond pas", ErrorLevel::ERROR);
				return null;
			}

			// Vérifier mot de passe
			if (!password_verify($password, $connexionRow['password'])) {
				$this->updateTry($card, ['type' => 'failed', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
				Errors::add("Mot de passe incorrect", ErrorLevel::ERROR);
				return null;
			}

			// Étape 3 : Génération du JWT
			$payload = [
				'uid'  => $user->getUid(),
				'cid'  => $connexionRow['cid'],
				'tel'  => $user->getTelephone(),
				'role' => $user->getRole(),
				'iat'  => time(),
				'exp'  => time() + 86400 // 24h
			];

			$jwt = $this->generateJwt($payload);
			if (!$jwt) {
				Errors::add("Erreur lors de la génération du JWT", ErrorLevel::ERROR);
				return null;
			}

			// Hydratation de la ConnexionCard
			$card->setCid($connexionRow['cid']);
			$card->setToken($jwt);
			$card->setTokenValidity(date('Y-m-d H:i:s', $payload['exp']));
			$card->setLocked(false);

			// Mise à jour lastConnexion
			$stmt = $this->Database->prepare("UPDATE user_connexion SET lastConnexion = NOW() WHERE cid = :cid");
			$stmt->execute([':cid' => $connexionRow['cid']]);

			// Enregistrer l'essai réussi
			$this->updateTry($card, ['type' => 'success', 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);

			return $card;
		}


		public function connectWithToken(string $token): ?ConnexionCard {
			// 1. Décoder et valider le JWT
			$payload = $this->decodeJwt($token);
			if ($payload === null) {
				Errors::add("Token invalide ou expiré", ErrorLevel::ERROR);
				return null;
			}

			// Vérification des champs obligatoires
			if (!isset($payload['uid'], $payload['cid'], $payload['exp'])) {
				Errors::add("Token incomplet", ErrorLevel::ERROR);
				return null;
			}

			// 2. Vérifier expiration
			if ($payload['exp'] < time()) {
				Errors::add("Token expiré", ErrorLevel::ERROR);
				return null;
			}

			$uid = (int)$payload['uid'];
			$cid = (int)$payload['cid'];

			// 3. Récupérer la ligne user_connexion
			$stmt = $this->Database->prepare("
				SELECT cid, uid, telephone, lastConnexion, locked
				FROM user_connexion
				WHERE cid = :cid AND uid = :uid
				LIMIT 1
			");
			$stmt->execute([':cid' => $cid, ':uid' => $uid]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$row) {
				Errors::add("Connexion introuvable", ErrorLevel::ERROR);
				return null;
			}

			// 4. Vérifier si le compte est verrouillé
			if (!empty($row['locked'])) {
				Errors::add("Compte verrouillé", ErrorLevel::WARNING);
				return null;
			}

			// 5. Récupérer l'utilisateur
			$user = (new User($this->Database))->get($uid, null);
			if ($user === null) {
				Errors::add("Utilisateur introuvable", ErrorLevel::ERROR);
				return null;
			}

			// 6. Hydrater la ConnexionCard
			$card = new ConnexionCard();
			$card->setCid($cid);
			$card->setUid($uid);
			$card->setTelephone($row['telephone']);
			$card->setLocked(false);
			$card->setToken($token);
			$card->setTokenValidity(date('Y-m-d H:i:s', $payload['exp']));

			// 7. Mettre à jour lastConnexion
			$stmt = $this->Database->prepare("
				UPDATE user_connexion SET lastConnexion = NOW() WHERE cid = :cid
			");
			$stmt->execute([':cid' => $cid]);

			return $card;
		}


		/**
		 * Génère un JWT signé avec la clé privée.
		 *
		 * @param array $payload Données à encoder dans le JWT
		 * @return string|false JWT signé ou false en cas d'erreur
		 */
		private function generateJwt(array $payload): string|false {
			// Header JWT standard
			$header = [
				'alg' => 'RS256',
				'typ' => 'JWT'
			];

			$headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
			$payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

			$signatureInput = $headerEncoded . '.' . $payloadEncoded;

			// Charger la clé privée et signer
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

		/**
		 * Met à jour l'historique des essais infructueux et vérifie le verrouillage.
		 *
		 * Si $newTry est false :
		 * - Lit le tryHistory de la BDD via le cid de la ConnexionCard
		 * - Compte les tentatives des 24 dernières heures
		 * - Si > 5, marque le compte comme verrouillé (locked = true)
		 * - Met à jour la ConnexionCard et la retourne
		 *
		 * Si $newTry est un tableau (ou string avec détails) :
		 * - Ajoute une nouvelle tentative au début du tryHistory
		 * - Garde max 10 entrées (supprime la plus ancienne)
		 * - Met à jour la BDD
		 * - Retourne true en cas de succès
		 *
		 * @param ConnexionCard $card Objet ConnexionCard contenant le cid
		 * @param mixed $newTry false pour lire, ou array ['type' => ..., 'ip' => ...] pour ajouter
		 * @return ConnexionCard|bool Objet ConnexionCard hydraté si lecture, true si ajout réussi, false si erreur
		 */
		private function updateTry(ConnexionCard $card, $newTry = false) {
			$cid = $card->getCid();

			if (!$cid) {
				Errors::add("ConnexionCard doit avoir un cid défini", ErrorLevel::ERROR);
				return false;
			}

			// Cas 1 : Lire l'historique et vérifier le verrouillage
			if ($newTry === false) {
				$stmt = $this->Database->prepare("SELECT tryHistory FROM connexion WHERE cid = :cid LIMIT 1");
				$stmt->execute([':cid' => $cid]);
				$row = $stmt->fetch(PDO::FETCH_ASSOC);

				if (!$row) {
					Errors::add("Connexion cid=$cid introuvable", ErrorLevel::ERROR);
					return false;
				}

				$history = [];
				if ($row['tryHistory']) {
					$history = json_decode($row['tryHistory'], true) ?? [];
				}

				// Compter les tentatives des 24 dernières heures
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
						// Format invalide, ignorer
						continue;
					}
				}

				// Hydrater la ConnexionCard avec l'historique
				$card->setTry(json_encode($history));

				// Si > 5 tentatives en 24h, marquer comme verrouillé
				if ($countLast24h > 5) {
					$card->setLocked(true);
					$stmt = $this->Database->prepare("UPDATE connexion SET locked = 1 WHERE cid = :cid");
					$stmt->execute([':cid' => $cid]);
				}

				return $card;
			}

			// Cas 2 : Ajouter une nouvelle tentative
			$stmt = $this->Database->prepare("SELECT tryHistory FROM connexion WHERE cid = :cid LIMIT 1");
			$stmt->execute([':cid' => $cid]);
			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$row) {
				Errors::add("Connexion cid=$cid introuvable", ErrorLevel::ERROR);
				return false;
			}

			$history = [];
			if ($row['tryHistory']) {
				$history = json_decode($row['tryHistory'], true) ?? [];
			}

			// Créer la nouvelle tentative
			$newAttempt = [
				'date'  => date('Y-m-d'),
				'time'  => date('H:i:s'),
				'type'  => $newTry['type'] ?? 'unknown',
				'ip'    => $newTry['ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
			];

			// Ajouter au début (plus récent en premier)
			array_unshift($history, $newAttempt);

			// Garder max 10 entrées
			$history = array_slice($history, 0, 10);

			// Sauvegarder en BDD
			$stmt = $this->Database->prepare("UPDATE connexion SET tryHistory = :tryHistory WHERE cid = :cid");
			$updated = $stmt->execute([
				':tryHistory' => json_encode($history),
				':cid'        => $cid
			]);
			return $updated ? true : false;
		}

	}
?>