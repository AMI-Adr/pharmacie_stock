CREATE DATABASE IF NOT EXISTS stockyy;
USE stockyy;

-- Création de la table Utilisateur
CREATE TABLE IF NOT EXISTS Utilisateur (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendeur') DEFAULT 'vendeur',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table Produit
CREATE TABLE IF NOT EXISTS Produit (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    categorie VARCHAR(50) NOT NULL,
    reference VARCHAR(50) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL DEFAULT 0,
    fournisseur VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Création de la table Ventes
CREATE TABLE IF NOT EXISTS Ventes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_total DECIMAL(10,2) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES Utilisateur(id),
    FOREIGN KEY (product_id) REFERENCES Produit(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion des produits
INSERT INTO Produit (nom, categorie, reference, prix, quantite, fournisseur) VALUES
    ('Produit 1', 'Catégorie A', 'REF001', 10.99, 50, 'Fournisseur X'),
    ('Produit 2', 'Catégorie B', 'REF002', 15.49, 30, 'Fournisseur Y'),
    ('Produit 3', 'Catégorie C', 'REF003', 8.99, 20, 'Fournisseur Z'),
    ('Produit 4', 'Catégorie A', 'REF004', 12.99, 40, 'Fournisseur X'),
    ('Produit 5', 'Catégorie B', 'REF005', 9.99, 60, 'Fournisseur Y'),
    ('Produit 6', 'Catégorie C', 'REF006', 18.99, 25, 'Fournisseur Z'),
    ('Produit 7', 'Catégorie A', 'REF007', 22.99, 15, 'Fournisseur X'),
    ('Produit 8', 'Catégorie B', 'REF008', 6.99, 80, 'Fournisseur Y'),
    ('Produit 9', 'Catégorie C', 'REF009', 14.99, 35, 'Fournisseur Z'),
    ('Produit 10', 'Catégorie A', 'REF010', 19.99, 45, 'Fournisseur X');

-- Assurez-vous d'être connecté à la base de données "stockyy"