#!/bin/bash
# Tell the system to run this script using the Bash shell

# Database connection settings
DB_USER="neves"
DB_PASS="rt"
DB_NAME="sae23"

# MQTT broker (server) connection settings
MQTT_BROKER="mqtt.iut-blagnac.fr"
MQTT_PORT=8883
MQTT_USER="student"
MQTT_PASS="student"
# The topic to listen to (the '+' acts as a wildcard for any room name)
MQTT_TOPIC="sensors/AM107/by-room/+/data" 

# Print a startup message to the console
echo "Démarrage de l'écoute MQTT..."

# Connect to the MQTT broker, listen for messages, and start a loop to read the topic and message payload
mosquitto_sub -h "$MQTT_BROKER" -p "$MQTT_PORT" -u "$MQTT_USER" -P "$MQTT_PASS" -t "$MQTT_TOPIC" -v --capath /etc/ssl/certs/ | while read -r topic payload; do
    
    # Extract the room name from the 4th part of the topic path (using '/' as a separator)
    nomSalle=$(echo "$topic" | awk -F'/' '{print $4}')
    
    # Prepare a database query to find all sensors located in this room
    query="SELECT nomCapteur, typeCapteur FROM Capteur WHERE nomSalle='$nomSalle' OR nomCapteur='$nomSalle';"
    
    # Run the query using MySQL and save the results into the 'capteurs' variable
    capteurs=$(/opt/lampp/bin/mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -B -N -e "$query" 2>/dev/null)
    
    # If the query is empty (no sensors found for this room), skip to the next message
    if [ -z "$capteurs" ]; then
        continue
    fi

    # Loop through each sensor found in the database results
    echo "$capteurs" | while IFS=$'\t' read -r nomCapteur typeCapteur; do
        
        # Read the JSON message payload using 'jq' to extract the specific value for this sensor type
        valeur=$(echo "$payload" | jq -r "(if type==\"array\" then .[0] else . end) | .\"$typeCapteur\" // empty")

        # If a value was actually found and it is not "null"
        if [ -n "$valeur" ] && [ "$valeur" != "null" ]; then
            
            # Get the current system date and time
            date_mesure=$(date +%Y-%m-%d)
            heure_mesure=$(date +%H:%M:%S)

            # Prepare the database query to insert the new measurement
            insert_query="INSERT INTO Mesure (date, horaire, valeur, nomCapteur) VALUES ('$date_mesure', '$heure_mesure', $valeur, '$nomCapteur');"
            
            # Run the query to save the data into the database
            /opt/lampp/bin/mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$insert_query"
            
            # Print a success message to the console showing what was saved
            echo "[SUCCÈS] Salle: $nomSalle | Capteur: $nomCapteur ($typeCapteur) | Valeur: $valeur insérée."
        fi
    done
done