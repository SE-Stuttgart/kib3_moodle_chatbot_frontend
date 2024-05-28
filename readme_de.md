# KI B3 Moodle Assistent Frontend

**ACHTUNG: In der aktuellen Version ist dieses Plugin nur für die Nutzung in KI B3 Moodle Kursen entwickelt.**

 <img src="https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/ab9fb75a-9e14-4bcc-9204-d0c50ea231ec" width="500px"/>

## Installation 

0. **Installieren Sie zuerst das [Backend für den Chatbot](https://github.com/SE-Stuttgart/moodle-block_booksearch)**
1. Klonen Sie anschließend das aktuelle Repository.
2. Speichern Sie den (ggf. entpackten) Code im Verzeichnis `./blocks/chatbot` (relativ zum obersten Verzeichnis Ihres Moodles),  
d.h. die Dateien und Verzeichnisse, die nach dem Klonen bzw. Auspacken im Verzeichnis `kib3_moodle_chatbot_backend` oder `kib3_moodle_chatbot_backend-master` liegen, sollten in Moodle im Verzeichnis `./blocks/chatbot/` liegen. 
3. Öffnen Sie die Website-Administration in Moodle. Dies sollte die Installation starten. 

**ACHTUNG: um die volle Funktionalität des Assistenten zu ermöglichen, muss auch [das Plugin Buchsuche](https://github.com/SE-Stuttgart/moodle-block_booksearch) installiert werden. Es ermöglicht die Suche nach Begriffen in PDF-Dokumenten in den KI B3-Kursen sowohl über ein Suchfenster als auch mithilfe des Assistenten.**

## Konfiguration des Assistenten

1. Gehen Sie zur Website-Administration und dort zu `Plugins`.
2. Klicken Sie im Abschnitt  `Blöcke` auf `Chatbot Plugin`. Dadurch sollte die Seite mit den Einstellungen angezeigt werden (siehe Bildschirmfoto unten).

![Einstellungen](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/0ed6629e-93bc-4a0d-9bc6-87d6ed972e67)

3. Tragen Sie unter `Server name` die IP Adresse oder URL des Servers ein, auf dem das [Chatbot Backend](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_backend) läuft.
4. **Wenn Sie den Moodle Assistenten NICHT im Rahmen [dieser Docker Installation](https://github.com/SE-Stuttgart/kib3_moodle_docker)** verwenden, tragen Sie bei `Event Server Name` denselben Server ein wie bei `Server name`. Andernfalls können Sie diese Einstellung ignorieren - der Default sollte im Rahmen der genannten Dockerinstallation funktionieren.
5. **Falls Sie bei der Installation des Backends den Default Port des** `Chatbot Backend Server` **im Python Code geändert haben (und nur dann!)**, tragen Sie den neuen Port entsprechend bei `Server port` ein. Andernfalls lassen Sie bitte den Default Port eingetragen.
6. Lassen Sie auch den Wert für `Chat Container` unverändert auf dem Default (falls Sie später Probleme mit Ihrem Theme haben, müssen Sie möglicherweise diese Einstellung ändern).
7. **Tragen Sie unter** `Available Courses` **alle Kurse ein, in denen der Assistent verwendet werden soll.**

## Webservices ergänzen

1. Navigieren Sie zur Website-Administration und klicken Sie auf `Server`.
2. Klicken Sie im Abschnitt `Webservices` auf `Übersicht`.
3. Stellen Sie sicher, dass `Webservices aktivieren` eingeschaltet ist (`Ja`).
4. Stellen Sie sicher, dass `Protokolle aktivieren` mindestens `rest` enthält.
5. Klicken Sie auf `Webservice-Nutzer anlegen` und geben Sie dem Nutzer folgende Werte: `Anmeldename`: `kib3_webservice`, wählen Sie ein Passwort, `Vorname`: `KIB3 Webservice`, `Nachname`: `KIB3 Webservice`, wählen Sie eine eMail-Adresse und erstellen Sie den Nutzer.

![webservice user](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/8ab816ee-834b-4281-8d29-071b2645f254)

6. Navigieren Sie zur Website-Administration und `Nutzer/innen`. Klicken Sie im Abschnitt `Rechte` auf `Rollen verwalten`.
7. Klicken Sie ganz unten auf `Neue Rolle hinzufügen`.
8. Wählen Sie die Rolle `Manager/in` und klicken Sie auf `Weiter`.
9. Tragen Sie als `Kurzbezeichnung` `kib3webservice` ein und bei `Angepasster Rollenname` `KIB3 Webservice`. Geben Sie eine Beschreibung an (z.B. Webservice für KIB3 Chatbot)
10. Scrollen Sie ganz nach unten zu der Liste mit Rechten, und erlauben Sie `Webservice-Token erzeugen (moodle/webservice:createtoken)` und `Protokoll REST verwenden (webservice/rest:use)` und speichern Sie.
11. Weisen Sie unter `Nutzer/innen` im Abschnitt `Rechte` mithilfe von `Globale Rollen zuweisen` dem neu erstellten Nutzer `kib3_webservice` die neu erstellte Rolle `KIB3 Webservice` zu.
12. Kommen Sie zurück zur Seite mit dem Überblick über die Webservices (unter `Server`, im Abschnitt `Webservices`). 
13. Klicken Sie `Service auswählen`. Unter `Spezifische Services` klicken Sie bitte auf `Hinzufügen`.
14. Wählen Sie für `Name` und `Kurzbezeichnung` `kib3_webservices`. Stellen Sie sicher, dass außerdem `Aktiviert` und `Nur berechtigte Personen` ausgewählt sind und speichern Sie dies über den Knopf `Service hinzufügen`.
![external service settings](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/ac899c06-e680-4119-8203-d3c919938c4e)
15. Kommen Sie zurück zur Seite mit dem Überblick über die Webservices und klicken Sie auf `Funktionen hinzufügen`.
16. Im Abschnitt `Spezifische Services` klicken Sie in der Zeile für `kib3_webservices` auf den Link `Funktionen`.
17. Klicken Sie auf `Funktionen hinzufügen` und fügen Sie alle Funktionen hinzu, die mit `block_chatbot_`, `block_booksearch_`, `mod_icecreamgame_` beginnen (letztere nur, wenn Sie das [Icecreamgame Plugin](https://github.com/SE-Stuttgart/kib3_moodleplugin_icecreamgame) installiert haben). Tragen Sie hierfür ins Suchfeld nacheinander alle drei gesuchten Anfänge ein, um die verfügbaren Funktionen anzuzeigen und klicken Sie alle an, deren Namen mit den oben genannten Bezeichnungen beginnen. Fügen Sie anschließend auf dieselbe Weise die beiden Funktionen `mod_glossary_get_entries_by_search` und `mod_glossary_get_glossaries_by_courses` hinzu.
18. Navigieren Sie zurück zur Seite mit dem Überblick über die Webservices und klicken Sie auf `Nutzer-Token erzeugen`.
19. Tragen Sie bei `Nutzer/in` den Nutzer KIB3 Webservice ein und stellen Sie sicher, dass unter `Service` kib3_webservices ausgewählt ist und speichern Sie.
![create webservice user token](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/99a2e513-ca7c-49bb-b3e8-8d84b1754d8b)


    
## Einen Block für den Assistenten hinzufügen

1. Gehen Sie zu Ihrer Moodle Startseite. 
2. Schalten Sie Bearbeiten ein. 
3. Klicken Sie in der Seitenleiste auf `Block hinzufügen` (eventuell müssen Sie die Seitenleiste erst einblenden)
4. Wählen Sie `Chatbot` aus.
5. Wenn der Block erstellt ist, klicken Sie auf das Icon mit den Einstellungen und dann auf  `Chatbot konfigurieren`.
6. Wählen Sie im Abschnitt `Blockplatzierung` die Einstellung `Überall auf der Website anzeigen`. Keine Sorge, der Assistent **wird nur in Kursen angezeigt, die bei der Konfiguration in Schritt 7. ausgewählt wurden.**

![block settings](https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/79d748f8-5293-4bc9-b33a-d8cf56cc1c58)


## Chatbot verwenden

1. Navigieren Sie zu dem KI B3-Kurs, in dem der Chatbot verwendet werden soll.
2. Stellen Sie sicher, dass Sie als Teilnehmer/in im Kurs eingeschrieben sind. 
3. Wenn das Backend korrekt läuft und alles korrekt konfiguriert ist, sollte das Chatbotfenster in der unteren rechten Ecke des Browsers zu sehen sein:

<img src="https://github.com/SE-Stuttgart/kib3_moodle_chatbot_frontend/assets/48446789/dee29884-8055-4958-89dc-dbeb8603ef13" width="500px"/>

4. Falls nichts angezeigt wird, überprüfen Sie bitte die Javascript Konsole in Ihrem Browser und das Output des Chatbot Backends auf dem Server, auf dem das Backend installiert wurde.


