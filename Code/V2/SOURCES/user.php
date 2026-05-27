<?php
	require_once(__DIR__ . "/errors.php");

	/**
	 * Enumération représentant les rôles possibles d'un utilisateur.
	 *
	 * Chaque rôle correspond à un bit dans un bitmask, permettant
	 * de combiner plusieurs rôles dans un entier unique.
	 */
	enum Role: int {

		/** Rôle utilisateur standard */
		case UTILISATEUR    = 1 << 0;

		/** Rôle administrateur (droits étendus) */
		case ADMINISTRATEUR = 1 << 1;

		/** Rôle technicien (support, interventions) */
		case TECHNICIEN     = 1 << 2;

		/** Rôle comptable (gestion financière) */
		case COMPTABLE      = 1 << 3;

		/** Rôle commercial (gestion clients, ventes) */
		case COMMERCIAL     = 1 << 4;

		/** Rôle développeur (accès technique avancé) */
		case DEVELOPPEUR    = 1 << 5;

		/**
		 * Vérifie si un bitmask contient un rôle donné.
		 *
		 * @param int  $roles Bitmask des rôles
		 * @param Role $role  Rôle à vérifier
		 * @return bool True si le rôle est présent
		 */
		public static function hasRole(int $roles, Role $role): bool {
			return ($roles & $role->value) === $role->value;
		}

		/**
		 * Ajoute un rôle à un bitmask.
		 *
		 * @param int  $roles Bitmask actuel
		 * @param Role $role  Rôle à ajouter
		 * @return int Nouveau bitmask incluant le rôle
		 */
		public static function addRole(int $roles, Role $role): int {
			return $roles | $role->value;
		}

		/**
		 * Retire un rôle d'un bitmask.
		 *
		 * @param int  $roles Bitmask actuel
		 * @param Role $role  Rôle à retirer
		 * @return int Nouveau bitmask sans le rôle
		 */
		public static function removeRole(int $roles, Role $role): int {
			return $roles & ~$role->value;
		}
	}

	/**
	 * Représente une fiche utilisateur avec informations personnelles,
	 * coordonnées, rôles et validations strictes.
	 *
	 * Permet l’hydratation dynamique via un tableau associatif.
	 */
	class UserCard {

		/** @var int|null Identifiant unique de l'utilisateur */
		private ?int $uid = null;

		/** @var string|null Nom de famille (lettres, tirets, apostrophes) */
		private ?string $nom = null;

		/** @var string|null Prénom (lettres, tirets, apostrophes) */
		private ?string $prenom = null;

		/** @var string|null Numéro de téléphone FR normalisé (0XXXXXXXXX) */
		private ?string $telephone = null;

		/** @var string|null Adresse email valide */
		private ?string $email = null;

		/** @var string|null Adresse postale */
		private ?string $adresse = null;

		/** @var string|null Complément d'adresse */
		private ?string $complement = null;

		/** @var string|null Code postal (5 chiffres) */
		private ?string $codePostal = null;

		/** @var string|null Ville (lettres, tirets, apostrophes) */
		private ?string $ville = null;

		/** @var string|null Lien Google Wallet valide */
		private ?string $wallet = null;

		/** @var string|null Numéro SIREN (9 chiffres, validé par Luhn) */
		private ?string $siren = null;

		/** @var int Bitmask des rôles attribués à l'utilisateur */
		private int $roles = 0;

		/**
		 * Constructeur.
		 *
		 * @param array $data Données initiales pour hydrater l'objet
		 */
		public function __construct(array $data = []) {
			if (!empty($data)) {
				$this->hydrate($data);
			}
		}

		/**
		 * Hydrate l'objet à partir d'un tableau associatif.
		 * Les clés sont converties en setters automatiquement.
		 *
		 * @param array $data
		 * @return void
		 */
		public function hydrate(array $data): void {
			foreach ($data as $key => $value) {
				if ($key === "role" || $key === "roles") {
					$this->setRole($value);
					continue;
				}

				$method = 'set' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));

				if (method_exists($this, $method)) {
					$success = $this->$method($value);
					if (!$success) {
						Errors::add("Hydrate: impossible d’hydrater '$key' avec la valeur '" . print_r($value, true) . "'", ErrorLevel::WARNING);
					}
				} else {
					Errors::add("Hydrate: propriété inconnue '$key' ignorée", ErrorLevel::INFO);
				}
			}
		}

		/** @return int|null */
		public function getUid(): ?int { return $this->uid; }

		/** @return string|null */
		public function getNom(): ?string { return $this->nom; }

		/** @return string|null */
		public function getPrenom(): ?string { return $this->prenom; }

		/** @return string|null */
		public function getTelephone(): ?string { return $this->telephone; }

		/** @return string|null */
		public function getEmail(): ?string { return $this->email; }

		/** @return string|null */
		public function getAdresse(): ?string { return $this->adresse; }

		/** @return string|null */
		public function getComplement(): ?string { return $this->complement; }

		/** @return string|null */
		public function getCodePostal(): ?string { return $this->codePostal; }

		/** @return string|null */
		public function getVille(): ?string { return $this->ville; }

		/** @return string|null */
		public function getWallet(): ?string { return $this->wallet; }

		/** @return string|null */
		public function getSiren(): ?string { return $this->siren; }

		/** @return int */
		public function getRoles(): int { return $this->roles; }

		/**
		 * Définit l'UID.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setUid($v): bool {
			if ($v === null) {
				$this->uid = null;
				return true;
			}
			if (!is_numeric($v) || (int)$v <= 0) {
				Errors::add("uid invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->uid = (int)$v;
			return true;
		}

		/**
		 * Définit le nom.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setNom($v): bool {
			if ($v === null) {
				$this->nom = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Nom invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->nom = strtoupper(trim($v));
			return true;
		}

		/**
		 * Définit le prénom.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setPrenom($v): bool {
			if ($v === null) {
				$this->prenom = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Prénom invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->prenom = ucfirst(strtolower(trim($v)));
			return true;
		}

		/**
		 * Définit le numéro de téléphone FR.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setTelephone($v): bool {
			if ($v === null) {
				$this->telephone = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Téléphone invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			$clean = preg_replace('/\D+/', '', $v);

			if (str_starts_with($clean, '0033')) {
				$clean = '0' . substr($clean, 4);
			}
			elseif (str_starts_with($clean, '33')) {
				$clean = '0' . substr($clean, 2);
			}

			if (!preg_match('/^0[1-9]\d{8}$/', $clean)) {
				Errors::add("Numéro de téléphone FR invalide : $v", ErrorLevel::ERROR);
				return false;
			}

			$this->telephone = $clean;
			return true;
		}

		/**
		 * Définit l'email.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setEmail($v): bool {
			if ($v === null) {
				$this->email = null;
				return true;
			}
			if (!is_string($v) || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
				Errors::add("Email invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->email = strtolower(trim($v));
			return true;
		}

		/**
		 * Définit l'adresse postale.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setAdresse($v): bool {
			if ($v === null) {
				$this->adresse = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Adresse invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->adresse = trim($v);
			return true;
		}

		/**
		 * Définit le complément d'adresse.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setComplement($v): bool {
			if ($v === null) {
				$this->complement = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Complément invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->complement = trim($v);
			return true;
		}

		/**
		 * Définit le code postal.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setCodePostal($v): bool {
			if ($v === null) {
				$this->codePostal = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^\d{5}$/', $v)) {
				Errors::add("Code postal invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->codePostal = $v;
			return true;
		}

		/**
		 * Définit la ville.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setVille($v): bool {
			if ($v === null) {
				$this->ville = null;
				return true;
			}
			if (!is_string($v) || !preg_match('/^[\p{L}\-\' ]+$/u', $v)) {
				Errors::add("Ville invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}
			$this->ville = strtoupper(trim($v));
			return true;
		}

		/**
		 * Définit un lien Google Wallet.
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setWallet($v): bool {
			if ($v === null) {
				$this->wallet = null;
				return true;
			}
			if (!is_string($v)) {
				Errors::add("Wallet doit être une chaîne de caractères", ErrorLevel::ERROR);
				return false;
			}

			$v = trim($v);

			if (!filter_var($v, FILTER_VALIDATE_URL)) {
				Errors::add("Lien Google Wallet invalide : $v", ErrorLevel::ERROR);
				return false;
			}

			$host = parse_url($v, PHP_URL_HOST);
			$allowedDomains = ['pay.google.com', 'wallet.google.com', 'wallet.google', 'google.com'];

			$isValidDomain = false;
			foreach ($allowedDomains as $domain) {
				if ($host === $domain || str_ends_with($host, '.' . $domain)) {
					$isValidDomain = true;
					break;
				}
			}

			if (!$isValidDomain) {
				Errors::add("Le lien fourni n'est pas un lien Google Wallet : $v", ErrorLevel::ERROR);
				return false;
			}

			$path = parse_url($v, PHP_URL_PATH);
			if (!preg_match('#^/gp/v/save/.+#', $path)) {
				Errors::add("Le lien Google Wallet ne contient pas de token valide : $v", ErrorLevel::ERROR);
				return false;
			}

			$this->wallet = $v;
			return true;
		}

		/**
		 * Définit le numéro SIREN (9 chiffres, validé par Luhn).
		 *
		 * @param mixed $v
		 * @return bool
		 */
		public function setSiren($v): bool {
			if ($v === null) {
				$this->siren = null;
				return true;
			}
			if (!is_string($v) && !is_numeric($v)) {
				Errors::add("SIREN invalide : " . print_r($v, true), ErrorLevel::ERROR);
				return false;
			}

			$clean = preg_replace('/\D+/', '', (string)$v);

			if (strlen($clean) !== 9) {
				Errors::add("SIREN doit contenir exactement 9 chiffres : $clean", ErrorLevel::ERROR);
				return false;
			}

			if (!$this->isValidLuhn($clean)) {
				Errors::add("SIREN invalide (échec Luhn) : $clean", ErrorLevel::ERROR);
				return false;
			}

			$this->siren = $clean;
			return true;
		}

		/**
		 * Définit les rôles via un bitmask.
		 *
		 * @param int $roles
		 * @return bool
		 */
		public function setRoles(int $roles): bool {
			if ($roles < 0) {
				Errors::add("Roles invalide : $roles", ErrorLevel::ERROR);
				return false;
			}

			$rolesValides = 0;
			foreach (Role::cases() as $role) {
				if (($roles & $role->value) === $role->value) {
					$rolesValides |= $role->value;
				}
			}

			$this->roles = $rolesValides;
			return true;
		}

		/**
		 * Définit un ou plusieurs rôles.
		 *
		 * @param mixed $value int|string|Role|array
		 * @return bool
		 */
		public function setRole($value): bool {
			if (is_int($value)) {
				return $this->setRoles($value);
			}

			if ($value instanceof Role) {
				return $this->addRole($value);
			}

			if (is_string($value)) {
				$value = strtoupper($value);
				if (defined("Role::$value")) {
					return $this->addRole(Role::from(constant("Role::$value")));
				}
				Errors::add("Rôle inconnu : $value", ErrorLevel::WARNING);
				return false;
			}

			if (is_array($value)) {
				foreach ($value as $item) {
					if (!$this->setRole($item)) {
						return false;
					}
				}
				return true;
			}

			Errors::add("Format de rôle invalide : " . print_r($value, true), ErrorLevel::ERROR);
			return false;
		}

		/**
		 * Ajoute un rôle.
		 *
		 * @param Role $role
		 * @return bool
		 */
		public function addRole(Role $role): bool {
			$this->roles = Role::addRole($this->roles, $role);
			return true;
		}

		/**
		 * Retire un rôle.
		 *
		 * @param Role $role
		 * @return bool
		 */
		public function removeRole(Role $role): bool {
			$this->roles = Role::removeRole($this->roles, $role);
			return true;
		}

		/**
		 * Vérifie si l'utilisateur possède un rôle.
		 *
		 * @param Role $role
		 * @return bool
		 */
		public function hasRole(Role $role): bool {
			return Role::hasRole($this->roles, $role);
		}

		/**
		 * Vérifie un numéro via l'algorithme de Luhn.
		 *
		 * @param string $number
		 * @return bool
		 */
		private function isValidLuhn(string $number): bool {
			$sum = 0;
			$len = strlen($number);

			for ($i = 0; $i < $len; $i++) {
				$digit = (int)$number[$len - 1 - $i];

				if ($i % 2 === 1) {
					$digit *= 2;
					if ($digit > 9) {
						$digit -= 9;
					}
				}

				$sum += $digit;
			}

			return ($sum % 10) === 0;
		}
	}

	/**
	 * Gestion des utilisateurs : création, mise à jour, récupération
	 * et gestion de la base de données associée.
	 */
	class User {

		/**
		 * @param PDO $Database Connexion PDO à la base de données
		 */
		public function __construct(private PDO $Database){}

		/**
		 * Crée ou met à jour un utilisateur.
		 *
		 * - Vérifie les droits du modificateur
		 * - Vérifie la validité des données
		 * - Insère ou met à jour la ligne SQL
		 *
		 * @param UserCard $cible        Utilisateur à créer ou modifier
		 * @param UserCard $modificateur Utilisateur effectuant l'action
		 * @return int UID créé ou modifié, 0 en cas d'erreur
		 */
		public function save(UserCard $cible, UserCard $modificateur): int {

			if ($modificateur->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return 0;
			}

			$isAdmin = (
				$modificateur->hasRole(Role::ADMINISTRATEUR) ||
				$modificateur->hasRole(Role::TECHNICIEN) ||
				$modificateur->hasRole(Role::COMMERCIAL)
			);

			$isCreation = ($cible->getUid() === null);

			if ($isCreation && !$isAdmin) {
				Errors::add("Droits insuffisants pour créer un utilisateur", ErrorLevel::ERROR);
				return 0;
			}

			if (!$isCreation && $modificateur->getUid() !== $cible->getUid() && !$isAdmin) {
				Errors::add("Droits insuffisants pour modifier cet utilisateur", ErrorLevel::ERROR);
				return 0;
			}

			if ($cible->getTelephone() === null) {
				Errors::add("Impossible de créer ou modifier un utilisateur sans numéro de téléphone", ErrorLevel::ERROR);
				return 0;
			}

			try {
				if ($isCreation) {

					$stmt = $this->Database->prepare("
						INSERT INTO user_card 
						(nom, prenom, adresse, complement, codePostal, ville, email, telephone, siren, wallet, roles)
						VALUES 
						(:nom, :prenom, :adresse, :complement, :codePostal, :ville, :email, :telephone, :siren, :wallet, :roles)
					");

					$stmt->execute([
						':nom'        => $cible->getNom(),
						':prenom'     => $cible->getPrenom(),
						':adresse'    => $cible->getAdresse(),
						':complement' => $cible->getComplement(),
						':codePostal' => $cible->getCodePostal(),
						':ville'      => $cible->getVille(),
						':email'      => $cible->getEmail(),
						':telephone'  => $cible->getTelephone(),
						':siren'      => $cible->getSiren(),
						':wallet'     => $cible->getWallet(),
						':roles'      => $cible->getRoles()
					]);

					$uid = (int)$this->Database->lastInsertId();
					$cible->setUid($uid);

					return $uid;
				}

				// UPDATE
				$stmt = $this->Database->prepare("
					UPDATE user_card SET
						nom = :nom,
						prenom = :prenom,
						adresse = :adresse,
						complement = :complement,
						codePostal = :codePostal,
						ville = :ville,
						email = :email,
						telephone = :telephone,
						wallet = :wallet,
						siren = :siren,
						roles = :roles
					WHERE uid = :uid
				");

				$stmt->execute([
					':nom'        => $cible->getNom(),
					':prenom'     => $cible->getPrenom(),
					':adresse'    => $cible->getAdresse(),
					':complement' => $cible->getComplement(),
					':codePostal' => $cible->getCodePostal(),
					':ville'      => $cible->getVille(),
					':email'      => $cible->getEmail(),
					':telephone'  => $cible->getTelephone(),
					':wallet'     => $cible->getWallet(),
					':siren'      => $cible->getSiren(),
					':roles'      => $cible->getRoles(),
					':uid'        => $cible->getUid()
				]);

				return $cible->getUid();
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return 0;
			}
		}

		/**
		 * Récupère un utilisateur par UID ou téléphone.
		 *
		 * @param int|null    $uid       Identifiant utilisateur
		 * @param string|null $telephone Numéro de téléphone
		 * @return UserCard|null
		 */
		public function get(int $uid, string $telephone = null): ?UserCard {

			if ($uid === null && ($telephone === null || $telephone === "")) {
				Errors::add("Aucun critère fourni pour récupérer un utilisateur", ErrorLevel::ERROR);
				return null;
			}

			try {
				if ($uid !== null) {
					$stmt = $this->Database->prepare("SELECT * FROM user_card WHERE uid = :uid LIMIT 1");
					$stmt->execute([':uid' => $uid]);
				} else {
					$normalizedTelephone = $this->normalizeTelephone($telephone);
					if ($normalizedTelephone === null) {
						Errors::add("Téléphone recherché invalide", ErrorLevel::ERROR);
						return null;
					}

					$stmt = $this->Database->prepare("SELECT * FROM user_card WHERE telephone = :telephone LIMIT 1");
					$stmt->execute([':telephone' => $normalizedTelephone]);
				}

				$row = $stmt->fetch(PDO::FETCH_ASSOC);

				if (!$row) {
					Errors::add("Aucun utilisateur trouvé", ErrorLevel::ERROR);
					return null;
				}

				$user = new UserCard();
				$user->hydrate($row);

				return $user;
			}
			catch (PDOException $e) {
				Errors::add("Erreur SQL : " . $e->getMessage(), ErrorLevel::ERROR);
				return null;
			}
		}

		/**
		 * Vérifie la présence de la table user_card, des colonnes,
		 * et que wallet est bien un LONGTEXT.
		 *
		 * Si la structure est incorrecte, la table est recréée.
		 *
		 * @return bool True si OK, false si recréation nécessaire
		 */
		public function checkDatabase(): bool {

			$table = "user_card";

			// Vérification de la table
			$stmt = $this->Database->prepare("SHOW TABLES LIKE :table");
			$stmt->execute(['table' => $table]);

			if ($stmt->rowCount() === 0) {
				Errors::add("La table '$table' est absente", ErrorLevel::WARNING);
				return $this->createDatabase();
			}

			// Colonnes attendues
			$expectedColumns = [
				'uid', 'nom', 'prenom', 'telephone', 'email',
				'adresse', 'complement', 'codePostal', 'ville',
				'wallet', 'siren', 'roles'
			];

			$columns = $this->Database
				->query("SHOW COLUMNS FROM $table")
				->fetchAll(PDO::FETCH_COLUMN);

			foreach ($expectedColumns as $col) {
				if (!in_array($col, $columns)) {
					Errors::add("Colonne manquante : $col", ErrorLevel::WARNING);
					return $this->createDatabase();
				}
			}

			// Vérification du type de wallet
			$stmt = $this->Database->query("SHOW COLUMNS FROM $table LIKE 'wallet'");
			$walletInfo = $stmt->fetch(PDO::FETCH_ASSOC);

			if (!$walletInfo) {
				Errors::add("Colonne wallet introuvable", ErrorLevel::WARNING);
				return $this->createDatabase();
			}

			$type = strtolower($walletInfo['Type']);

			if (!str_contains($type, 'longtext')) {
				Errors::add("La colonne wallet doit être LONGTEXT, trouvé : $type", ErrorLevel::WARNING);
				return $this->createDatabase();
			}

			return true;
		}

		/**
		 * Normalise un numéro de téléphone FR pour la recherche.
		 *
		 * @param string $telephone
		 * @return string|null
		 */
		private function normalizeTelephone(string $telephone): ?string {
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

		/**
		 * Supprime la table user_card si nécessaire et la recrée proprement.
		 *
		 * @param bool $forceDrop Si true, supprime la table existante
		 * @return bool
		 */
		private function createDatabase(bool $forceDrop = true): bool {

			$table = "user_card";

			if ($forceDrop) {
				$this->Database->exec("DROP TABLE IF EXISTS $table");
			}

			$sql = "
				CREATE TABLE IF NOT EXISTS $table (
					uid INT PRIMARY KEY AUTO_INCREMENT,
					nom VARCHAR(100),
					prenom VARCHAR(100),
					telephone VARCHAR(20),
					email VARCHAR(255),
					adresse VARCHAR(255),
					complement VARCHAR(255),
					codePostal VARCHAR(5),
					ville VARCHAR(100),
					wallet LONGTEXT,
					siren VARCHAR(9),
					roles INT NOT NULL DEFAULT 0
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
			";

			try {
				$this->Database->exec($sql);
				return true;
			}
			catch (Exception $e) {
				Errors::add("Erreur création base : " . $e->getMessage(), ErrorLevel::ERROR);
				return false;
			}
		}
	}

?>
