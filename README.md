# WebService Personnel

API REST en PHP + MariaDB pour gerer:
- le personnel
- les postes (parametrables)
- les motifs
- les conges

Le projet inclut aussi une interface d administration web.

## Stack
- PHP 8+
- MariaDB / MySQL
- WAMP (ou equivalent)

## Structure
- `index.php`: point d entree API REST
- `database.sql`: schema + donnees de demo
- `config/`: configuration et connexion DB
- `middleware/Auth.php`: auth API par cle
- `models/`: logique metier / CRUD
- `admin/`: interface d administration

## Installation rapide
1. Copier le projet dans le repertoire web (ex: `wamp64/www/WebServices`).
2. Creer la base et les tables avec le script `database.sql`.
3. Ajuster la configuration DB dans `config/config.php`:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`
4. Verifier que l URL locale repond, par exemple:
   - `http://localhost/WebServices/`

## Configuration securite
Dans `database.sql`, une cle API par defaut est inseree dans `api_keys`.
Pensez a la remplacer par une cle forte apres installation.

L API accepte:
- `X-API-Key: <votre_cle>`
- ou `Authorization: Bearer <votre_cle>`

## Interface d administration
- URL: `http://localhost/WebServices/admin/`
- Premiere utilisation:
  - ouvrir `admin/setup.php` pour creer le premier compte admin
- Ensuite:
  - connexion via `admin/login.php`

Fonctionnalites admin:
- gestion du personnel
- gestion des postes (parametrables)
- gestion des motifs
- gestion des conges

## API - Routes principales
### Personnel
- `GET /personnel`
- `GET /personnel/{id}`
- `POST /personnel`
- `PUT /personnel/{id}`
- `DELETE /personnel/{id}`

### Conges
- `GET /conges`
- `GET /conges/{id}`
- `GET /conges/personnel/{id}`
- `POST /conges`
- `PUT /conges/{id}`
- `DELETE /conges/{id}`

### Motifs
- `GET /motifs`
- `GET /motifs/{id}`
- `POST /motifs`
- `PUT /motifs/{id}`
- `DELETE /motifs/{id}`

### Postes
- `GET /postes`
- `GET /postes/{id}`
- `POST /postes`
- `PUT /postes/{id}`
- `DELETE /postes/{id}`

## Exemple appel API (PowerShell)
```powershell
$headers = @{ "X-API-Key" = "VOTRE_CLE_API" }
Invoke-RestMethod -Uri "http://localhost/WebServices/personnel" -Headers $headers -Method GET
```

## CORS
Les constantes CORS sont dans `config/config.php`:
- `CORS_ORIGIN`
- `CORS_METHODS`
- `CORS_HEADERS`

Adaptez ces valeurs pour la production.

## Notes
- Le projet renvoie les erreurs SQL detaillees en mode `development`.
- Passez `APP_ENV` a `production` avant mise en ligne.
