<?php
// Start the session to keep the user logged in across pages
session_start();

// Database connection settings
$db_host = "localhost";
$db_user = "neves";
$db_pass = "rt";    
$db_name = "sae23"; 

// Try to connect to the database
$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Stop the script and show an error if the connection fails
if (!$conn) {
    die("Erreur de connexion à la base de données : " . mysqli_connect_error());
}

// Variable to hold login error messages
$erreur_login = "";

// Check if the user submitted the login form
if (isset($_POST['btn_login'])) {
    // Secure the input data to prevent SQL injection
    $login = mysqli_real_escape_string($conn, $_POST['loginGest']);
    $mdp = mysqli_real_escape_string($conn, $_POST['mdpGest']);

    // Query to find the manager in the database
    $req = "SELECT * FROM Batiment WHERE loginGest = '$login' AND mdpGest = '$mdp'";
    $resultat = mysqli_query($conn, $req);

    // If the manager is found (correct login and password)
    if (mysqli_num_rows($resultat) > 0) {
        $row_user = mysqli_fetch_assoc($resultat);
        // Save manager details in the session variables
        $_SESSION['gest_connecte'] = true;
        $_SESSION['idBatiment'] = $row_user['idBatiment'];
        $_SESSION['nomBatiment'] = $row_user['nomBatiment'];
        // Refresh the page to show the logged-in view
        header("Location: gestion.php");
        exit();
    } else {
        // Set an error message if the login is incorrect
        $erreur_login = "Identifiants de gestionnaire incorrects.";
    }
}

