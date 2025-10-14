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
