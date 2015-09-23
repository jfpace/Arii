Plan journalier 
===============

Le plan journalier récapitule les lancements pour un jour donné. Il permet de simuler un plan comme le ferait un ordonnanceur avec un moteur de type plan.

Ce module n'est pas inclu dans le JID car il ne s'appuie pas sur les données que moteur injecte dans la base mais sur des tables remplis par un job java.

Pré-requis
----------
Modules:
- Core
- User

Configuration
-------------

### Lecture des informations dans la base de données

Contenu de **app/config/parameters.yml**:

    repository_name:     Test SOS-Paris
    repository_dbname:   scheduler
    repository_host:     %database_host%
    repository_port:     %database_port%
    repository_user:     %database_user%
    repository_password: %database_password%
    repository_driver:   %database_driver%

__v1.5.0__