// Check if the user clicked the logout link
if (isset($_GET['logout'])) {
    // End the session to log the user out
    session_destroy();
    // Redirect to the login page
    header("Location: gestion.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de Bâtiment</title>
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
        // Check if the user is NOT logged in
        if (!isset($_SESSION['gest_connecte'])) {
        ?>
            <h2>Connexion Gestionnaire</h2>
            <?php if($erreur_login != "") echo "<p style='color:red;'>$erreur_login</p>"; ?>
            
            <form method="post" action="gestion.php">
                <p>
                    <label>Login Gestionnaire :</label><br>
                    <input type="text" name="loginGest" required>
                </p>
                <p>
                    <label>Mot de passe :</label><br>
                    <input type="password" name="mdpGest" required>
                </p>
                <input type="submit" name="btn_login" value="Se connecter">
            </form>

        <?php
        // If the user IS logged in
        } else {
            // Get building info from the session
            $idBatiment = $_SESSION['idBatiment'];
            $nomBatiment = $_SESSION['nomBatiment'];
        ?>
            <h2>Espace Gestion : <?php echo $nomBatiment; ?> (ID: <?php echo $idBatiment; ?>)</h2>
            <p><a href="?logout=1" style="color: red; font-weight: bold;">Se déconnecter</a></p>

            <hr>

            <h3>Statistiques des Salles du Bâtiment (Historique complet)</h3>
            <table border="1" style="width:100%; border-collapse: collapse; text-align:center;">
                <thead style="background-color:#34495e; color:white;">
                    <tr>
                        <th style='padding:8px;'>Nom Salle</th>
                        <th style='padding:8px;'>Type Capteur</th>
                        <th style='padding:8px;'>Unité</th>
                        <th style='padding:8px;'>Capacité</th>
                        <th style='padding:8px;'>Valeur Min</th>
                        <th style='padding:8px;'>Valeur Max</th>
                        <th style='padding:8px;'>Moyenne globale</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Optimized query: get min, max, and average values, grouped by room and sensor type
                $req_stats = "SELECT s.nomSalle, c.typeCapteur, c.unite, s.capaciteAccueil, 
                                     MIN(m.valeur) AS min_val, 
                                     MAX(m.valeur) AS max_val, 
                                     AVG(m.valeur) AS avg_val
                              FROM Salle s
                              JOIN Capteur c ON s.nomSalle = c.nomSalle
                              LEFT JOIN Mesure m ON c.nomCapteur = m.nomCapteur
                              WHERE s.idBatiment = '$idBatiment'
                              GROUP BY s.nomSalle, c.typeCapteur, c.unite";
                              
                $res_stats = mysqli_query($conn, $req_stats);
                
                // Loop through the results and display them in table rows
                while ($row = mysqli_fetch_assoc($res_stats)) {
                    $unite = str_replace('Â°C', '°C', $row['unite']); // Fix display bug for Celsius
                    echo "<tr>";
                    echo "<td style='padding:8px;'><strong>" . $row['nomSalle'] . "</strong></td>";
                    echo "<td style='padding:8px;'>" . ucfirst($row['typeCapteur']) . "</td>";
                    echo "<td style='padding:8px;'>" . $unite . "</td>";
                    echo "<td style='padding:8px;'>" . $row['capaciteAccueil'] . "</td>";
                    // Display values or "N/A" if there is no data
                    echo "<td style='padding:8px;'>" . ($row['min_val'] !== null ? round($row['min_val'], 2) : "N/A") . "</td>";
                    echo "<td style='padding:8px;'>" . ($row['max_val'] !== null ? round($row['max_val'], 2) : "N/A") . "</td>";
                    echo "<td style='padding:8px;'>" . ($row['avg_val'] !== null ? round($row['avg_val'], 2) : "N/A") . "</td>";
                    echo "</tr>";
                }
                ?>
                </tbody>
            </table>

            <hr>

            <h3>Historique des Mesures par Capteur</h3>
            <p>Sélectionnez un capteur de votre bâtiment et définissez la plage horaire.</p>
            
            <form method="get" action="gestion.php">
                <p>
                    <label>Capteur :</label>
                    <select name="nomCapteur" required>
                        <option value="">-- Choisir un capteur --</option>
                        <?php
                        // Fetch the list of sensors available for this building
                        $req_capt = "SELECT c.nomCapteur, c.typeCapteur, c.nomSalle 
                                     FROM Capteur c
                                     JOIN Salle s ON c.nomSalle = s.nomSalle
                                     WHERE s.idBatiment = '$idBatiment'";
                        $res_capt = mysqli_query($conn, $req_capt);
                        
                        // Populate the dropdown menu
                        while ($row_c = mysqli_fetch_assoc($res_capt)) {
                            // Keep the currently selected sensor visible after submitting
                            $selected = (isset($_GET['nomCapteur']) && $_GET['nomCapteur'] == $row_c['nomCapteur']) ? "selected" : "";
                            echo "<option value='" . $row_c['nomCapteur'] . "' $selected>" . $row_c['nomCapteur'] . " (" . $row_c['typeCapteur'] . " - " . $row_c['nomSalle'] . ")</option>";
                        }
                        ?>
                    </select>
                </p>
                <p>
                    <label> Date de début :</label>
                    <input type="date" name="date_debut" value="<?php echo isset($_GET['date_debut']) ? $_GET['date_debut'] : ''; ?>" required>
                    
                    <label> Date de fin :</label>
                    <input type="date" name="date_fin" value="<?php echo isset($_GET['date_fin']) ? $_GET['date_fin'] : ''; ?>" required>
                </p>
                <input type="submit" name="btn_filtrer" value="Filtrer l'historique">
            </form>

            <?php
            // Check if the user requested a filtered history
            if (isset($_GET['btn_filtrer']) && !empty($_GET['nomCapteur'])) {
                // Secure the inputs
                $capteur_sel = mysqli_real_escape_string($conn, $_GET['nomCapteur']);
                $date_deb = mysqli_real_escape_string($conn, $_GET['date_debut']);
                $date_fin = mysqli_real_escape_string($conn, $_GET['date_fin']);

                // Verify that the chosen sensor actually belongs to the user's building
                $verif_capteur = "SELECT c.nomCapteur FROM Capteur c 
                                  JOIN Salle s ON c.nomSalle = s.nomSalle 
                                  WHERE c.nomCapteur = '$capteur_sel' AND s.idBatiment = '$idBatiment'";
                $res_verif = mysqli_query($conn, $verif_capteur);

                // If verification is successful
                if (mysqli_num_rows($res_verif) > 0) {
                    echo "<h4>Résultats pour le capteur : $capteur_sel (Du $date_deb au $date_fin)</h4>";
                    ?>
                    <table border="1" style="width:100%; border-collapse: collapse; text-align:center;">
                        <thead style="background-color:#34495e; color:white;">
                            <tr>
                                <th style='padding:5px;'>ID Mesure</th>
                                <th style='padding:5px;'>Date</th>
                                <th style='padding:5px;'>Horaire</th>
                                <th style='padding:5px;'>Valeur</th>
                                <th style='padding:5px;'>Unité</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Query to get all measurements for the chosen sensor within the dates
                        $req_mesures = "SELECT m.*, c.unite 
                                        FROM Mesure m
                                        JOIN Capteur c ON m.nomCapteur = c.nomCapteur
                                        WHERE m.nomCapteur = '$capteur_sel' 
                                        AND m.date BETWEEN '$date_deb' AND '$date_fin'
                                        ORDER BY m.date DESC, m.horaire DESC";
                                        
                        $res_mesures = mysqli_query($conn, $req_mesures);
                        
                        // If measurements exist, show them
                        if (mysqli_num_rows($res_mesures) > 0) {
                            while ($row_m = mysqli_fetch_assoc($res_mesures)) {
                                $unite = str_replace('Â°C', '°C', $row_m['unite']);
                                echo "<tr>";
                                echo "<td style='padding:5px;'>" . $row_m['idMesure'] . "</td>";
                                // Format the date to day/month/year
                                echo "<td style='padding:5px;'>" . date("d/m/Y", strtotime($row_m['date'])) . "</td>";
                                echo "<td style='padding:5px;'>" . $row_m['horaire'] . "</td>";
                                echo "<td style='padding:5px;'>" . $row_m['valeur'] . "</td>";
                                echo "<td style='padding:5px;'>" . $unite . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            // If no measurements are found for this time period
                            echo "<tr><td colspan='5' style='padding:10px;'>Aucune mesure enregistrée pour cette période.</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                    <?php
                } else {
                    // Security check failed message
                    echo "<p style='color:red;'>Erreur : Vous n'avez pas l'autorisation d'accéder aux données de ce capteur.</p>";
                }
            }
        }
        // Close the database connection to free up resources
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