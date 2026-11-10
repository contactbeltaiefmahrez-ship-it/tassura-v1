-- TASSURA — MySQL 8 / MariaDB 10.4+ ; database selected by config/config.php
SET NAMES utf8mb4;
CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED PRIMARY KEY, slug VARCHAR(60) UNIQUE NOT NULL, name VARCHAR(100) NOT NULL, sort_order INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS colors (id INT UNSIGNED PRIMARY KEY, name VARCHAR(80) NOT NULL, hex CHAR(7) NOT NULL, sort_order INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS sizes (id INT UNSIGNED PRIMARY KEY, label VARCHAR(10) UNIQUE NOT NULL, sort_order INT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS products (
 id INT UNSIGNED PRIMARY KEY, category_id INT UNSIGNED NOT NULL, slug VARCHAR(120) UNIQUE NOT NULL, name VARCHAR(120) NOT NULL,
 price_millimes INT UNSIGNED NULL, short_description VARCHAR(400) NOT NULL, description TEXT NOT NULL, composition TEXT NULL, care TEXT NULL, size_guide TEXT NULL,
 default_color_id INT UNSIGNED NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, sort_order INT NOT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id), FOREIGN KEY(default_color_id) REFERENCES colors(id), INDEX(category_id,active,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS product_images (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, media_id VARCHAR(100) NOT NULL, caption VARCHAR(255) NOT NULL, sort_order INT NOT NULL, UNIQUE(product_id,sort_order), FOREIGN KEY(product_id) REFERENCES products(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS variants (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, color_id INT UNSIGNED NOT NULL,size_id INT UNSIGNED NOT NULL, stock INT UNSIGNED NOT NULL DEFAULT 0, revision INT UNSIGNED NOT NULL DEFAULT 0, UNIQUE(product_id,color_id,size_id), FOREIGN KEY(product_id) REFERENCES products(id), FOREIGN KEY(color_id) REFERENCES colors(id), FOREIGN KEY(size_id) REFERENCES sizes(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,role ENUM('customer','admin') NOT NULL DEFAULT 'customer',active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS rate_limits (id CHAR(64) PRIMARY KEY,hits INT UNSIGNED NOT NULL,window_start INT UNSIGNED NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(40) UNIQUE NOT NULL, user_id INT UNSIGNED NULL, access_token CHAR(64) UNIQUE NOT NULL, checkout_key CHAR(48) UNIQUE NOT NULL,
 customer_name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,phone VARCHAR(20) NOT NULL,governorate VARCHAR(80) NOT NULL,address VARCHAR(500) NOT NULL,postal_code CHAR(4) NOT NULL,notes VARCHAR(500) NOT NULL DEFAULT '',
 subtotal_millimes BIGINT UNSIGNED NOT NULL,shipping_millimes BIGINT UNSIGNED NOT NULL,total_millimes BIGINT UNSIGNED NOT NULL,payment_method VARCHAR(30) NOT NULL DEFAULT 'cash_on_delivery',
 status ENUM('pending','confirmed','packed','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',inventory_released TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,INDEX(status,created_at),INDEX(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS order_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,variant_id INT UNSIGNED NOT NULL,product_name VARCHAR(120) NOT NULL,color_name VARCHAR(80) NOT NULL,size_label VARCHAR(10) NOT NULL,unit_price_millimes INT UNSIGNED NOT NULL,quantity SMALLINT UNSIGNED NOT NULL,FOREIGN KEY(order_id) REFERENCES orders(id),FOREIGN KEY(variant_id) REFERENCES variants(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS order_history (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,order_id BIGINT UNSIGNED NOT NULL,user_id INT UNSIGNED NULL,status VARCHAR(30) NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(order_id) REFERENCES orders(id),FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL,message TEXT NOT NULL,is_read TINYINT(1) NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO categories VALUES (1,'robes','Les robes',1),(2,'ensembles','Les ensembles',2),(3,'essentiels','Les essentiels',3);
INSERT IGNORE INTO colors VALUES (1,'Blanc perle','#F2EFE6',1);
INSERT IGNORE INTO colors VALUES (2,'Beige sable','#D9C4A9',2);
INSERT IGNORE INTO colors VALUES (3,'Camel','#C19A6B',3);
INSERT IGNORE INTO colors VALUES (4,'Taupe chaud','#8D857A',4);
INSERT IGNORE INTO colors VALUES (5,'Brun terre','#8B5E3C',5);
INSERT IGNORE INTO colors VALUES (6,'Vert olive','#6E7B4F',6);
INSERT IGNORE INTO colors VALUES (7,'Vert sauge','#B1BAA7',7);
INSERT IGNORE INTO colors VALUES (8,'Pierre','#B7AFA3',8);
INSERT IGNORE INTO colors VALUES (9,'Anthracite','#343839',9);
INSERT IGNORE INTO sizes VALUES (1,'XS',1);
INSERT IGNORE INTO sizes VALUES (2,'S',2);
INSERT IGNORE INTO sizes VALUES (3,'M',3);
INSERT IGNORE INTO sizes VALUES (4,'L',4);
INSERT IGNORE INTO sizes VALUES (5,'XL',5);
INSERT IGNORE INTO sizes VALUES (6,'XXL',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (1,1,'lenvol','L’Envol',NULL,'Des étages fluides, une présence légère.','Robe longue ample à étages froncés. Encolure haute volantée avec liens à nouer. Manches longues à doubles volants. Une silhouette qui accompagne le mouvement.',1,1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (1,'product-01-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (2,1,'la-ligne','La Ligne',NULL,'Une ligne nette, un volume libre.','Robe longue évasée à encolure ronde. Couture verticale devant, poches discrètes et manches longues à poignets boutonnés. Une construction épurée sans ceinture.',1,2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (2,'product-02-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (3,1,'la-trame','La Trame',NULL,'Le rythme discret des nervures.','Robe longue ample à nervures verticales devant et aux épaules. Jupe froncée et manches longues généreuses, terminées par des poignets boutonnés.',1,3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (3,'product-03-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (4,1,'le-pli','Le Pli',NULL,'Le volume compose la silhouette.','Robe longue au buste plissé, soulignée par une basque ample et froncée à la taille. Manches bishop à poignets élastiqués et longue jupe souple.',1,4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (4,'product-04-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (5,1,'les-signes','Les Signes',NULL,'Une taille nouée, une allure libre.','Robe longue évasée à patte de cinq boutons. Ceinture à nouer dans le même tissu, manches amples à poignets élastiqués et large finition au bas.',6,5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (5,'product-05-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (6,3,'le-mouvement','Le Mouvement',NULL,'Toute l’ampleur du palazzo.','Pantalon palazzo très large, à taille haute et plis profonds sur le devant. Une longueur ample et une ceinture structurée qui dessinent un volume généreux.',1,6);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (6,'product-06-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (7,3,'larchitecture','L’Architecture',NULL,'Des épaules affirmées, une ligne longue.','Manteau long ouvert à épaules structurées et larges revers crantés. Une silhouette droite et allongée, à superposer sans dissimuler sa construction.',3,7);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (7,'product-07-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (8,2,'lelan','L’Élan',NULL,'Le confort prend de l’allure.','Ensemble composé d’un hoodie ample à manches superposées et d’une jupe longue coordonnée. Panneau décoratif vertical au dos et épaules tombantes.',7,8);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (8,'product-08-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (9,2,'les-plis','Les Plis',NULL,'Une rencontre de lignes et de plis.','Ensemble composé d’une chemise à manches longues et col droit, rentrée dans une jupe longue à larges plis creux et taille haute. Deux pièces coordonnées.',6,9);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (9,'product-09-06','La palette des neuf coloris',6);
INSERT IGNORE INTO products (id,category_id,slug,name,price_millimes,short_description,description,default_color_id,sort_order) VALUES (10,3,'les-passages','Les Passages',NULL,'Les volumes dialoguent avec le détail.','Tunique ample à deux poches plaquées latérales, devant arrondi et dos plus long. Encolure à deux boutons. Manches longues et larges à poignets évasés, bande géométrique ajourée et petits boutons latéraux.',3,10);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-01','La silhouette',1);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-02','Regards croisés — coloris de mise en scène',2);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-03','Face, profil & dos — présentation de la coupe',3);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-04','Une autre allure — coloris de mise en scène',4);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-05','Les détails de la pièce',5);
INSERT IGNORE INTO product_images (product_id,media_id,caption,sort_order) VALUES (10,'product-10-06','La palette des neuf coloris',6);
INSERT IGNORE INTO variants (product_id,color_id,size_id,stock) SELECT p.id,c.id,s.id,0 FROM products p CROSS JOIN colors c CROSS JOIN sizes s;
