<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des Capteurs - IUT Blagnac</title>
    <meta http-equiv="refresh" content="10">
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
        <h2>Derniers relevés des capteurs (Temps Réel)</h2>

        <?php
        // 1. Database connection using PDO (PHP Data Objects)
        
        // Database credentials
        $host = 'localhost'; 
        $db   = 'sae23';
        $user = 'neves';
        $pass = 'rt';
        $charset = 'utf8mb4';

        // Set up the Data Source Name (DSN)
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        
        // Connection settings (show errors, format data properly)
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Try to connect to the database
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // If connection fails, stop the script and show an error message
            die("<p style='color:red; text-align:center;'>Erreur de connexion à la base de données : " . $e->getMessage() . "</p>");
        }

        // 2. Complex SQL Query
        // This query gets each sensor's details along with its VERY LATEST measurement
        $sql = "
            SELECT c.nomSalle, c.nomCapteur, c.typeCapteur, c.unite, m.valeur, m.date, m.horaire
            FROM Capteur c
            LEFT JOIN Mesure m ON c.nomCapteur = m.nomCapteur 
                AND m.idMesure = (
                    SELECT MAX(idMesure) 
                    FROM Mesure m2 
                    WHERE m2.nomCapteur = c.nomCapteur
                )
            ORDER BY c.nomSalle ASC, c.typeCapteur ASC;
        ";

        // Execute the query and fetch all results
        $stmt = $pdo->query($sql);
        $resultats = $stmt->fetchAll();

        // 3. Display the HTML table
        // Check if we have any data to show
        if (count($resultats) > 0) {
            // Start creating the table
            echo "<table border='1' style='width:100%; border-collapse: collapse; text-align:center;'>";
            echo "<thead style='background-color:#34495e; color:white;'>
                    <tr>
                        <th style='padding:10px;'>Salle</th>
                        <th style='padding:10px;'>Nom Capteur</th>
                        <th style='padding:10px;'>Type</th>
                        <th style='padding:10px;'>Dernière Valeur</th>
                        <th style='padding:10px;'>Date</th>
                        <th style='padding:10px;'>Heure</th>
                    </tr>
                  </thead>";
            echo "<tbody>";

            // Loop through each row of data
            foreach ($resultats as $row) {
                echo "<tr>";
                // Print basic sensor information (Room, Name, Type)
                echo "<td style='padding:8px;'><strong>" . htmlspecialchars($row['nomSalle']) . "</strong></td>";
                echo "<td style='padding:8px;'>" . htmlspecialchars($row['nomCapteur']) . "</td>";
                echo "<td style='padding:8px;'>" . ucfirst(htmlspecialchars($row['typeCapteur'])) . "</td>";

                // If the sensor has recorded a measurement
                if ($row['valeur'] !== null) {
                    // Fix encoding issue for the degree symbol
                    $unite = str_replace('Â°C', '°C', $row['unite']);
                    
                    // Print the value and the unit
                    echo "<td style='padding:8px;'><strong>" . htmlspecialchars($row['valeur']) . " " . htmlspecialchars($unite) . "</strong></td>";
                    
                    // Format the date to French standard (Day/Month/Year)
                    $date_fr = date("d/m/Y", strtotime($row['date']));
                    echo "<td style='padding:8px;'>" . $date_fr . "</td>";
                    
                    // Print the time
                    echo "<td style='padding:8px;'>" . htmlspecialchars($row['horaire']) . "</td>";
                } else {
                    // If no measurement exists yet, show a waiting message across 3 columns
                    echo "<td colspan='3' style='padding:8px; color:#999; font-style:italic;'>En attente de la première mesure...</td>";
                }
                echo "</tr>";
            }

            // Close the table
            echo "</tbody>";
            echo "</table>";
        } else {
            // If no sensors are found in the database at all
            echo "<p style='text-align:center;'>Aucun capteur enregistré dans la base de données pour le moment.</p>";
        }
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