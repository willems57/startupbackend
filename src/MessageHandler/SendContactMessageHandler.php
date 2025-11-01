<?php

// src/MessageHandler/SendContactMessageHandler.php
namespace App\MessageHandler;

use App\Message\SendContactMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SendContactMessageHandler
{
    public function __invoke(SendContactMessage $message)
    {
        // Ici tu peux traiter l'envoi d'email, enregistrer le message, etc.
        // Exemple : envoyer un email avec le Mailer
        dump("Message reçu de : " . $message->email);
    }
}