<?php
    require_once(__DIR__ . "/SOURCES/controller.php");
	$Controll = new Controller();
    $user = null;
    if (isset($_SESSION['auth']['token'], $_SESSION['auth']['tokenValidity'])) {
		Errors::add("Connexion par token", ErrorLevel::LOG);
		try {
			$validity = new DateTimeImmutable($_SESSION['auth']['tokenValidity']);
			if ($validity > new DateTimeImmutable()) {
				if($Controll->connect($_SESSION['auth']['token'])) {
					$user = $Controll->getUser();
				}
				else{
					Errors::add("Token incorrect", ErrorLevel::ERROR);
				}
			}
			else {
				Errors::add("Token incorrect", ErrorLevel::ERROR);
			}
		}
		catch (Throwable $e) {
			Errors::add("Connexion par token echoué : " . $e->getMessage(), ErrorLevel::LOG);
		}
	}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Client - ROYJohanInfo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="SOURCES/style.css">
    <link rel="stylesheet" href="SOURCES/dashboard.css">
</head>

<body>
<div class="dashboard-container">

    <!-- ==========================
         TUYAU : INFORMATIONS CLIENT
    =========================== -->
    <div class="tile">
        <h2 class="tile-title">Informations du client</h2>

        <div class="client-info">
            <div class="info-row">
                <span class="label">Nom :</span>
                <span class="value"><?= $user ? $user->getNom() : 'DOE' ?> <?= $user ? $user->getPrenom() : 'John' ?></span>
            </div>

            <div class="info-row">
                <span class="label">Email :</span>
                <span class="value"><?= $user ? $user->getEmail() : 'john.doe@example.com' ?></span>
            </div>

            <div class="info-row">
                <span class="label">Téléphone :</span>
                <span class="value"><?= $user ? $user->getTelephone() : '06 12 34 56 78' ?></span>
            </div>

            <div class="info-row">
                <span class="label">Adresse :</span>
                <span class="value"><?= $user ? $user->getAdresse() : '12 rue des Fleurs, 08300 Seuil' ?></span>
            </div>

            <button class="btn-edit">Modifier</button>
        </div>
    </div>

    <!-- ==========================
         TUYAU : APPAREILS
    =========================== -->
    <div class="tile">
        <h2 class="tile-title">Appareils enregistrés</h2>

        <div class="list">
            <div class="list-item">
                <div>
                    <strong>iPhone 12</strong><br>
                    Numéro de série : A1B2C3D4
                </div>
                <button class="btn-small">Voir</button>
            </div>

            <div class="list-item">
                <div>
                    <strong>PC Portable ASUS</strong><br>
                    Numéro de série : Z9X8Y7W6
                </div>
                <button class="btn-small">Voir</button>
            </div>

            <div class="list-item">
                <div>
                    <strong>iPad Air</strong><br>
                    Numéro de série : IPD4458
                </div>
                <button class="btn-small">Voir</button>
            </div>
        </div>
    </div>

    <!-- ==========================
         TUYAU : INTERVENTIONS
    =========================== -->
    <div class="tile">
        <h2 class="tile-title">Interventions</h2>

        <div class="list">
            <div class="list-item">
                <div>
                    <strong>Remplacement écran iPhone 12</strong><br>
                    Statut : En cours (65%)
                </div>
                <button class="btn-small">Détails</button>
            </div>

            <div class="list-item">
                <div>
                    <strong>Nettoyage PC ASUS</strong><br>
                    Statut : Terminé
                </div>
                <button class="btn-small">Détails</button>
            </div>

            <div class="list-item">
                <div>
                    <strong>Diagnostic iPad Air</strong><br>
                    Statut : En attente client
                </div>
                <button class="btn-small">Détails</button>
            </div>
        </div>
    </div>

</div>
<?= Errors::display(); ?>
</body>
</html>
