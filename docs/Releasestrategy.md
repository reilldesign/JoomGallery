# Release-Strategie
Letzte Änderungen: 20.04.2025
[Change to the english version](Releasestrategy_en.md)

Wir folgen einer einfachen, aber klar strukturierten Versions- und Release-Strategie basierend auf [Semantic Versioning (SemVer)](https://semver.org/lang/de/).  

Unsere Versionsnummern folgen dem Schema:

```
MAJOR.MINOR.PATCH
z. B. 5.0.3
```

Releases erfolgen **nicht zeitgesteuert**, sondern **nach Bedarf**, sobald ausreichend Pull Requests (PRs) zusammengekommen, getestet und gemergt wurden.

---

## PATCH-Release (z. B. `4.0.1`)

### Zweck:
Behebt kleinere Fehler (Bugfixes), **ohne** neue Funktionen einzuführen oder bestehende zu verändern.

### Regeln:
- Änderungen erfolgen ausschließlich im `master`-Branch.
- Wird veröffentlicht, sobald ein oder mehrere relevante Bugfixes enthalten sind.
- **Keine Änderung am Verhalten.**

### Beispiele:
- Behebt einen UI-Fehler
- Korrigiert eine falsche Validierung
- Fix für einen Crash in einer bestimmten Situation

---

## MINOR-Release (z. B. `4.1.0`)

### Zweck:
Führt **neue Funktionen** ein, die **rückwärtskompatibel** sind.

### Regeln:
- Feature-Entwicklung findet auf einem dedizierten Branch wie `4.1.0` statt.
- Vor dem Release werden alle relevanten Bugfixes aus `master` **selektiv** in den Feature-Branch gemerged.
- Nach dem Release wird `master` mit dem Stand des Feature-Branches aktualisiert.

### Beispiele:
- Neue Komponenten oder Funktionen
- Verbesserte UX, neue APIs
- Interne Optimierungen mit extern gleichbleibendem Verhalten

---

## MAJOR-Release (z. B. `5.0.0`)

### Zweck:
Führt größere Änderungen ein, die **nicht rückwärtskompatibel** sein müssen.

### Regeln:
- Neue Major-Releases erhalten einen eigenen Branch (`5.0.0`, `6.0.0`, etc.).
- Es kann zu **Breaking Changes** kommen (z. B. geändertes Verhalten, entfernte Funktionen).
- Alte APIs oder Verhaltensweisen werden evtl. entfernt oder durch neue ersetzt.

### Beispiele:
- Umstellung auf komplett neue Architektur
- Entfernen veralteter Funktionen
- Änderungen am Datenmodell oder API-Kontrakt

---

## Release-Zeitpunkte

Es gibt **keinen festen Zeitplan** für Releases.  
Ein Release erfolgt, wenn:

- eine sinnvolle Menge an PRs (Bugfixes oder Features) enthalten ist,
- die Änderungen vollständig getestet wurden,
- die Branchstruktur entsprechend vorbereitet ist.

---

## Vor jedem Release

- [ ] Alle relevanten PRs sind gemerged
- [ ] Branch ist sauber und aktuell
- [ ] Version wird per Git-Tag gekennzeichnet (z. B. `v4.1.0`)
- [ ] Changelog wurde aktualisiert (falls vorhanden)
