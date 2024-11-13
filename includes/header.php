<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinetech Anime</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Accueil</a>
            <form action="search.php" method="GET">
                <input type="text" name="q" placeholder="Rechercher un anime...">
                <button type="submit">Rechercher</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="profile.php">Profil</a>
                <a href="logout.php">Déconnexion</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php">Inscription</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>