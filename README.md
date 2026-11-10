# TASSURA — Production PHP / MySQL

Une maison tunisienne de mode pudique, à l’âme amazighe.

Le dossier `production` contient le site complet à installer sur un hébergement PHP8.1+ / MySQL avec PDO MySQL. Aucune compilation, aucun package JavaScript et aucun accès SSH ne sont nécessaires.

## Expérience livrée

- Accueil plein écran sans défilement, six films réels fournis, trois univers, lecture/pause et respect du mouvement réduit.
- Boutique avec barre de filtres fixe et panneau de recherche par prix, catégorie, coloris et taille disponibles, tri côté serveur.
- Six photos parcourables horizontalement dans chaque carte. Sous la photographie : uniquement le nom et le prix.
- Une vraie page par produit, six vues verticales, neuf aperçus de coloris, sections détaillées et trois recommandations réelles.
- Seule une sélection explicite de coloris change sa photographie d’aperçu. La taille, la quantité et le scroll ne recolorent jamais l’image.
- Sur ordinateur, colonne d’achat adhérente ; sur téléphone, un même panneau d’achat fixé en bas et réductible. Il contient coloris, tailles, quantité et ajout au panier.
- Panier en session PHP, commande avec paiement à la livraison en Tunisie, validation des prix et du stock côté serveur, historique et suivi par lien privé.
- Comptes clients et administrateur, mots de passe hachés, protection CSRF, requêtes PDO préparées, limitation des tentatives et révocation des comptes suspendus.
- Administration : fiches produit, prix, composition, mesures, stock par variante, remplacement de photos, commandes, états et messages.

## Mise en service

Suivez `INSTALLATION.md`. Importez `database/schema.sql` via l’installateur ou phpMyAdmin, puis créez votre propre administrateur. Aucun compte ni mot de passe public n’est fourni.

Les dix produits, leurs descriptions de coupe et les neuf coloris proviennent du travail TASSURA approuvé. Les anciennes valeurs de prix et contacts étaient explicitement fictives : elles ne sont pas reprises. Les prix restent à renseigner, le stock initial est0, et les frais de livraison restent non définis. Saisissez les données commerciales réelles avant l’ouverture des ventes. Le code de commande est opérationnel et testé sur une base isolée, sans données de test dans cet export.

## Ressources et extensions autorisées

Le projet contient uniquement `.php`, `.css`, `.js`, `.sql`, `.htaccess` et `.md`. Il n’y a aucun fichier JSON, Node, npm, React, Vue, Composer, dépendance CDN ou appel à une API externe.

Les images, polices et vidéos sont des octets binaires originaux/optimisés encapsulés après `__halt_compiler();` dans `private/media/*.php`. Ces fichiers ne sont jamais inclus ni exécutés par le lecteur de médias. `media.php` sert uniquement la plage d’octets utile avec le vrai type MIME. La lecture vidéo prend en charge HTTP Range. Les images sont de vraies images ; aucune recoloration CSS n’est appliquée. Cette organisation respecte la liste stricte d’extensions sans supprimer les ressources visuelles.

## Organisation

| Chemin | Rôle |
| --- | --- |
| `index.php` | Routeur des pages publiques |
| `config/config.php` | Paramètres centralisés du site et de la base |
| `includes/` | Sécurité, session, PDO et composants |
| `pages/` | Accueil, boutique, produit, compte, panier, commande et informations |
| `actions/` | Actions POST réelles avec CSRF |
| `admin/` | Administration authentifiée |
| `database/schema.sql` | Structure complète et catalogue TASSURA sans commandes fictives |
| `assets/css/site.css` | Design responsive centralisé |
| `assets/js/site.js` | Interactions natives |
| `private/media-index.php` | Index interne des médias |
| `private/media/` | Médias embarqués et protégés |
| `tools/verify.php` | Vérification en lecture seule, réservée à l’administration sur le web |

Voir aussi `VERIFICATION.md` et `docs/HANDOVER_AR.md`.
