# Microsoft Software Shop

## 1. Projektbeschreibung

Bei diesem Projekt handelt es sich um einen lokal betriebenen Onlineshop für Microsoft-Softwareprodukte. Ziel ist die Entwicklung einer webbasierten Anwendung mit Frontend, Backend und relationaler Datenbank.

Besucher können die angebotenen Softwareprodukte ansehen, Produkte in den Warenkorb legen und eine Bestellung aufgeben. Über einen separaten Administrationsbereich können berechtigte Administratoren Produkte und Bestellungen verwalten.

Das Projekt wurde im Rahmen einer Studienarbeit entwickelt und dient als lokaler Prototyp. Es ist nicht für den produktiven Einsatz im Internet vorgesehen.

## 2. Funktionen

### Frontend
- Anzeige der verfügbaren Softwareprodukte
- Darstellung von Produktinformationen und Preisen
- Warenkorb mit Sitzungsverwaltung
- Anpassung und Entfernung von Warenkorbpositionen
- Bestellvorgang mit Kundendaten
- Speicherung der Bestellungen in der Datenbank

### Backend (Administrationsbereich)
- Anmeldung und Abmeldung von Administratoren
- Geschützter Administrationsbereich
- Übersicht über vorhandene Produkte
- Produkte anlegen, bearbeiten und verwalten
- Bestellungen anzeigen und verwalten

## 3. Verwendete Technologien

| Technologie | Verwendung |
|---|---|
| PHP | Serverseitige Programmierung |
| HTML | Strukturierung der Webseiten |
| CSS | Gestaltung und Layout |
| JavaScript | Interaktive Funktionen |
| MariaDB | Relationale Datenbank |
| Apache | Lokaler Webserver |
| XAMPP | Lokale Entwicklungsumgebung |
| Visual Studio Code | Entwicklungsumgebung |

## 4. Voraussetzungen

Für die lokale Ausführung werden folgende Komponenten benötigt:

- Windows, Linux oder macOS
- XAMPP mit Apache, PHP und MariaDB
- Ein moderner Webbrowser
- Git (optional, zum Klonen des Repositorys)

## 5. Installation und Einrichtung

### Schritt 1: XAMPP installieren

XAMPP kann kostenlos über die offizielle Webseite heruntergeladen werden:

https://www.apachefriends.org/

Nach der Installation das XAMPP Control Panel öffnen und folgende Dienste starten:

- Apache
- MySQL (MariaDB)

### Schritt 2: Projekt herunterladen

Das Repository kann über Git geklont werden:

```bash
https://github.com/jamilkoujar01/my-online-shop-Uni
```

Alternativ kann das Projekt über GitHub als ZIP-Datei heruntergeladen werden.

Anschließend den Projektordner in das XAMPP-Verzeichnis `htdocs` kopieren.

Der lokale Projektpfad sollte beispielsweise so aussehen:

```text
C:\xampp\htdocs\software-shop\
```

### Schritt 3: Datenbank einrichten

1. Einen Browser öffnen.
2. `http://localhost/phpmyadmin/` aufrufen.
3. Eine neue Datenbank mit dem Namen `software_shop` erstellen.
4. Die zum Projekt gehörende SQL-Datei importieren, sofern sie im Repository bereitgestellt wird.

**Hinweis:** Falls noch keine SQL-Datei im Repository vorhanden ist, muss die Datenbankstruktur vor der Ausführung angelegt und als SQL-Datei exportiert werden. So kann die Datenbank auch auf anderen Computern eingerichtet werden.

### Schritt 4: Datenbankverbindung konfigurieren

Die lokale Datenbankverbindung wird in der Konfigurationsdatei des Projekts eingerichtet.

Falls eine Beispieldatei wie `config/database.example.php` vorhanden ist, kann diese als Vorlage verwendet werden.

Die lokale Konfigurationsdatei muss die passenden Verbindungsdaten enthalten:

- Host: `localhost`
- Datenbankname: `software_shop`
- Benutzername: `root` (bei einer entsprechenden lokalen XAMPP-Standardkonfiguration)
- Passwort: abhängig von der lokalen MariaDB-Konfiguration

Die tatsächlichen Zugangsdaten werden nicht im öffentlichen GitHub-Repository gespeichert.

### Schritt 5: Onlineshop starten

Nachdem Apache und MySQL gestartet und die Datenbank eingerichtet wurden, kann der Onlineshop im Browser aufgerufen werden:

**Frontend:**

http://localhost/software-shop/

Der Administrationsbereich ist über den im Projekt vorgesehenen Admin-Pfad erreichbar.

## 6. Datenbankstruktur

Die Anwendung verwendet eine relationale MariaDB-Datenbank mit folgenden Tabellen:

| Tabelle | Beschreibung |
|---|---|
| `admins` | Speicherung der Administratoren |
| `categories` | Verwaltung der Produktkategorien |
| `products` | Speicherung der Softwareprodukte |
| `orders` | Speicherung der Bestellungen |
| `order_items` | Speicherung der einzelnen Bestellpositionen |

Die Tabellen sind über Primär- und Fremdschlüssel miteinander verbunden.

Die Bestellpositionen speichern zusätzlich den Produktnamen und den Einzelpreis zum Zeitpunkt der Bestellung. Dadurch bleiben die ursprünglichen Bestelldaten auch dann nachvollziehbar, wenn sich Produktinformationen später ändern.

## 7. Sicherheit und Datenschutz

Bei der Entwicklung wurden grundlegende Sicherheitsmaßnahmen berücksichtigt:

- Passwort-Hashing für Administratorzugänge
- Prepared Statements für Datenbankabfragen
- Serverseitige Eingabevalidierung
- Sitzungsverwaltung für den Warenkorb und den Administrationsbereich
- Schutz vor CSRF-Angriffen durch CSRF-Tokens
- Keine Veröffentlichung vertraulicher Zugangsdaten im öffentlichen Repository

Es handelt sich um einen lokalen Entwicklungsprototyp. Vor einem produktiven Einsatz wären weitere Sicherheits- und Datenschutzmaßnahmen erforderlich.

## 8. Einschränkungen

Folgende Funktionen sind im aktuellen Projektstand nicht vollständig umgesetzt:

- Keine Registrierung und Anmeldung für Kunden
- Keine echte Zahlungsabwicklung
- Keine Anbindung an einen externen Zahlungsanbieter
- Kein produktiver Betrieb im Internet

Die Zahlungsoptionen dienen lediglich als Platzhalter und lösen keine tatsächlichen Zahlungsvorgänge aus.

## 9. Projektziel

Das Projekt zeigt die grundlegende Entwicklung eines webbasierten Onlineshops mit Frontend, Backend und Datenbank.

Dabei werden insbesondere die Verbindung zwischen PHP und MariaDB, die Verarbeitung von Bestellungen, die Verwaltung von Produkten sowie grundlegende Sicherheitsmaßnahmen umgesetzt.

---

**Projektart:** Studienprojekt / lokaler Webshop-Prototyp  
**Betriebsumgebung:** XAMPP  
**Datenbank:** MariaDB  
**Programmiersprache:** PHP
