<?php
require_once 'includes/header.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Récupérer les informations actuelles de l'utilisateur
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
	$new_password = $_POST['new_password'];
	$confirm_password = $_POST['confirm_password'];

	// Validation des champs
	if (empty($firstname) || empty($lastname) || empty($email)) {
		$message = "Tous les champs sont requis.";
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$message = "L'adresse email n'est pas valide.";
	} elseif (!empty($new_password) && ($new_password !== $confirm_password)) {
		$message = "Les mots de passe ne correspondent pas.";
	} else {
		try {
			// Vérifier si l'email est déjà utilisé par un autre utilisateur
			$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
			$stmt->execute([$email, $user_id]);
			if ($stmt->fetch()) {
				$message = "Cette adresse email est déjà utilisée par un autre compte.";
			} else {
				// Mise à jour des informations de base
				$stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
				$stmt->execute([$firstname, $lastname, $email, $user_id]);

				// Mise à jour du mot de passe si un nouveau est fourni
				if (!empty($new_password)) {
					if (strlen($new_password) >= 8) { // Vérification de la longueur minimale du mot de passe
						$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
						$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
						$stmt->execute([$hashed_password, $user_id]);
					} else {
						$message = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
					}
				}

				if (empty($message)) {
					$message = "Profil mis à jour avec succès.";
					// Mettre à jour les informations de session
					$_SESSION['user_name'] = "$firstname $lastname";
					$_SESSION['user_email'] = $email;

					header("Location: profile.php");
					exit();
				}
			}
		} catch (PDOException $e) {
			$message = "Erreur lors de la mise à jour du profil : " . $e->getMessage();
		}
	}
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Modifier le profil</title>
   <link rel="stylesheet" href="css/style.css">
   <style>
       .edit-profile-form {
           max-width: 500px;
           margin: 0 auto;
           padding: 20px;
       }
       .form-group {
           margin-bottom: 15px;
       }
       .form-group label {
           display: block;
           margin-bottom: 5px;
       }
       .form-group input {
           width: 100%;
           padding: 8px;
           border: 1px solid #ddd;
           border-radius: 4px;
       }
       .btn-submit {
           background-color: #007bff;
           color: white;
           padding: 10px 15px;
           border: none;
           border-radius: 4px;
           cursor: pointer;
       }
       .message {
           margin-bottom: 15px;
           padding: 10px;
           border-radius: 4px;
       }
       .success { background-color: #d4edda; color: #155724; }
       .error { background-color: #f8d7da; color: #721c24; }
   </style>
</head>
<body>
   <div class="edit-profile-form">
       <h2>Modifier le profil</h2>

       <?php if (!empty($message)): ?>
           <div class="message <?php echo strpos($message, 'succès') !== false ? 'success' : 'error'; ?>">
               <?php echo htmlspecialchars($message); ?>
           </div>
       <?php endif; ?>

       <form action="edit_profile.php" method="post">
           <div class="form-group">
               <label for="firstname">Prénom :</label>
               <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
           </div>
           <div class="form-group">
               <label for="lastname">Nom :</label>
               <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
           </div>
           <div class="form-group">
               <label for="email">Email :</label>
               <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
           </div>
           <div class="form-group">
               <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer) :</label>
               <input type="password" id="new_password" name="new_password">
           </div>
           <div class="form-group">
               <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
               <input type="password" id="confirm_password" name="confirm_password">
           </div>
           <div class="form-group">
               <button type="submit" class="btn-submit">Mettre à jour le profil</button>
               <a href='profile.php' class='btn'>Annuler</a> <!-- Lien pour annuler -->
           </div>
       </form>
   </div>

   <?php require_once 'includes/footer.php'; ?>
</body>
</html> 