# Politique de sécurité

## Signaler une vulnérabilité
- Contactez-nous à [security@example.com](mailto:security@example.com) avec un résumé, un impact estimé et étapes de reproduction (logs ou PoC si possible).
- Accusé de réception sous **48 h** ouvrées, premier retour technique sous **5 jours ouvrés**.
- Nous coordonnons la divulgation responsable : merci d’attendre la mise à disposition d’un correctif avant toute publication.

## Surfaces d’attaque à surveiller
- **Authentification & session** : formulaires de connexion, réinitialisation, 2FA, cookies de session.
- **Réservations** : création/modification d’une réservation, gestion de stock de chambres, calendrier.
- **Téléversements** : gestion des médias (photos de chambres, avatars) et conversion d’images.
- **Espace d’administration** : paramétrage global, création de comptes, accès aux données sensibles.

## Bonnes pratiques attendues
- Aucun secret dans le dépôt git : utilisez `.env`, GitHub secrets ou un gestionnaire de secrets.
- Rotation immédiate des clés compromettues et suppression des identifiants inutilisés.
- Gardez les dépendances et l’OS du serveur à jour (correctifs de sécurité appliqués rapidement).
- Activez les audits (`composer audit`, `npm audit`) et corrigez les vulnérabilités critiques avant mise en production.

## Versions supportées
- Branche `main` : reçoit les correctifs de sécurité.
- Toute autre branche (historique, forks) n’est pas couverte et doit être mise à jour avant déploiement.

Merci de contribuer à la sécurité de Riad Projet.
