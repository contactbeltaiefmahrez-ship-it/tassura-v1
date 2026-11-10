# Installation sur OVH mutualisé

## 1. Hébergement

Dans le panneau OVH, sélectionnez PHP8.1 ou ultérieur et activez HTTPS pour le domaine. Le site utilise seulement PHP standard, PDO et PDO MySQL. MySQL et les sessions doivent être disponibles. Une installation MySQL/MariaDB existe déjà dans votre offre d’hébergement : aucune installation serveur supplémentaire n’est requise.

## 2. Fichiers

Transférez par le gestionnaire de fichiers ou FTP **le contenu** de `production` dans le dossier web du domaine, généralement `www`. Conservez les fichiers `.htaccess` cachés et transférez les médias en mode binaire. Ne déplacez pas uniquement index.php : tous les sous-dossiers sont nécessaires.

Le site ne s’ouvre pas en double-cliquant un fichier local : PHP doit être exécuté par l’hébergement. Pas de fichier index.html de substitution.

## 3. Configuration unique

Éditez `config/config.php` :

- `db.host`, `db.port`, `db.name`, `db.user`, `db.password` : identifiants MySQL fournis par OVH.
- `db.socket` : laisser vide sur OVH si la connexion utilise un nom d’hôte.
- `base_url` : origine HTTPS du domaine, sans slash final. Une valeur vide utilise des URL relatives à la racine et reste fonctionnelle.
- `url_prefix` : vide si le site est à la racine ; le chemin du sous-dossier commençant par `/` sinon, sans slash final. N’incluez pas deux fois ce préfixe dans base_url.
- `https_only` : true après activation effective de HTTPS.
- `setup_token` : remplacez la clé aléatoire fournie par une clé privée de votre choix d’au moins32caractères, puis utilisez-la une seule fois pour l’installation.
- `shipping_millimes` : tarif réel entier en millimes,0si la livraison est offerte. Ne laissez pas null pour ouvrir la commande.
- Coordonnées, raison sociale, adresse, immatriculation, conditions réelles de livraison/retour et éventuels liens sociaux.

Les variables d’environnement TASSURA_BASE_URL, TASSURA_URL_PREFIX, TASSURA_DB_HOST, TASSURA_DB_PORT, TASSURA_DB_NAME, TASSURA_DB_USER, TASSURA_DB_PASSWORD, TASSURA_DB_SOCKET, TASSURA_SETUP_TOKEN et TASSURA_SHIPPING_MILLIMES peuvent aussi alimenter ces valeurs. Elles ne sont pas nécessaires sur un mutualisé : l’édition du fichier suffit.

## 4. Base et premier administrateur

Créez une base vide dans OVH Manager. Ouvrez ensuite `install.php` sous l’URL configurée. Saisissez la clé de configuration, votre nom, votre e-mail et votre propre mot de passe (12à72octets). L’installateur importe la structure et les dix pièces puis crée le premier administrateur.

Si vous préférez, importez d’abord `database/schema.sql` via phpMyAdmin, puis ouvrez install.php pour créer votre compte. Le SQL ne crée aucun administrateur, compte client ou commande. La création publique de comptes reste fermée tant que le premier administrateur n’existe pas.

Après réussite, **supprimez install.php** sur l’hébergement. Il se verrouille aussi automatiquement dès qu’un administrateur existe.

## 5. Données de vente

Connectez-vous via la page Compte puis cliquez Administration. Pour chaque pièce : saisissez le prix approuvé, la composition exacte, les instructions d’entretien, les mesures et les quantités réelles par couleur/taille. Les tailles XSàXXL du catalogue sont des positions de stock : n’activez que celles réellement commercialisées. Les données de coupe, coloris et médias sont déjà intégrées.

Le stock initial est0et les prix sont null ; ce ne sont pas des niveaux de stock ni des prix supposés. Le site ouvre la prise de commande pour une pièce uniquement lorsque son prix et son stock le permettent. Les frais de livraison doivent être configurés pour valider la commande.

Pour remplacer une photographie, utilisez le formulaire de sa fiche administrateur. Autorisez l’écriture par PHP dans `private/` et `private/media/` selon les permissions du compte d’hébergement (ne rendez pas le dossier public ; pas de chmod777). Les protections `.htaccess` restent en place.

## 6. Vérifications finales

- Ouvrez la boutique, une fiche produit et les neuf coloris sur mobile et ordinateur.
- Testez une commande autorisée par la maison, puis son annulation depuis l’administration. Le stock doit revenir une seule fois.
- Contrôlez les coordonnées et les conditions de vente avant ouverture publique.
- Ouvrez `tools/verify.php` connecté en administrateur pour le contrôle en lecture seule.
- Vérifiez que `/config/`, `/private/`, `/database/` et leurs fichiers sont refusés en accès HTTP direct.

Les sessions utilisent HttpOnly, SameSite=Lax et Secure dès que HTTPS est actif. La configuration est hors accès HTTP par .htaccess. Les secrets ne doivent jamais être placés dans les fichiers CSS/JS.

## E-mails et paiement

Les commandes sont conservées en MySQL, visibles dans l’administration et confirmées par un lien privé, même sans e-mail. L’envoi facultatif au propriétaire via PHP mail peut être activé dans la configuration après validation de l’adresse d’expédition OVH. Ce mécanisme n’est pas une intégration SMTP ni un prestataire de paiement. Le mode livré est le paiement à la livraison ; aucune saisie de carte bancaire n’est proposée.

## Exploitation

Sauvegardez la base et le dossier private/media avant une mise à jour. Les états suivent : pending→confirmed→packed→shipped→delivered ; l’annulation est possible avant expédition. Une commande annulée ne peut pas être réactivée automatiquement. Les changements de stock concurrentiels sont contrôlés et un enregistrement ancien est refusé pour ne pas écraser une commande récente.
