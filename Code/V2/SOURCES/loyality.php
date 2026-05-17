<?php
	require_once(__DIR__ . "/errors.php");
	require_once(__DIR__ . "/lib/vendor/autoload.php");
	
	class LoyalityCard{
		private ?string $url           = null;
		private ?string $telephone     = null;
		private ?string $nom           = null;
		private ?string $prenom        = null;
		private ?int    $points        = null;
		private  array  $textes        = [];
		private  array  $liens         = [];
		private  array  $notifications = [];
		
		public function __construct(array $data = []){
			$this->hydrate($data);
		}
		
		public function hydrate(array $data): void{
			foreach ($data as $key => $value){
				$method = "set" . ucfirst($key);
				if (method_exists($this, $method)){
					$this->$method($value);
				}
			}
		}
		
		public function getUrl(): ?string{return $this->url;}
		public function getTelephone(): ?string{return $this->telephone;}
		public function getNom(): ?string{return $this->nom;}
		public function getPrenom(): ?string{return $this->prenom;}
		public function getPoints(): ?int{return $this->points;}
		public function getTextes(): array{return $this->textes;}
		public function getLiens(): array{return $this->liens;}
		public function getNotifications(): array{return $this->notifications;}
		
		public function setUrl(?string $url): void{
			if ($url===null){
				Errors::add("Url vide.",ErrorLevel::INFO);
				$this->url = null;
				return;
			}
			$url = trim($url);
			if ($url===''){
				Errors::add("Url vide.",ErrorLevel::INFO);
				$this->url = null;
				return;
			}
			$parts = parse_url($url);
			if ($parts === false){
				Errors::add("Url n'est pas au format google wallet (no parts)",ErrorLevel::ERROR);
				return;
			}
			if (($parts['scheme'] ?? '') !== 'https'){
				Errors::add("Url n'est pas au format google wallet (no https)",ErrorLevel::ERROR);
				return;
			}
			if (($parts['host'] ?? '') !== 'pay.google.com'){
				Errors::add("Url n'est pas au format google wallet (no pay.google.com)",ErrorLevel::ERROR);
				return;
			}
			$path = $parts['path'] ?? '';
			$prefix = '/gp/v/save/';
			if (!str_starts_with($path, $prefix)){
				Errors::add("Url n'est pas au format google wallet (no /gp/v/save/)",ErrorLevel::ERROR);
				return;
			}
			$jwt = $parts['fragment'] ?? null;
			if ($jwt === null && isset($parts['path'])){
				$prefix = '/gp/v/save/';
				if (str_starts_with($parts['path'], $prefix)) {
					$jwt = substr($parts['path'], strlen($prefix));
				}
			}
			if ($jwt === null || $jwt === '') {
				Errors::add("Url n'est pas au format google wallet (no jwt)",ErrorLevel::ERROR);
				return;
			}
			if (!preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $jwt)) {
				Errors::add("Url n'est pas au format google wallet (incorrect jwt)",ErrorLevel::ERROR);
				return;
			}
			$this->url = $url;
		}
		public function setTelephone(?string $telephone): void{
			if ($telephone === null){
				Errors::add("Téléphone null",ErrorLevel::INFO);
				$this->telephone = null;
				return;
			}
			$telephone = trim($telephone);
			$telephone = preg_replace('/[\s\.\-]/', '', $telephone);
			if ($telephone===''){
				Errors::add("Téléphone vide",ErrorLevel::INFO);
				$this->telephone = null;
				return;
			}
			if (!preg_match('/^0[1-79][0-9]{8}$/', $telephone)){
				Errors::add("Téléphone invalide",ErrorLevel::ERROR);
				return;
			}
			$this->telephone = $telephone;
		}
		public function setNom(?string $nom): void{
			if ($nom === null) {
				$this->nom = null;
				Errors::add("Nom null",ErrorLevel::INFO);
				return;
			}
			$nom = trim($nom);
			$nom = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
			$nom = strtoupper($nom);
			$nom = preg_replace('/[^A-Za-z]/', '', $nom);
			$nom = trim($nom, '-');
			if ($nom===''){
				$this->nom = null;
				Errors::add("Nom vide ou incorrect",ErrorLevel::INFO);
				return;
			}
			$this->nom = $nom;
		}
		public function setPrenom(?string $prenom): void{
			if ($prenom === null) {
				$this->prenom = null;
				Errors::add("Prenom null",ErrorLevel::INFO);
				return;
			}
			$prenom = trim($prenom);
			$prenom = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $prenom);
			$prenom = strtoupper($prenom);
			$prenom = preg_replace('/[^A-Za-z]/', '', $prenom);
			$prenom = trim($prenom, '-');
			if ($prenom===''){
				$this->prenom = null;
				Errors::add("Prenom vide ou incorrect",ErrorLevel::INFO);
				return;
			}
			$this->prenom = $prenom;
		}
		public function setPoints(int|string|null $points): void{
			if ($points === null){
				$this->points = null;
				Errors::add("Points null",ErrorLevel::INFO);
				return;
			}
			if (!is_numeric($points) || (int)$points < 0){
				Errors::add("Points invalides : $points",ErrorLevel::ERROR);
				return;
			}
			$this->points = (int)$points;
		}
		public function setTextes(?array $textes): void{
			if ($textes === null){
				$this->textes = [];
				Errors::add("Textes nulls", ErrorLevel::INFO);
				return;
			}
			foreach ($textes as $texte){
				if (!is_array($texte)){
					Errors::add("Textes incorrects (chaque élément doit être un tableau)", ErrorLevel::ERROR);
					return;
				}
				$required = ['header', 'body', 'id'];
				foreach ($required as $key) {
					if (!array_key_exists($key, $texte)){
						Errors::add("Textes incorrects (clé manquante : $key)", ErrorLevel::ERROR);
						return;
					}
				}
				if (!is_string($texte['header']) || !is_string($texte['body'])){
					Errors::add("Textes incorrects (header/body doivent être des chaînes)", ErrorLevel::ERROR);
					return;
				}
				if (!is_string($texte['id']) && !is_int($texte['id'])){
					Errors::add("Textes incorrects (id doit être string ou int)", ErrorLevel::ERROR);
					return;
				}
			}
			$this->textes = $textes;
		}
		public function setLiens(?array $liens): void{
			if ($liens === null){
				$this->liens = [];
				Errors::add("Liens nulls", ErrorLevel::INFO);
				return;
			}
			foreach ($liens as $lien){
				if (!is_array($lien)){
					Errors::add("Liens incorrects (chaque élément doit être un tableau)", ErrorLevel::ERROR);
					return;
				}
				$required = ['uri', 'description', 'id'];
				foreach ($required as $key) {
					if (!array_key_exists($key, $lien)){
						Errors::add("Liens incorrects (clé manquante : $key)", ErrorLevel::ERROR);
						return;
					}
				}
				if (!is_string($lien['uri']) || !is_string($lien['description'])){
					Errors::add("Liens incorrects (uri/description doivent être des chaînes)", ErrorLevel::ERROR);
					return;
				}
				if (!is_string($lien['id']) && !is_int($lien['id'])){
					Errors::add("Liens incorrects (id doit être string ou int)", ErrorLevel::ERROR);
					return;
				}
			}
			$this->liens = $liens;
		}
		public function setNotifications(?array $notifications): void{
			if ($notifications === null){
				$this->notifications = [];
				Errors::add("Notifications nulls", ErrorLevel::INFO);
				return;
			}
			foreach ($notifications as $notification){
				if (!is_array($notification)){
					Errors::add("Notifications incorrects (chaque élément doit être un tableau)", ErrorLevel::ERROR);
					return;
				}
				$required = ['header', 'body', 'id','messageType'];
				foreach ($required as $key) {
					if (!array_key_exists($key, $notification)){
						Errors::add("Notifications incorrects (clé manquante : $key)", ErrorLevel::ERROR);
						return;
					}
				}
				if (!is_string($notification['header']) || !is_string($notification['body'])){
					Errors::add("Notifications incorrects (header/body doivent être des chaînes)", ErrorLevel::ERROR);
					return;
				}
				if (!is_string($notification['messageType']) || $notification['messageType']!=="TEXT_AND_NOTIFY" ){
					Errors::add("Notifications incorrects (messageType doit etre TEXT_AND_NOTIFY)", ErrorLevel::ERROR);
					return;
				}
				if (!is_string($notification['id']) && !is_int($notification['id'])) {
					Errors::add("Notifications incorrects (id doit être string ou int)", ErrorLevel::ERROR);
					return;
				}
			}
			$this->notifications = $notifications;
		}
		
		public function addTextes(string $titre,string $texte):void{
			$id = $this->getNextGlobalId();
			$titre = trim($titre);
			$texte = trim($texte);
			if ($titre === '' || $texte === ''){
				Errors::add("Titre ou texte vide", ErrorLevel::ERROR);
				return;
			}
			$texte = htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$titre = htmlspecialchars($titre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$this->textes[] = ['header' => $titre,'body' => $texte,'id' => $id];
		}
		public function addLiens(string $url, string $texte):void{
			$id = $this->getNextGlobalId();
			$url = trim($url);
			$texte = trim($texte);
			if ($url === '' || $texte === ''){
				Errors::add("Url ou texte vide", ErrorLevel::ERROR);
				return;
			}
			$texte = htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			if (!filter_var($url, FILTER_VALIDATE_URL)) {Errors::add("Url incorrect",ErrorLevel::ERROR);return;}
			$url   = htmlspecialchars($url,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$this->liens[] = ['uri' => $url,'description' => $texte,'id' => $id];
		}
		public function addNotifications(string $titre, string $texte):void{
			$id = $this->getNextGlobalId();
			$titre = trim($titre);
			$texte = trim($texte);
			if ($titre === '' || $texte === ''){
				Errors::add("Titre ou texte vide", ErrorLevel::ERROR);
				return;
			}
			$texte = htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$titre = htmlspecialchars($titre, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
			$this->notifications[] = ['header' => $titre,'body' => $texte,'messageType' => 'TEXT_AND_NOTIFY','id' => $id];
		}
		
		private function getNextIdFromArray(array $items): int{
			$max = 0;
			foreach ($items as $item){
				if (!is_array($item)){
					continue;
				}
				if (!array_key_exists('id', $item)){
					continue;
				}
				$id = is_numeric($item['id']) ? (int)$item['id'] : null;
				if ($id !== null && $id > $max){
					$max = $id;
				}
			}
			return $max + 1;
		}
		private function getNextGlobalId(): int{
			$idTextes = $this->getNextIdFromArray($this->textes);
			$idLiens = $this->getNextIdFromArray($this->liens);
			$idNotifications = $this->getNextIdFromArray($this->notifications);
			return max($idTextes, $idLiens, $idNotifications);
		}	
	}
	
	class Loyality{
		private static  string                       $privateKeyPath = __DIR__ . "/lib/vendor/key.json";
		private static  string                       $walletId       = "3388000000022976665";
		private static  string                       $walletProgram  = "Fidelite01";
		private         string                       $token          = "";
		private         string                       $privateKey     = "";
		private         string                       $mail           = "";
		private         string                       $classId        = "";
		private        ?Google\Service\Walletobjects $service        = null;
		
		public function __construct(private PDO $Database){
			$client = new Google\Client();
			$client->setAuthConfig(self::$privateKeyPath);
			$client->setScopes(['https://www.googleapis.com/auth/wallet_object.issuer']);
			$tokenData = $client->fetchAccessTokenWithAssertion();
			if (!isset($tokenData['access_token'])) {
				Errors::add("Impossible de récupérer le token Google Wallet",ErrorLevel::ERROR);
				return;
			}
			$this->token = $tokenData['access_token'];
			$this->service = new Google\Service\Walletobjects($client);
			$credentials = json_decode(file_get_contents(self::$privateKeyPath), true);
			if (!$credentials) {
				Errors::add("Impossible de lire le fichier de clé Google Wallet",ErrorLevel::ERROR);
				return;
			}
			$this->privateKey = $credentials['private_key'];
			$this->mail       = $credentials['client_email'];
			$this->classId = self::$walletId . "." . self::$walletProgram;
		}
		
		public function save(LoyalityCard $wallet):bool{
			if($this->saveWallet($wallet)){
				if($this->saveSql($wallet)){
					return true;
				}
			}
			return false;
		}
		public function get(string $telephone): ?Wallet{
			$sql = "SELECT url, telephone, nom, prenom, points, textes,uri,messages FROM wallet WHERE telephone = :tel LIMIT 1";
			$stmt = $this->Database->prepare($sql);
			$stmt->bindValue(':tel', $telephone);
			$stmt->execute();
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if (!$row){
				Errors::add("Aucun wallet trouvé pour $telephone", ErrorLevel::WARNING);
				return null;
			}
			$wallet = new Wallet();
			$wallet->setUrl($row['url']);
			$wallet->setTelephone($row['telephone']);
			$wallet->setNom($row['nom']);
			$wallet->setPrenom($row['prenom']);
			$wallet->setPoints((int)$row['points']);
			$wallet->setTextes(json_decode($row['textes'], true) ?: []);
			$wallet->setLiens(json_decode($row['uri'], true) ?: []);
			$wallet->setNotifications(json_decode($row['messages'], true) ?: []);
			return $wallet;
		}
		private function saveWallet(LoyalityCard $wallet):bool{
			$telephone = $wallet->getTelephone();
			$tag = $wallet->getNom().$wallet->getPrenom();
			$points = $wallet->getPoints();
			$textes = [];
			$liens = [];
			$notifications = [];
			if (!$telephone || !$tag || $telephone===null || strlen($tag)<4){
				Errors::add("Loyality card incomplete",ErrorLevel::ERROR);
				return false;
			}
			if ($points===null || $points<=0){
				$points=1;
			}
			$objetId = self::$walletId . "." . $telephone;
			try {
				$this->service->loyaltyobject->get($objetId);
				Errors::add("Le wallet existe déjà", ErrorLevel::LOG);
				return $this->updateWallet($wallet);
			} 
			catch (\Google\Service\Exception $e){
				if ($e->getCode() !== 404){
					Errors::add($e->getMessage(), ErrorLevel::ERROR);
					return false;
				}
			}
			$heroImage = new Google\Service\Walletobjects\Image(['sourceUri' => new Google\Service\Walletobjects\ImageUri(['uri' => "https://royjohan.fr/wp-content/uploads/2025/08/ROYJohanInfo-Ordinateur-apple-scaled.jpg"]),'contentDescription' => new Google\Service\Walletobjects\LocalizedString(['defaultValue' => new Google\Service\Walletobjects\TranslatedString(['language' => 'fr-FR','value' => 'Image de fond'])])]);
			$loyaltyPoints = new Google\Service\Walletobjects\LoyaltyPoints(['label' => 'Nombre d interventions','balance' => new Google\Service\Walletobjects\LoyaltyPointsBalance(['int' => $points])]);
			$data = ['id' => $objetId,'classId' => $this->classId,'state' => 'ACTIVE','heroImage' => $heroImage,'barcode' => new Google\Service\Walletobjects\Barcode(['type' => 'QR_CODE','value' => $telephone]),'accountId' => $telephone,'accountName' => $tag,'loyaltyPoints' => $loyaltyPoints];
			foreach ($wallet->getTextes() ?? [] as $texte){
				$textes[] = new Google\Service\Walletobjects\TextModuleData($texte);
			}
			foreach ($wallet->getLiens() ?? [] as $lien){
				$liens[] = new Google\Service\Walletobjects\Uri($lien);
			}
			foreach ($wallet->getNotifications() ?? [] as $notification){
				$notifications[] = new Google\Service\Walletobjects\Message($notification);
			}
			if (!empty($textes)) {$data['textModulesData'] = $textes;}
			$data['linksModuleData'] = new Google\Service\Walletobjects\LinksModuleData(['uris' => $liens]);
			if (!empty($notifications)) {$data['messages'] = $notifications;}
			$new_object = new Google\Service\Walletobjects\LoyaltyObject($data);
			$claims = ['iss' => $this->mail,'aud' => 'google','origins' => ['www.royjohan.fr'],'typ' => 'savetowallet','payload' => ['loyaltyObjects' => [$new_object->toSimpleObject()]]];
			$jwt_signed = \Firebase\JWT\JWT::encode($claims,$this->privateKey,'RS256');
			$url="https://pay.google.com/gp/v/save/" . $jwt_signed;
			Errors::add("Wallet créé avec success.", ErrorLevel::SUCCESS);
			$wallet->setUrl($url);
			return true;
		}
		private function updateWallet(LoyalityCard $wallet):bool{
			$telephone = $wallet->getTelephone();
			$tag = $wallet->getNom().$wallet->getPrenom();
			$points = $wallet->getPoints();
			$textes = [];
			$liens = [];
			$notifications = [];
			if (!$telephone || !$tag || $telephone===null || strlen($tag)<4){
				Errors::add("Loyality card incomplete",ErrorLevel::ERROR);
				return false;
			}
			if ($points===null || $points<=0){
				$points=1;
			}
			$objetId = self::$walletId . "." . $telephone;
			$url = "https://walletobjects.googleapis.com/walletobjects/v1/loyaltyObject/{$objetId}";
			$loyaltyPoints = new Google\Service\Walletobjects\LoyaltyPoints(['label' => 'Nombre d interventions','balance' => new Google\Service\Walletobjects\LoyaltyPointsBalance(['int' => $points])]);
			$data = ['accountName' => $tag,'loyaltyPoints' => $loyaltyPoints];
			foreach ($wallet->getTextes() ?? [] as $texte){
				$textes[] = new Google\Service\Walletobjects\TextModuleData($texte);
			}
			foreach ($wallet->getLiens() ?? [] as $lien){
				$liens[] = new Google\Service\Walletobjects\Uri($lien);
			}
			foreach ($wallet->getNotifications() ?? [] as $notification){
				$notifications[] = new Google\Service\Walletobjects\Message($notification);
			}
			if (!empty($textes)) {$data['textModulesData'] = $textes;}
			$data['linksModuleData'] = new Google\Service\Walletobjects\LinksModuleData(['uris' => $liens]);
			if (!empty($notifications)) {$data['messages'] = $notifications;}
			$headers = ['Authorization: Bearer ' . $this->token,'Content-Type: application/json',];
			$ch = curl_init($url);curl_setopt_array($ch, [CURLOPT_HTTPHEADER => $headers,CURLOPT_RETURNTRANSFER => true,CURLOPT_CUSTOMREQUEST => 'PATCH',CURLOPT_POSTFIELDS => json_encode($data),CURLOPT_SSL_VERIFYPEER => true,]);
			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			if ($http_code === 200){
				Errors::add("Wallet modifié avec succes",ErrorLevel::SUCCESS);
				return true;
			}
			else{
				Errors::add("HttpError : $http_code !:",ErrorLevel::ERROR);
				return false;
			}
		}
		private function saveSql(LoyalityCard $wallet):bool{
			$telephone = $wallet->getTelephone();
			$tag = $wallet->getNom().$wallet->getPrenom();
			$points = $wallet->getPoints();
			$url = $wallet->getUrl();
			$textes = [];
			$liens = [];
			$notifications = [];
			if (!$telephone || !$tag || $telephone===null || strlen($tag)<4 || $url===null|| $url===''){
				Errors::add("Loyality card incomplete",ErrorLevel::ERROR);
				return false;
			}
			if ($points===null || $points<=0){
				$points=1;
			}
			$sql = "SELECT url FROM wallet WHERE telephone = ? LIMIT 1";
			$stmt = $this->Database->prepare($sql);
			$stmt->execute([$telephone]);
			$res = $stmt->fetch();
			if ($res) {
				Errors::add("Le wallet existe déjà", ErrorLevel::LOG);
				return $this->updateSql($wallet);
			}
			$sql = "INSERT INTO wallet (url, telephone, nom, prenom, points, textes, uri, messages)VALUES (:url, :telephone, :nom, :prenom, :points, :textes, :uri, :messages)";
			$stmt = $this->Database->prepare($sql);
			$stmt->bindValue(':url', $url);
			$stmt->bindValue(':telephone', $telephone);
			$stmt->bindValue(':nom', $wallet->getNom());
			$stmt->bindValue(':prenom', $wallet->getPrenom());
			$stmt->bindValue(':points', $points, PDO::PARAM_INT);
			$stmt->bindValue(':textes', json_encode($wallet->getTextes(), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(':uri', json_encode($wallet->getLiens(), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(':messages', json_encode($wallet->getNotifications(), JSON_UNESCAPED_UNICODE));
			$ok = $stmt->execute();
			if (!$ok) {
				Errors::add("Erreur SQL : " . implode(" | ", $stmt->errorInfo()), ErrorLevel::ERROR);
				return false;
			}
			return true;
		}
		private function updateSql(LoyalityCard $wallet):bool{
			$telephone = $wallet->getTelephone();
			$tag = $wallet->getNom().$wallet->getPrenom();
			$points = $wallet->getPoints();
			$url = $wallet->getUrl();
			$textes = [];
			$liens = [];
			$notifications = [];
			if (!$telephone || !$tag || $telephone===null || strlen($tag)<4 || $url===null|| $url===''){
				Errors::add("Loyality card incomplete",ErrorLevel::ERROR);
				return false;
			}
			if ($points===null || $points<=0){
				$points=1;
			}
			$sql = "SELECT url FROM wallet WHERE telephone = ? LIMIT 1";
			$stmt = $this->Database->prepare($sql);
			$stmt->execute([$telephone]);
			$res = $stmt->fetch();
			if (!$res) {
				Errors::add("Le wallet n'existe pas déjà", ErrorLevel::LOG);
				return false;
			}
			$sql = "UPDATE wallet SET url=:url, nom=:nom, prenom=:prenom, points=:points, textes=:textes, uri=:uri, messages=:messages WHERE telephone=:telephone";
			$stmt = $this->Database->prepare($sql);
			$stmt->bindValue(':url', $url);
			$stmt->bindValue(':telephone', $telephone);
			$stmt->bindValue(':nom', $wallet->getNom());
			$stmt->bindValue(':prenom', $wallet->getPrenom());
			$stmt->bindValue(':points', $points, PDO::PARAM_INT);
			$stmt->bindValue(':textes', json_encode($wallet->getTextes(), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(':uri', json_encode($wallet->getLiens(), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(':messages', json_encode($wallet->getNotifications(), JSON_UNESCAPED_UNICODE));
			$ok = $stmt->execute();
			if (!$ok) {
				Errors::add("Erreur SQL : " . implode(" | ", $stmt->errorInfo()), ErrorLevel::ERROR);
				return false;
			}
			return true;
		}
	}
?>