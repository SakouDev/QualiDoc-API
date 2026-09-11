# QualiDoc — API

<img src="https://raw.githubusercontent.com/SakouDev/QualiDoc-API/master/QualiDoc.jpg" alt="Logo QualiDoc" width="500">

API REST pour QualiDoc, une plateforme de prise de rendez-vous médicaux.
Test technique développeur full-stack pour QualiJob.

Le front associé (Vue 3) se trouve dans le repo [qualidoc-front](https://github.com/SakouDev/qualidoc-front).

## Stack technique

- **PHP 8.2** / **CodeIgniter 4**
- **MariaDB** (utf8mb4)
- Authentification par **JWT** (`firebase/php-jwt`)
- Documentation API via **OpenAPI 3** + Swagger UI

## Choix techniques

Vue.js, CodeIgniter et MariaDB étaient imposés. Les points suivants étaient libres :

- **JWT plutôt que session serveur** (§5.2) : API stateless consommée par
  un SPA séparé, pas de session à stocker côté back. Durée 15 jours
  (`JWT_EXPIRY`), pas de refresh token — au-delà l'utilisateur se
  reconnecte simplement.
- **Format d'échange** (§5.1) : JSON partout. Le `patient_id` d'un
  rendez-vous vient toujours du token, jamais du body envoyé.
- **Recherche médecins** (§5.3) : recherche préfixe (`LIKE 'terme%'`) sur
  nom/prénom/spécialité, utilisable par un index classique.

## Installation

### Prérequis

- PHP >= 8.2 avec les extensions `intl`, `mbstring`, `mysqlnd`
- Composer
- MariaDB

### Étapes

```bash
composer install
cp env .env
```

Configurer `.env` (hostname, nom de base, identifiants MariaDB, secret JWT) :

```
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = qualidoc
database.default.username = root
database.default.password = root
database.default.DBDriver = MySQLi

JWT_SECRET = change-me-to-a-random-secret
JWT_EXPIRY = 1296000
```

Créer la base puis importer le schéma **en forçant le charset utf8mb4 côté
client** — sans ça, les accents (ex : "Généraliste") sont mal importés :

```bash
mysql -u root -p --default-character-set=utf8mb4 qualidoc < database/schema.sql
```

Lancer le serveur :

```bash
php spark serve
```

L'API est alors disponible sur `http://localhost:8080/api`.

## Avec Docker (bonus)

```bash
docker network create qualidoc
docker compose up -d --build
```

Aucun `.env` requis : `docker-compose.yml` configure tout (base, JWT)
automatiquement — voir le fichier pour le détail des variables injectées.
Seule exception : pour tester l'envoi réel des rappels de rendez-vous
(section suivante), ajoute tes identifiants SMTP dans le bloc
`environment:` du service `api`.

Le container sert l'API via `php spark serve` (serveur de dev intégré à
CodeIgniter), pas via PHP-FPM + Nginx. Choix délibéré pour ce périmètre :
suffisant pour une démo, une vraie mise en prod utiliserait un vrai
serveur applicatif.

## Gestion des disponibilités (bonus)

Chaque médecin créé reçoit automatiquement un planning par défaut (Lun-Ven,
9h-12h + 14h-18h, créneaux de 20min sur 2 semaines) via
`DisponibiliteModel::seedDefault()`. L'admin peut ensuite ajuster par-dessus
via `/admin/disponibilites` (`GET`/`POST`/`DELETE`) — ajouter des plages,
en supprimer (ex : une demi-journée off).

## Rappels de rendez-vous (bonus)

```bash
php spark rappels:envoyer
```

Envoie un vrai mail (SMTP, testé avec [Mailtrap](https://mailtrap.io)) aux
patients dont le rendez-vous confirmé a lieu dans les prochaines 24h.
Anti-doublon via un flag `rappel_envoye` sur le rendez-vous : relancer la
commande plusieurs fois n'envoie jamais deux fois le même rappel, et un
envoi en échec (SMTP indisponible, quota atteint...) reste éligible au
prochain passage plutôt que d'être perdu.

En prod, cette commande serait déclenchée par un vrai cron système
(`* * * * * php spark rappels:envoyer`) — ici on la lance à la main pour
la démo. Configuration SMTP via `.env` :

```
email.protocol = smtp
email.SMTPHost = sandbox.smtp.mailtrap.io
email.SMTPUser = ...
email.SMTPPass = ...
email.SMTPPort = 2525
email.SMTPCrypto = tls
email.fromEmail = noreply@qualidoc.fr
email.fromName = QualiDoc
```

## Organisation Git

Une branche par fonctionnalité, mergée sur `master` via pull request —
`master` est protégée (PR obligatoire, force-push bloqué).

## Tests

```bash
composer test
```

Couvre les parties critiques demandées (§7.5) : authentification
(`tests/feature/AuthTest.php`) et synchronisation front↔API sur la prise
de rendez-vous (`tests/feature/RendezVousTest.php`).

## Documentation de l'API

Doc Swagger sur `/docs.html` une fois le serveur lancé.

### MCD de la BDD

<img src="https://raw.githubusercontent.com/SakouDev/QualiDoc-API/master/database/mcd.svg" alt="MCD QualiDoc" width="500">

## Authentification

Toutes les routes (sauf inscription/connexion et la liste des spécialités)
nécessitent un header `Authorization: Bearer <token>`. Les routes `/admin/*`
nécessitent en plus un compte avec `role = admin`.

## Limites connues

- Suppression d'un médecin : bloquée s'il a des rendez-vous
  `confirme`/`honore` en cours, mais une fois ces rendez-vous annulés la
  suppression cascade et l'historique associé est perdu.
- `duree_creneau` (disponibilités) n'a pas de borne haute validée, et la
  connexion par défaut (`Config/Database.php`) tourne sans mode SQL
  strict — une valeur excessive est silencieusement tronquée par MariaDB
  plutôt que rejetée. Identifié en stress-test ; impact limité (erreur de
  saisie admin, pas un risque sécurité), laissé en l'état et documenté ici.
- Body JSON malformé sur les routes d'auth : renvoie une erreur PHP brute
  (500) plutôt qu'un 400 propre.

## Pistes d'amélioration

- Génération automatique des disponibilités à partir d'un planning
  hebdomadaire récurrent + jours "off", plutôt que blocs saisis un par un.
- Pagination côté serveur sur les listings admin (actuellement côté client).
- Couverture de tests automatisés plus large (au-delà de l'auth et de la
  synchronisation, déjà couvertes).
