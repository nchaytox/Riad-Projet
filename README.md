# Riad Projet

Riad Projet est une application de gestion de riad qui centralise les réservations de chambres, la disponibilité en temps réel et la communication entre les équipes. L’objectif est d’offrir une expérience fluide aux voyageurs tout en simplifiant le travail quotidien de l’équipe sur place.

Le MVP couvre la réservation de chambres, la gestion des séjours par les employés et le pilotage administratif (clients, planning, inventaire) pour les administrateurs. Chaque parcours est livré dans l’interface web Laravel.

## Stack technique
- Laravel 12 · PHP 8.2+
- MySQL 8 (compatible MariaDB) pour la persistance
- Vite + Tailwind CSS + Blade pour l’UI
- Serveur web local : `php artisan serve` (HTTP) + `npm run dev` pour les assets

## Démarrage rapide (sans Docker)
- **Prérequis** : PHP 8.2, Composer 2, Node.js 18 (ou +) et npm, MySQL 8 disponible en local.
- **Installer le projet**
  ```bash
  git clone https://github.com/nchaytox/Riad-Projet.git
  cd Riad-Projet
  cp .env.example .env
  composer install
  npm install
  ```
- **Configurer `.env`** : renseigner `APP_URL=http://localhost:8000`, mettre à jour les variables `DB_*` et vérifier `MAIL_MAILER=log`.
- **Initialiser l’application**
  ```bash
  php artisan key:generate
  php artisan migrate --seed
  php artisan storage:link
  ```
- **Lancer les services**
  ```bash
  php artisan serve      # http://localhost:8000
  npm run dev            # http://localhost:5173 pour les assets Vite
  ```

Un nouvel arrivant peut ainsi découvrir le projet en moins de 10 minutes sur `http://localhost:8000`.

## Comptes de démo
| Rôle | Email | Mot de passe | Notes |
| --- | --- | --- | --- |
| Administrateur | admin.demo@example.com | password | Accès complet (configurations, utilisateurs, chambres). |
| Employé | staff.demo@example.com | password | Gestion quotidienne : arrivées, départs, disponibilité. |
| Client | guest.demo@example.com | password | Tableau de bord client et historique de réservations. |

Ces comptes sont créés automatiquement par `php artisan migrate --seed`. Modifiez `database/seeders/UserSeeder.php` si vous souhaitez des identifiants différents.

## Documentation
- [Architecture générale](docs/architecture.md)
- [Modèle métier (réservations)](docs/domain-model.md)
- [Runbook exploitation](docs/runbook.md)
- [Sécurité & durcissement](docs/security-hardening.md)
- [Process CI et contrôles](docs/ci-security.md)
- [Gestion des environnements](docs/environments.md)
- [Modèle de menaces](docs/threat-model.md)
- [Topologie de déploiement](docs/deployment-topology.md)

## Ressources utiles
- Contribution : [CONTRIBUTING.md](CONTRIBUTING.md)
- Code de conduite : [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- Politique de sécurité : [SECURITY.md](SECURITY.md)
- Licence : [LICENSE](LICENSE)
