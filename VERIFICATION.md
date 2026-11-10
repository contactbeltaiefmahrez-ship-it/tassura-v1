# Vérification de la livraison —10novembre2026

## Exécution réelle

Vérifié avec PHP8.4.27 natif, PDO MySQL et MariaDB11.8.6 / InnoDB dans une base locale isolée. Le code livré vise PHP8.1+. Aucune base SQLite, aucun serveur JavaScript et aucune simulation de commande n’ont été utilisés.

117 contrôles HTTP et10contrôles complémentaires ont réussi. Ces contrôles comprennent les réponses HTTP et les assertions de comportement, pas127scénarios indépendants.

- Installateur : import SQL, création de l’administrateur et verrouillage après installation.
- Authentification : session réelle, password_hash/password_verify, refus des droits administrateur à un client, limitation des tentatives, révocation d’un compte suspendu.
- Boutique :10produits, filtres combinés par prix/catégorie/couleur/taille, tri et échappement des recherches. Les requêtes d’injection ne modifient pas les résultats attendus.
- Fiche :6vues,9aperçus de coloris,5sections détaillées et3recommandations.
- Panier et commande : quantité, recalcul serveur des prix et des frais, CSRF, persistance MySQL, réduction atomique du stock et reprise idempotente sans doublon.
- Annulation : restitution du stock une seule fois.
- Concurrence : deux sessions sur deux processus PHP distincts ont commandé la dernière unité simultanément ; exactement une commande a été créée, sans stock négatif et sans erreur serveur pour la seconde.
- Administration : édition de fiche et de stock, refus d’un formulaire ancien via revision, remplacement d’image réel, refus d’un faux fichier image contenant du PHP, actualisation de l’ETag.
- Médias : vrais octets vidéo, lecture Range206, plage invalide416, traversée de chemin404, images et polices servies avec leur type MIME.
- Chemins : installation à la racine et sous le préfixe /production, y compris actions et ressources.
- Redirections et erreurs : panier vide303, produit/commande inconnus404, aucune sortie prématurée empêchant les en-têtes.
- Formulaire Contact : message réellement enregistré et contenu échappé dans l’administration.

## Vérification visuelle

Le HTML issu du vrai rendu PHP a été inspecté dans Chromium, avec les CSS/JS et médias réels. La prévisualisation graphique est séparée du moteur PHP local : les assertions de base et de commande ont été exécutées par HTTP contre PHP/MySQL, et les interactions visuelles dans le navigateur.

Dimensions examinées : bureau1363×936, téléphone390×844, petit téléphone320×568, tablette820×1100et paysage844×390. Absence de défilement de l’accueil, lecture réelle des films, changement d’univers, galerie horizontale des cartes, panneau de filtres, galerie produit verticale, maintien du panneau d’achat en bas à844px pendant un défilement de1872px, barre de filtres à68px sous l’en-tête pendant le défilement.

La photographie de coloris a été changée via le bouton Vert olive ; les modifications ultérieures de taille et de quantité ont conservé exactement la même URL d’image.

## Intégrité

Tous les fichiers PHP, y compris les conteneurs média, passent php-l. Le lecteur d’intégrité tools/verify.php contrôle les extensions autorisées, l’absence de fichiers vides, les tailles des170conteneurs, la connexion PDO et les540positions de variantes du catalogue. Aucun fichier JSON ni outil de compilation n’est inclus.

La base de contrôle et ses données de test ne sont pas exportées. Le SQL livré ne contient ni commande de test, ni client de test, ni prix fictif. Les prix sont non renseignés et les stocks à0jusqu’à leur saisie par la maison.

## Périmètre restant à vérifier sur l’hébergement

Les identifiants et le domaine OVH n’étant pas fournis, la connexion à cet hébergement précis, son certificat HTTPS, ses permissions FTP et l’envoi réel via son service mail ne peuvent pas être certifiés ici. L’interprétation Apache des .htaccess doit être contrôlée après transfert. Aucune garantie de délivrabilité e-mail ni de conformité juridique des conditions spécifiques de vente n’est donnée par un test logiciel.

Le prestataire de paiement en ligne n’est pas défini ; le mode implémenté est le paiement à la livraison en Tunisie. Les prix, compositions exactes, quantités, mesures, identité légale et politiques de vente doivent être approuvés par la maison avant ouverture.
