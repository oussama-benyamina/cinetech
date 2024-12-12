<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cinetech Anime</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <a href="index.php">Accueil</a>
            <form action="search.php" method="GET">
                <input type="text" id="search-input" name="q" placeholder="Rechercher un anime..." autocomplete="off">
                <div id="autocomplete-suggestions" class="autocomplete-suggestions"></div>
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