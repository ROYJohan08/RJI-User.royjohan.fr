<?php
    require_once(__DIR__ . "/SOURCES/errors.php");

    class deviceCard{
        private ?int    $did  = null;
        private ?int    $uid  = null;
        private ?string $type = null;
        private ?string $brand = null;
        private ?string $model = null;
        private ?string $serial = null;
        private ?string $snid = null;
        private ?string $color = null;
        private ?string $password = null;
        private ?string $rdp = null;

        public function __construct(?array $data = null) {
            if ($data !== null) {
                $this->hydrate($data);
            }
        }

        public function hydrate(array $data): void {
            foreach ($data as $key => $value) {
                $method = 'set' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
                if (method_exists($this, $method)) {
                    $this->$method($value);
                } else {
                    Errors::add("Hydrate: propriété inconnue '$key' ignorée", ErrorLevel::INFO);
                }
            }
        }

        public function getDid(): ?int {
            return $this->did;
        }
        public function getUid(): ?int {
            return $this->uid;
        }
        public function getType(): ?string {
            return $this->type;
        }
        public function getBrand(): ?string {
            return $this->brand;
        }
        public function getModel(): ?string {
            return $this->model;
        }
        public function getSerial(): ?string {
            return $this->serial;
        }
        public function getSnid(): ?string {
            return $this->snid;
        }
        public function getColor(): ?string {
            return $this->color;
        }
        public function getPassword(): ?string {
            return $this->password;
        }
        public function getRdp(): ?string {
            return $this->rdp;
        }

        public function setDid($v): bool {
            if ($v === null) { $this->did = null; return true; }
            if (!is_numeric($v) || (int)$v <= 0) { Errors::add("did invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $this->did = (int)$v; return true;
        }
        public function setUid($v): bool {
            if ($v === null) { $this->uid = null; return true; }
            if (!is_numeric($v) || (int)$v <= 0) { Errors::add("uid invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $this->uid = (int)$v; return true;
        }
        public function setType($v): bool {
            if ($v === null) { $this->type = null; return true; }
            if (!is_string($v)) { Errors::add("type invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 50 || !preg_match('/^[A-Za-z0-9\-\s]{1,50}$/u', $v)) {
                Errors::add("type invalide (caractères autorisés: lettres, chiffres, espace, - ; max 50) : " . $v, ErrorLevel::ERROR);
                return false;
            }
            $this->type = $v; return true;
        }
        public function setBrand($v): bool {
            if ($v === null) { $this->brand = null; return true; }
            if (!is_string($v)) { Errors::add("brand invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 100) { Errors::add("brand invalide (longueur) : " . $v, ErrorLevel::ERROR); return false; }
            $this->brand = $v; return true;
        }
        public function setModel($v): bool {
            if ($v === null) { $this->model = null; return true; }
            if (!is_string($v)) { Errors::add("model invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 100) { Errors::add("model invalide (longueur) : " . $v, ErrorLevel::ERROR); return false; }
            $this->model = $v; return true;
        }
        public function setSerial($v): bool {
            if ($v === null) { $this->serial = null; return true; }
            if (!is_string($v)) { Errors::add("serial invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 100 || !preg_match('/^[A-Za-z0-9\-\._]{1,100}$/', $v)) {
                Errors::add("serial invalide (alphanum, -, ., _ ; max 100) : " . $v, ErrorLevel::ERROR);
                return false;
            }
            $this->serial = $v; return true;
        }
        public function setSnid($v): bool {
            if ($v === null) { $this->snid = null; return true; }
            if (!is_string($v) && !is_numeric($v)) { Errors::add("snid invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim((string)$v);
            if ($v === '' || mb_strlen($v) > 100 || !preg_match('/^[A-Za-z0-9\-_]{1,100}$/', $v)) {
                Errors::add("snid invalide (alphanum, -, _ ; max 100) : " . $v, ErrorLevel::ERROR);
                return false;
            }
            $this->snid = $v; return true;
        }
        public function setColor($v): bool {
            if ($v === null) { $this->color = null; return true; }
            if (!is_string($v)) { Errors::add("color invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 50 || !preg_match('/^[A-Za-z\s\-]{1,50}$/u', $v)) {
                Errors::add("color invalide (lettres, espace, - ; max 50) : " . $v, ErrorLevel::ERROR);
                return false;
            }
            $this->color = $v; return true;
        }
        public function setPassword($v): bool {
            if ($v === null) { $this->password = null; return true; }
            if (!is_string($v)) { Errors::add("password invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if (mb_strlen($v) < 8 || mb_strlen($v) > 128) { Errors::add("password invalide (8-128 chars)", ErrorLevel::ERROR); return false; }
            $this->password = $v; return true;
        }
        public function setRdp($v): bool {
            if ($v === null) { $this->rdp = null; return true; }
            if (!is_string($v)) { Errors::add("rdp invalide : " . print_r($v, true), ErrorLevel::ERROR); return false; }
            $v = trim($v);
            if ($v === '' || mb_strlen($v) > 255) { Errors::add("rdp invalide (vide ou trop long)", ErrorLevel::ERROR); return false; }
            $this->rdp = $v; return true;
        }

    }

    class Device {
        public function __construct(private PDO $Databse) {}

        public function get($uid, $did, userCard $user): ?deviceCard {
            if ($user->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return 0;
			}
			$isAdmin = (
				$user->hasRole(Role::ADMINISTRATEUR) ||
				$user->hasRole(Role::TECHNICIEN) ||
				$user->hasRole(Role::COMMERCIAL)
			);
            if(!$isAdmin && $user->getUid() !== $uid) {
                Errors::add("Accès refusé : vous n'avez pas les droits pour accéder à cet appareil", ErrorLevel::ERROR);
                return null;
            }
           if($did!==null && is_numeric($did)) {
                $stmt = $this->Databse->prepare("SELECT * FROM devices WHERE uid = :uid AND did = :did");
                $stmt->execute([':uid' => $uid, ':did' => $did]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($data === false) {
                    return null;
                }
                return [new deviceCard($data)];
            }
            else{
                $stmt = $this->Databse->prepare("SELECT * FROM devices WHERE uid = :uid ORDER BY did ASC");
                $stmt->execute([':uid' => $uid]);
                $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($data === false) {
                    return null;
                }
                $deviceCards = [];
                foreach ($data as $row) {
                    $deviceCards[] = new deviceCard($row);
                }
                return $deviceCards;
            }
        }

        public function save(deviceCard $device, userCard $user): bool {
            if ($user->getUid() === null) {
				Errors::add("Authentification requise", ErrorLevel::ERROR);
				return 0;
			}
			$isAdmin = (
				$user->hasRole(Role::ADMINISTRATEUR) ||
				$user->hasRole(Role::TECHNICIEN) ||
				$user->hasRole(Role::COMMERCIAL)
			);
            if(!$isAdmin) {
                Errors::add("Accès refusé : vous n'avez pas les droits pour gérer cet appareil", ErrorLevel::ERROR);
                return null;
            }
            $isCreation = ($device->getDid() === null);
            if ($isCreation) {
                $stmt = $this->Database->prepare("INSERT INTO user_devices (uid, type, brand, model, serial, snid, color, password, rdp) VALUES (:uid, :type, :brand, :model, :serial, :snid, :color, :password, :rdp)");
                $stmt->execute([':uid' => $device->getUid(), ':type' => $device->getType(), ':brand' => $device->getBrand(), ':model' => $device->getModel(), ':serial' => $device->getSerial(), ':snid' => $device->getSnid(), ':color' => $device->getColor(), ':password' => $device->getPassword(), ':rdp' => $device->getRdp()]);
                $cid = (int)$this->Database->lastInsertId();
                $device->setDid($cid);
                return $cid;
            }
            $stmt = $this->Database->prepare(" UPDATE user_devices SET uid = :uid, type = :type, brand = :brand, model = :model, serial = :serial, snid = :snid, color = :color, password = :password, rdp = :rdp WHERE did = :did ");
			$stmt->execute([ ':uid' => $device->getUid(), ':type' => $device->getType(), ':brand' => $device->getBrand(), ':model' => $device->getModel(), ':serial' => $device->getSerial(), ':snid' => $device->getSnid(), ':color' => $device->getColor(), ':password' => $device->getPassword(), ':rdp' => $device->getRdp(), ':did' => $device->getDid() ]);
			return $device->getDid();
        }

        public function checkDatabase(): bool {
            $table = 'user_devices';
            $stmt = $this->Database->query("SHOW TABLES LIKE '$table'");
            if ($stmt === false) {
                Errors::add("Impossible d'exécuter SHOW TABLES", ErrorLevel::ERROR);
                return false;
            }
            if ($stmt->rowCount() === 0) {
                Errors::add("La table '$table' est absente", ErrorLevel::WARNING);
                return $this->createDatabase();
            }
            $expectedColumns = ['did', 'uid', 'type', 'brand', 'model', 'serial', 'snid', 'color', 'password', 'rdp'];
            $columns = $this->Database->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($expectedColumns as $col) {
                if (!in_array($col, $columns, true)) {
                    Errors::add("Colonne manquante : $col", ErrorLevel::WARNING);
                    return $this->createDatabase();
                }
            }
            return true;
        }

        private function createDatabase(bool $forceDrop = true): bool {
            $table = 'user_devices';
            if ($forceDrop) {
                $this->Database->exec("DROP TABLE IF EXISTS $table");
            }
            $sql = " CREATE TABLE IF NOT EXISTS $table ( did INT PRIMARY KEY AUTO_INCREMENT, uid INT NOT NULL, type VARCHAR(50), brand VARCHAR(100), model VARCHAR(100), serial VARCHAR(100), snid VARCHAR(100), color VARCHAR(50), password VARCHAR(128), rdp VARCHAR(255) ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            try {
                $this->Database->exec($sql);
                return true;
            }
            catch (PDOException $e) {
                Errors::add("Erreur création base : " . $e->getMessage(), ErrorLevel::ERROR);
                return false;
            }
        }
    }
?>