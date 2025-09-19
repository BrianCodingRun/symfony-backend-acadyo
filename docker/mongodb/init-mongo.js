// Script d'initialisation pour MongoDB
print("🚀 Initialisation de la base de données MongoDB...");

// Se connecter à la base db_acadyo
db = db.getSiblingDB("db_acadyo");

// Créer une collection de test
db.createCollection("test_collection");

print("✅ Base de données db_acadyo initialisée avec succès!");
