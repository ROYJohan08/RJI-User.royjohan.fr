<?php
	session_start();
	$libs = ["config","errors","connexion"];
	foreach($libs as $lib){
		if(file_exists(__DIR__ . "/SOURCES/$lib.php")){
			require_once(__DIR__ . "/SOURCES/$lib.php");
		}
	}
	$config = new Config();
	$database = new PDO(
		"mysql:host=".$config->get("BDDHOST").";dbname=".$config->get("BDDBASE").";charset=utf8mb4",
		$config->get("BDDUSERNAME"),
		$config->get("BDDPASSWORD"),
		[
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_EMULATE_PREPARES   => false
		]
	);
	$connexion = new Connexion($database, $config->get("PUBLICKEYPATH"), $config->get("PRIVATEKEYPATH"));
	$connexion->checkDatabase();
	if(
		isset($_SESSION['try']) && 
		(
			(
				isset($_SESSION['try']['COOKIE']) && 
				$_SESSION['try']['COOKIE']>=2
			) || 
			(
				isset($_SESSION['try']['SESSION']) && 
				$_SESSION['try']['SESSION']>=3
			) || 
			(
				isset($_SESSION['try']['POST']) && 
				$_SESSION['try']['POST']>=5
			) || 
			(
				isset($_SESSION['try']['COOKIE']) &&
				isset($_SESSION['try']['SESSION']) && 
				isset($_SESSION['try']['POST']) && 
				$_SESSION['try']['COOKIE']+$_SESSION['try']['SESSION']+$_SESSION['try']['POST']>=5
			)
		)
	){
		$username = $_POST['username'] ?? $_SESSION['username'] ?? $_COOKIE['username'] ?? null;
		$connexion->lock($username);
		exit;
	}
	if (isset($_POST['connexion'])) {
		Errors::add("Connexion par post",ErrorLevel::LOG);
		if (!empty($_POST['username']) && !empty($_POST['password'])) {
			Errors::add("Vars OK",ErrorLevel::LOG);
			if ($connexion->connect($_POST['username'], $_POST['password'])) {
				Errors::add("Connexion OK",ErrorLevel::LOG);
				$_SESSION['auth'] = [
					'token' => $connexion->getToken(),
					'tokenValidity' => $connexion->getTokenValidity()->format('Y-m-d H:i:s')
				];
				if($_POST['remember']){
					$expire = time() + 60 * 60 * 24 * 30;
					setcookie('auth[token]', $connexion->getToken(), [
						'expires'  => $expire,
						'path'     => '/',
						'secure'   => true,
						'httponly' => true,
						'samesite' => 'Strict'
					]);
					setcookie('auth[tokenValidity]', $connexion->getTokenValidity()->format('Y-m-d H:i:s'), [
						'expires'  => $expire,
						'path'     => '/',
						'secure'   => true,
						'httponly' => true,
						'samesite' => 'Strict'
					]);
				}
				Errors::save("Vous êtes connecté", ErrorLevel::SUCCESS);
				return;
			}
			else{
				if(!isset($_SESSION['try']['POST'])){$_SESSION['try']['POST']=0;}
				$_SESSION['try']['POST']++;
			}
		}
	}
	elseif (isset($_SESSION['auth']['token'], $_SESSION['auth']['tokenValidity'])) {
		try {
			$validity = new DateTimeImmutable($_SESSION['auth']['tokenValidity']);
			if ($validity > new DateTimeImmutable()) {
				if ($connexion->checkToken($_SESSION['auth']['token'])) {
					Errors::save("Vous êtes connecté", ErrorLevel::SUCCESS);
					return;
				}
				else{
					if(!isset($_SESSION['try']['SESSION'])){$_SESSION['try']['SESSION']=0;}
					$_SESSION['try']['SESSION']++;
					$_SESSION['auth']=null;
				}
			}
			$_SESSION['auth']=null;
		}
		catch (Throwable $e) {
			$_SESSION['auth']=null;
		}
	}
	elseif (isset($_COOKIE['auth']['token'], $_COOKIE['auth']['tokenValidity'])) {
		try {
			$validity = new DateTimeImmutable($_COOKIE['auth']['tokenValidity']);
			if ($validity > new DateTimeImmutable()) {
				if ($connexion->checkToken($_COOKIE['auth']['token'])) {
					$_SESSION['auth'] = [
						'token' => $_COOKIE['auth']['token'],
						'tokenValidity' => $_COOKIE['auth']['tokenValidity']
					];
					Errors::save("Vous êtes connecté", ErrorLevel::SUCCESS);
					return;
				}
				else{
					if(!isset($_SESSION['try']['COOKIE'])){$_SESSION['try']['COOKIE']=0;}
					$_SESSION['try']['COOKIE']++;
					setcookie('auth[token]', '', time() - 3600, '/');
					setcookie('auth[tokenValidity]', '', time() - 3600, '/');
				}
			}
		} 
		catch (Throwable $e) {
		}
	}

?>
<!DOCTYPE html>
<html lang="fr">
	<head>
		<meta charset="UTF-8">
		<title>Connexion - ROYJohanInfo</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style> </style>
		<link rel="stylesheet" href="SOURCES/style.css" />
	</head>
	<body>
		<div class="login-container">
			<div class="brand">
			<img src="https://royjohan.fr/wp-content/uploads/2025/08/ROYJohanInfo-Logo.png" alt="Logo ROYJohanInfo" class="brand-logo">
			<div class="brand-bar"></div>

			<div class="brand-title">ROYJohanInfo</div>
			<div class="brand-subtitle">Espace de connexion client sécurisé</div>
		</div>
			<div class="card">
				<div class="card-header">
					<h1 class="card-title">Connexion</h1>
					<p class="card-subtitle">Connectez-vous à votre espace ROYJohanInfo</p>
				</div>
				<form method="post" autocomplete="on">
					<div class="field">
						<label for="username">Identifiant</label>
						<input type="text" id="username" name="username" autocomplete="username" required>
					</div>
					<div class="field">
						<label for="password">Mot de passe</label>
						<input type="password" id="password" name="password" autocomplete="current-password" required>
					</div>
					<div class="row-inline">
						<div class="remember-wrapper">
							<div class="checkbox-wrapper-12">
								<div class="cbx">
									<input id="cbx-12" type="checkbox" name="remember"/>
									<label for="cbx-12"></label>
									<svg width="15" height="14" viewBox="0 0 15 14" fill="none">
										<path d="M2 8.36364L6.23077 12L13 2"></path>
									</svg>
								</div>
								<svg xmlns="http://www.w3.org/2000/svg" version="1.1">
									<defs>
										<filter id="goo-12">
											<feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur"></feGaussianBlur>
											<feColorMatrix in="blur" mode="matrix"values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 22 -7"result="goo-12"></feColorMatrix>
											<feBlend in="SourceGraphic" in2="goo-12"></feBlend>
										</filter>
									</defs>
								</svg>
							</div>
							<span>Se souvenir de moi</span>
						</div>
						<div class="links">
							<a href="forgot-password.php">Mot de passe oublié</a>
							<a href="first-login.php">Première connexion</a>
						</div>
					</div>
					<button type="submit" name="connexion" class="btn-submit">Se connecter</button>
					<div class="card-footer">
						© <?= date('Y') ?> ROYJohanInfo
					</div>
				</form>
			</div>
		</div>
		<?= Errors::display(); ?>
	</body>
</html>
