<?php
// Start the session to keep the user logged in
session_start();

// Database connection details
$db_host = "localhost";
$db_user = "neves";
$db_pass = "rt";    
$db_name = "sae23"; 

// Connect to the database
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Check if the connection failed
if (!$conn) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}

// Variable to store login error messages
$erreur_login = "";

// If the login form is submitted
if (isset($_POST['btn_login'])) {
    // Clean the input to prevent security issues (SQL Injection)
    $login = mysqli_real_escape_string($conn, $_POST['loginAdmin']);
    $mdp = mysqli_real_escape_string($conn, $_POST['mdpAdmin']);

    // Check if the admin exists in the database
    $req = "SELECT * FROM Administration WHERE loginAdmin = '$login' AND mdpAdmin = '$mdp'";
    $resultat = mysqli_query($conn, $req);

    // If a match is found, log the user in
    if (mysqli_num_rows($resultat) > 0) {
        $_SESSION['connecte'] = true;
        header("Location: admin.php");
        exit();
    } else {
        // If no match, show an error
        $erreur_login = "Identifiants incorrects.";
    }
}

// If the user clicks logout
if (isset($_GET['logout'])) {
    // Destroy the session and refresh the page
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Check if the user is connected before allowing actions
if (isset($_SESSION['connecte']) && $_SESSION['connecte'] === true) {
    
    // --- BUILDING MANAGEMENT ---
    
    // Add a new building to the database
    if (isset($_POST['add_batiment'])) {
        $id = mysqli_real_escape_string($conn, $_POST['idBatiment']);
        $nom = mysqli_real_escape_string($conn, $_POST['nomBatiment']);
        $logGest = mysqli_real_escape_string($conn, $_POST['loginGest']);
        $mdpGest = mysqli_real_escape_string($conn, $_POST['mdpGest']);
        
        $req = "INSERT INTO Batiment (idBatiment, nomBatiment, loginGest, mdpGest) VALUES ('$id', '$nom', '$logGest', '$mdpGest')";
        mysqli_query($conn, $req);
    }
    
    // Delete a building from the database
    if (isset($_GET['del_batiment'])) {
        $id = mysqli_real_escape_string($conn, $_GET['del_batiment']);
        $req = "DELETE FROM Batiment WHERE idBatiment = '$id'";
        mysqli_query($conn, $req);
    }

    // --- ROOM MANAGEMENT ---
    
    // Add a new room to the database
    if (isset($_POST['add_salle'])) {
        $nom = mysqli_real_escape_string($conn, $_POST['nomSalle']);
        $type = mysqli_real_escape_string($conn, $_POST['typeSalle']);
        $cap = mysqli_real_escape_string($conn, $_POST['capaciteAccueil']);
        $idBat = mysqli_real_escape_string($conn, $_POST['idBatiment']);
        
        $req = "INSERT INTO Salle (nomSalle, typeSalle, capaciteAccueil, idBatiment) VALUES ('$nom', '$type', '$cap', '$idBat')";
        mysqli_query($conn, $req);
    }
    
    // Delete a room from the database
    if (isset($_GET['del_salle'])) {
        $nom = mysqli_real_escape_string($conn, $_GET['del_salle']);
        $req = "DELETE FROM Salle WHERE nomSalle = '$nom'";
        mysqli_query($conn, $req);
    }

    // --- SENSOR MANAGEMENT ---
    
    // Add a new sensor to the database
    if (isset($_POST['add_capteur'])) {
        $nom = mysqli_real_escape_string($conn, $_POST['nomCapteur']);
        $type = mysqli_real_escape_string($conn, $_POST['typeCapteur']);
        $unite = mysqli_real_escape_string($conn, $_POST['unite']);
        $nomSalle = mysqli_real_escape_string($conn, $_POST['nomSalle']);
        
        $req = "INSERT INTO Capteur (nomCapteur, typeCapteur, unite, nomSalle) VALUES ('$nom', '$type', '$unite', '$nomSalle')";
        mysqli_query($conn, $req);
    }
    
    // Delete a sensor from the database
    if (isset($_GET['del_capteur'])) {
        $nom = mysqli_real_escape_string($conn, $_GET['del_capteur']);
        $req = "DELETE FROM Capteur WHERE nomCapteur = '$nom'";
        mysqli_query($conn, $req);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>Projet SAÉ 23</h1>
        <p>Mettre en place une solution informatique pour l'entreprise</p>
    </header>

    <nav>
        <a href="index.html">Accueil</a>
        <a href="admin.php">Administration</a>
        <a href="gestion.php">Gestion</a>
        <a href="consultation.php">Consultation</a>
        <a href="gestion_projet.html">Gestion de projet</a>
    </nav>

    <div class="container">
        <?php
        // If the user is not logged in, show the login form
        if (!isset($_SESSION['connecte'])) {
        ?>
            <h2>Connexion Administration</h2>
            
            <?php if($erreur_login != "") echo "<p style='color:red;'>$erreur_login</p>"; ?>
            
            <form method="post" action="admin.php">
                <p>
                    <label>Login Admin:</label><br>
                    <input type="text" name="loginAdmin" required>
                </p>
                <p>
                    <label>Mot de passe:</label><br>
                    <input type="password" name="mdpAdmin" required>
                </p>
                <input type="submit" name="btn_login" value="Se connecter">
            </form>

        <?php
        // If the user is logged in, show the admin panel
        } else {
        ?>
            <h2>Panneau d'Administration</h2>
            
            <p><a href="?logout=1" style="color: red; font-weight: bold;">Se déconnecter</a></p>

            <hr>

            <h3>Gestion des Bâtiments</h3>
            <table border="1" style="width:100%; border-collapse: collapse; text-align:center;">
                <thead style="background-color:#34495e; color:white;">
                    <tr>
                        <th style='padding:5px;'>ID Bâtiment</th>
                        <th style='padding:5px;'>Nom</th>
                        <th style='padding:5px;'>Login Gest</th>
                        <th style='padding:5px;'>Mdp Gest</th>
                        <th style='padding:5px;'>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Get all buildings from the database and display them in a table
                $req_bat = "SELECT * FROM Batiment";
                $res_bat = mysqli_query($conn, $req_bat);
                while ($row = mysqli_fetch_assoc($res_bat)) {
                    echo "<tr>";
                    echo "<td style='padding:5px;'>" . $row['idBatiment'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['nomBatiment'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['loginGest'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['mdpGest'] . "</td>";
                    echo "<td style='padding:5px;'><a href='?del_batiment=" . $row['idBatiment'] . "'>Supprimer</a></td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
            
            <h4>Ajouter un bâtiment</h4>
            <form method="post" action="admin.php">
                ID: <input type="number" name="idBatiment" required>
                Nom: <input type="text" name="nomBatiment" required>
                Login: <input type="text" name="loginGest">
                Mdp: <input type="text" name="mdpGest">
                <input type="submit" name="add_batiment" value="Ajouter">
            </form>

            <hr>

            <h3>Gestion des Salles</h3>
            <table border="1" style="width:100%; border-collapse: collapse; text-align:center;">
                <thead style="background-color:#34495e; color:white;">
                    <tr>
                        <th style='padding:5px;'>Nom Salle</th>
                        <th style='padding:5px;'>Type</th>
                        <th style='padding:5px;'>Capacité</th>
                        <th style='padding:5px;'>ID Bâtiment</th>
                        <th style='padding:5px;'>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Get all rooms from the database and display them in a table
                $req_salle = "SELECT * FROM Salle";
                $res_salle = mysqli_query($conn, $req_salle);
                while ($row = mysqli_fetch_assoc($res_salle)) {
                    echo "<tr>";
                    echo "<td style='padding:5px;'>" . $row['nomSalle'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['typeSalle'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['capaciteAccueil'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['idBatiment'] . "</td>";
                    echo "<td style='padding:5px;'><a href='?del_salle=" . $row['nomSalle'] . "'>Supprimer</a></td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
            
            <h4>Ajouter une salle</h4>
            <form method="post" action="admin.php">
                Nom: <input type="text" name="nomSalle" required>
                Type: <input type="text" name="typeSalle">
                Capacité: <input type="number" name="capaciteAccueil">
                ID Bâtiment: <input type="number" name="idBatiment" required>
                <input type="submit" name="add_salle" value="Ajouter">
            </form>

            <hr>

            <h3>Gestion des Capteurs</h3>
            <table border="1" style="width:100%; border-collapse: collapse; text-align:center;">
                <thead style="background-color:#34495e; color:white;">
                    <tr>
                        <th style='padding:5px;'>Nom Capteur</th>
                        <th style='padding:5px;'>Type</th>
                        <th style='padding:5px;'>Unité</th>
                        <th style='padding:5px;'>Nom Salle</th>
                        <th style='padding:5px;'>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Get all sensors from the database and display them in a table
                $req_capteur = "SELECT * FROM Capteur";
                $res_capteur = mysqli_query($conn, $req_capteur);
                while ($row = mysqli_fetch_assoc($res_capteur)) {
                    // Fix encoding issue for the degree symbol
                    $unite = str_replace('Â°C', '°C', $row['unite']);
                    
                    echo "<tr>";
                    echo "<td style='padding:5px;'>" . $row['nomCapteur'] . "</td>";
                    echo "<td style='padding:5px;'>" . $row['typeCapteur'] . "</td>";
                    echo "<td style='padding:5px;'>" . $unite . "</td>";
                    echo "<td style='padding:5px;'>" . $row['nomSalle'] . "</td>";
                    echo "<td style='padding:5px;'><a href='?del_capteur=" . $row['nomCapteur'] . "'>Supprimer</a></td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>
            
            <h4>Ajouter un capteur</h4>
            <form method="post" action="admin.php">
                Nom: <input type="text" name="nomCapteur" required>
                Type: <input type="text" name="typeCapteur">
                Unité: <input type="text" name="unite">
                Nom Salle: <input type="text" name="nomSalle" required>
                <input type="submit" name="add_capteur" value="Ajouter">
            </form>

        <?php
        }
        
        // Close the database connection when finished
        mysqli_close($conn);
        ?>
    </div>

    <footer>
        <p><strong>Mentions Légales</strong></p>
        <p>© 2026 - IUT de Blagnac - Département Réseaux et Télécommunications.</p>
        <p><em>Éditeur :</em> NEVES Ruben - PERIN Nicolas - CHONE Arthur - VACHER Maël.</p>
        <p><em>Hébergement :</em> Serveur local (LAMPP / Machine Virtuelle Lubuntu).</p>
        <p><em>Données :</em> Les mesures affichées proviennent de capteurs IoT internes à des fins strictement éducatives.</p>
    </footer>

</body>
</html>