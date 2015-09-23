Opérations OpenSource JobScheduler 
==================================

Le module "Opérations" publie les données extraites des JobSchedulers par le module __Focus__. Le but est d'obtenir une image du contenu des moteurs pour avoir une vision globale.

Base de données
---------------
Contrairement au module de Suivi, les données ne proviennent pas de la base de données du JobScheduler mais d'une base de données dédiée à cet usage. Cette base est actuellement en MySQL/MariaDB, il n'est pour l'instant pas prévu de la porter sur un autre type de base.

On notera que cette base de données ne nécessite pas de sauvegarde dans la mesure où il ne s'agit que d'un cache. En cas de perte de données, le module __Focus__ mettra les tables à jour à la prochaine synchronisation.

Pré-requis
----------
Modules:
- Core
- User
- Focus

Configuration
-------------
Le suivi utilise les données envoyées directement par JobScheduler permettant un suivi en temps réel.

### Lecture des informations dans la base de données Arii

Contenu de **app/config/parameters.yml**:

    database_name:     Test SOS-Paris
    database_dbname:   scheduler
    database_host:     localhost
    database_port:     3306
    database_user:     root
    database_password: null
    database_driver:   mysqli

__v1.5.0__