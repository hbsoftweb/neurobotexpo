<?php
declare(strict_types=1);

// ==== EDIT THESE FOR YOUR HOST ====
const DB_HOST = '127.0.0.1';              // or your MySQL host
const DB_NAME = 'neurobot_expo';
const DB_USER = 'root';
const DB_PASS = '';

const API_KEY = 'longrandomkey'; // required for GET endpoints

// Storage locations (relative to project)
const STORAGE_DIR = __DIR__ . '/../storage';
const SELFIES_DIR = STORAGE_DIR . '/selfies';

// CORS (relaxed for dev)
const CORS_ALLOW_ORIGIN = '*';

// --- SMTP (Gmail App Password) ---
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_USER = 'jaymodihbsoftweb@gmail.com'; // must match From
const SMTP_PASS = 'vzvcajuhmkdezuiu';
const SMTP_PORT = 465; // 465 (SSL) or 587 (STARTTLS)

const MAIL_FROM       = SMTP_USER;       // must equal SMTP_USER for Gmail
const MAIL_FROM_NAME  = 'Neurobot Expo';
const MAIL_ADMIN      = 'jaymodihbsoftweb@gmail.com'; // admin notification recipient

