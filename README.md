# quiqqer/translator

Das quiqqer/translation Packeterweitert QUIQQER um ein Übersetzungs-Panel und Übersetzungsfunktionen.
Mit dem quiqqer/translator ist es möglich Übersetzungsvariablen zu editieren / hinzuzufügen oder zu löschen.
Zusätzluch können Übersetzungen (locale.xml Dateien) importiert und exportiert werden.

Übersetzungen werden in verschiedene Formate gespeichert damit Übersetzungen performant eingesetzt werden können.
JavaScript (Client) und PHP (Server) Variablen werden getrennt aufbereitet und zur Verfügung gestellt.

Es wird versucht auf gettext (http://php.net/manual/de/book.gettext.php) zurück zugreifen,
wenn dies der Server nicht unterstützt werden normale ini Dateien verwendet.
