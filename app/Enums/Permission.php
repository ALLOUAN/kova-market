<?php

namespace App\Enums;

/**
 * Back-office permissions. The role → permission matrix lives in Role::permissions().
 */
enum Permission: string
{
    case ViewCatalog = 'catalogue.consulter';
    case ManageCatalog = 'catalogue.gerer';
    case ManagePromotions = 'promotions.gerer';
    case ManageContent = 'contenus.gerer';
    case ManageDelivery = 'livraison.gerer';
    case ViewOrders = 'commandes.consulter';
    case PrepareOrders = 'commandes.preparer';
    case ManageOrders = 'commandes.gerer';
    case ManageSettings = 'parametres.gerer';
    case ManageStaff = 'utilisateurs.gerer';
    case ViewActivityLog = 'journal.consulter';
}
