<?php

// src/Message/SendContactMessage.php
namespace App\Message;

class SendContactMessage
{
    public function __construct(
        public string $email,
        public string $subject,
        public string $content
    ) {}
}