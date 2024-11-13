<?php
session_start();
require_once 'db_connect.php'; // Assurez-vous que ce fichier existe et contient la connexion PDO
require_once 'includes/header.php';
$errors = [];
$success = false;

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (!$email) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    if (empty($firstname)) {
        $errors[] = "Le prénom est requis.";
    }
    if (empty($lastname)) {
        $errors[] = "Le nom est requis.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Si pas d'erreurs, procéder à l'enregistrement
    if (empty($errors)) {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn()) {
                $errors[] = "Cette adresse email est déjà utilisée.";
            } else {
                // Insertion de l'utilisateur
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, firstname, lastname, password) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$email, $firstname, $lastname, $hashed_password])) {
                    $success = true;
                    $_SESSION['message'] = "Inscription réussie. Vous pouvez maintenant vous connecter.";
                } else {
                    $errors[] = "Une erreur est survenue lors de l'inscription.";
                }
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>
    <h2>Inscription</h2>
    
    <?php if ($success): ?>
        <p style="color: green;"><?php echo $_SESSION['message']; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form action="register.php" method="post">
        <div>
            <label for="email">Email :</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>
        <div>
            <label for="firstname">Prénom :</label>
            <input type="text" id="firstname" name="firstname" required value="<?php echo htmlspecialchars($firstname ?? ''); ?>">
        </div>
        <div>
            <label for="lastname">Nom :</label>
            <input type="text" id="lastname" name="lastname" required value="<?php echo htmlspecialchars($lastname ?? ''); ?>">
        </div>
        <div>
            <label for="password">Mot de passe :</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <label for="confirm_password">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        <div>
            <button type="submit">S'inscrire</button>
        </div>
    </form>
    <?php require_once 'includes/footer.php'; ?>
</body>
</html>