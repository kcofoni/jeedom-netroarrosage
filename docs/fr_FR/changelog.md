# Changelog plugin Netro Arrosage

>**IMPORTANT**
>
>S'il n'y a pas d'information sur la mise à jour, c'est que celle-ci concerne uniquement de la mise à jour de documentation, de traduction ou de texte.

# 6/5/2023
- Ajoute le niveau de batterie du contrôleur, utile pour les modèles autonomes (par ex. Pixie)
- Génère des noms alternatifs lors de la création d'équipements dont les noms Netro sont déjà pris dans Jeedom
- Intégration du modèle Pixie
- Possibilité de gérer plusieurs contrôleurs
- Récupération du nom et des informations de version des capteurs, affichés dans l'onglet "Equipement"

>**ATTENTION** : Il est conseillé de refaire une synchronisation après montée de version pour mettre à jour les informations sur les équipements (opération non destructive donc sans risque à priori)

# 23/2/2023
correction d'une anomalie concernant la date de prévision du prochain arrosage

# 15/1/2023
L’écran de configuration permet désormais d’étendre la période d’obtention de l’historique et des prévisions d’arrosage (voir documentation)

# 10/12/2022
Ajout d'une commande info (*et alors*) donnant un statut textuel de la zone.

# 3/12/2022
Ajout d'une commande info donnant le nombre de token restant à consommer d'ici la fin de la journée sur l'API de Netro

# 11/11/2022
Finalisation de la traduction en anglais. La traduction du nom des commandes est réalisée au moment de la synchronisation. Il faut donc refaire une synchro après avoir changé de langue si l'on veut les commandes dans la langue cible. L'ensemble des propriétés des commandes existantes est préservé de sorte que le changement de nom n'a aucun impact par ailleurs.
