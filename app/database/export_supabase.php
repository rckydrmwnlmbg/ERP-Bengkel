<?php

declare(strict_types=1);

$sqlitePath = __DIR__ . '/database.sqlite';
if (!file_exists($sqlitePath)) {
    echo "SQLite database not found at $sqlitePath\n";
    exit(1);
}

$pdo = new PDO("sqlite:$sqlitePath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$out = [];
$out[] = "-- =================================================================";
$out[] = "-- Supabase / PostgreSQL Schema & Demo Data Dump";
$out[] = "-- Generated for Heavy Equipment & Machining ERP";
$out[] = "-- Date: " . date('Y-m-d H:i:s');
$out[] = "-- =================================================================";
$out[] = "";
$out[] = "-- 1. Reset public schema (Clean slate)";
$out[] = "DROP SCHEMA IF EXISTS public CASCADE;";
$out[] = "CREATE SCHEMA public;";
$out[] = "GRANT ALL ON SCHEMA public TO postgres;";
$out[] = "GRANT ALL ON SCHEMA public TO public;";
$out[] = "";
$out[] = "-- Disable constraints temporarily for clean import";
$out[] = "SET statement_timeout = 0;";
$out[] = "SET lock_timeout = 0;";
$out[] = "SET client_encoding = 'UTF8';";
$out[] = "SET standard_conforming_strings = on;";
$out[] = "SET check_function_bodies = false;";
$out[] = "SET xmloption = content;";
$out[] = "SET client_min_messages = warning;";
$out[] = "SET row_security = off;";
$out[] = "";

// 2. Table Definitions (DDL)
$ddl = [
    "CREATE TABLE migrations (
        id SERIAL PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        batch INTEGER NOT NULL
    );",

    "CREATE TABLE users (
        id BIGSERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        email_verified_at TIMESTAMP NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'mechanic',
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        remember_token VARCHAR(100) NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE password_reset_tokens (
        email VARCHAR(255) PRIMARY KEY,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NULL
    );",

    "CREATE TABLE sessions (
        id VARCHAR(255) PRIMARY KEY,
        user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        payload TEXT NOT NULL,
        last_activity INTEGER NOT NULL
    );
    CREATE INDEX sessions_user_id_index ON sessions (user_id);
    CREATE INDEX sessions_last_activity_index ON sessions (last_activity);",

    "CREATE TABLE cache (
        key VARCHAR(255) PRIMARY KEY,
        value TEXT NOT NULL,
        expiration BIGINT NOT NULL
    );
    CREATE INDEX cache_expiration_index ON cache (expiration);",

    "CREATE TABLE cache_locks (
        key VARCHAR(255) PRIMARY KEY,
        owner VARCHAR(255) NOT NULL,
        expiration BIGINT NOT NULL
    );
    CREATE INDEX cache_locks_expiration_index ON cache_locks (expiration);",

    "CREATE TABLE jobs (
        id BIGSERIAL PRIMARY KEY,
        queue VARCHAR(255) NOT NULL,
        payload TEXT NOT NULL,
        attempts SMALLINT NOT NULL,
        reserved_at INTEGER NULL,
        available_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    );
    CREATE INDEX jobs_queue_index ON jobs (queue);",

    "CREATE TABLE job_batches (
        id VARCHAR(255) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        total_jobs INTEGER NOT NULL,
        pending_jobs INTEGER NOT NULL,
        failed_jobs INTEGER NOT NULL,
        failed_job_ids TEXT NOT NULL,
        options TEXT NULL,
        cancelled_at INTEGER NULL,
        created_at INTEGER NOT NULL,
        finished_at INTEGER NULL
    );",

    "CREATE TABLE failed_jobs (
        id BIGSERIAL PRIMARY KEY,
        uuid VARCHAR(255) UNIQUE NOT NULL,
        connection TEXT NOT NULL,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        exception TEXT NOT NULL,
        failed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX failed_jobs_connection_queue_failed_at_index ON failed_jobs (connection, queue, failed_at);",

    "CREATE TABLE settings (
        id BIGSERIAL PRIMARY KEY,
        key VARCHAR(255) UNIQUE NOT NULL,
        value TEXT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE suppliers (
        id BIGSERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        phone VARCHAR(255) NULL,
        address TEXT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE parts (
        id BIGSERIAL PRIMARY KEY,
        sku VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        category VARCHAR(255) NOT NULL,
        unit VARCHAR(255) NOT NULL,
        sell_price BIGINT NOT NULL DEFAULT 0,
        avg_cost BIGINT NOT NULL DEFAULT 0,
        qty_on_hand INTEGER NOT NULL DEFAULT 0,
        min_stock INTEGER NOT NULL DEFAULT 0,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_parts_qty_on_hand CHECK (qty_on_hand >= 0)
    );",

    "CREATE TABLE services (
        id BIGSERIAL PRIMARY KEY,
        code VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        default_price BIGINT NOT NULL DEFAULT 0,
        is_active BOOLEAN NOT NULL DEFAULT TRUE,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE customers (
        id BIGSERIAL PRIMARY KEY,
        code VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL,
        phone VARCHAR(255) NULL,
        address TEXT NULL,
        payment_term_days INTEGER NOT NULL DEFAULT 0,
        notes TEXT NULL,
        deleted_at TIMESTAMP NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE customer_service_rates (
        id BIGSERIAL PRIMARY KEY,
        customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
        service_id BIGINT NOT NULL REFERENCES services(id) ON DELETE CASCADE,
        price BIGINT NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        UNIQUE (customer_id, service_id)
    );",

    "CREATE TABLE equipment_units (
        id BIGSERIAL PRIMARY KEY,
        customer_id BIGINT NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
        brand VARCHAR(255) NOT NULL,
        model VARCHAR(255) NOT NULL,
        serial_number VARCHAR(255) NULL,
        unit_code VARCHAR(255) NULL,
        hour_meter INTEGER NOT NULL DEFAULT 0,
        notes TEXT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE purchases (
        id BIGSERIAL PRIMARY KEY,
        supplier_id BIGINT NOT NULL REFERENCES suppliers(id) ON DELETE CASCADE,
        purchase_date DATE NOT NULL,
        reference VARCHAR(255) NULL,
        total BIGINT NOT NULL DEFAULT 0,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );",

    "CREATE TABLE purchase_items (
        id BIGSERIAL PRIMARY KEY,
        purchase_id BIGINT NOT NULL REFERENCES purchases(id) ON DELETE CASCADE,
        part_id BIGINT NOT NULL REFERENCES parts(id),
        qty INTEGER NOT NULL,
        unit_cost BIGINT NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_purchase_items_qty CHECK (qty > 0)
    );",

    "CREATE TABLE work_orders (
        id BIGSERIAL PRIMARY KEY,
        wo_number VARCHAR(255) UNIQUE NOT NULL,
        customer_id BIGINT NOT NULL REFERENCES customers(id),
        equipment_unit_id BIGINT NOT NULL REFERENCES equipment_units(id),
        complaint TEXT NOT NULL,
        diagnosis TEXT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'open',
        mechanic_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        opened_at TIMESTAMP NOT NULL,
        completed_at TIMESTAMP NULL,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL
    );
    CREATE INDEX work_orders_status_index ON work_orders (status);
    CREATE INDEX work_orders_customer_id_index ON work_orders (customer_id);",

    "CREATE TABLE work_order_items (
        id BIGSERIAL PRIMARY KEY,
        work_order_id BIGINT NOT NULL REFERENCES work_orders(id) ON DELETE CASCADE,
        item_type VARCHAR(50) NOT NULL,
        part_id BIGINT NULL REFERENCES parts(id) ON DELETE SET NULL,
        service_id BIGINT NULL REFERENCES services(id) ON DELETE SET NULL,
        description VARCHAR(255) NULL,
        qty INTEGER NOT NULL,
        unit_price BIGINT NOT NULL,
        unit_cost_snapshot BIGINT NOT NULL DEFAULT 0,
        line_total BIGINT NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_wo_items_qty CHECK (qty > 0),
        CONSTRAINT check_wo_items_type CHECK ((item_type = 'part' AND part_id IS NOT NULL AND service_id IS NULL) OR (item_type = 'service' AND service_id IS NOT NULL AND part_id IS NULL))
    );",

    "CREATE TABLE invoices (
        id BIGSERIAL PRIMARY KEY,
        invoice_number VARCHAR(255) UNIQUE NOT NULL,
        customer_id BIGINT NOT NULL REFERENCES customers(id),
        source_type VARCHAR(50) NOT NULL,
        work_order_id BIGINT NULL REFERENCES work_orders(id) ON DELETE SET NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        subtotal BIGINT NOT NULL,
        discount_amount BIGINT NOT NULL DEFAULT 0,
        tax_amount BIGINT NOT NULL DEFAULT 0,
        total BIGINT NOT NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'issued',
        void_reason TEXT NULL,
        voided_at TIMESTAMP NULL,
        voided_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_invoices_total CHECK (total > 0)
    );
    CREATE INDEX invoices_customer_id_status_index ON invoices (customer_id, status);
    CREATE INDEX invoices_due_date_index ON invoices (due_date);",

    "CREATE TABLE invoice_items (
        id BIGSERIAL PRIMARY KEY,
        invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        item_type VARCHAR(50) NOT NULL,
        part_id BIGINT NULL REFERENCES parts(id) ON DELETE SET NULL,
        service_id BIGINT NULL REFERENCES services(id) ON DELETE SET NULL,
        description VARCHAR(255) NULL,
        qty INTEGER NOT NULL,
        unit_price BIGINT NOT NULL,
        unit_cost_snapshot BIGINT NOT NULL DEFAULT 0,
        line_total BIGINT NOT NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_inv_items_qty CHECK (qty > 0),
        CONSTRAINT check_inv_items_type CHECK ((item_type = 'part' AND part_id IS NOT NULL AND service_id IS NULL) OR (item_type = 'service' AND service_id IS NOT NULL AND part_id IS NULL))
    );",

    "CREATE TABLE payments (
        id BIGSERIAL PRIMARY KEY,
        invoice_id BIGINT NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
        paid_at DATE NOT NULL,
        amount BIGINT NOT NULL,
        method VARCHAR(50) NOT NULL,
        reference VARCHAR(255) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'valid',
        void_reason TEXT NULL,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_payments_amount CHECK (amount > 0)
    );
    CREATE INDEX payments_invoice_id_index ON payments (invoice_id);
    CREATE INDEX payments_paid_at_index ON payments (paid_at);",

    "CREATE TABLE stock_movements (
        id BIGSERIAL PRIMARY KEY,
        part_id BIGINT NOT NULL REFERENCES parts(id),
        type VARCHAR(255) NOT NULL,
        qty_change INTEGER NOT NULL,
        unit_cost_snapshot BIGINT NOT NULL,
        balance_after INTEGER NOT NULL,
        ref_type VARCHAR(255) NULL,
        ref_id BIGINT NULL,
        reason VARCHAR(255) NULL,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT check_stock_movements_qty_change CHECK (qty_change <> 0)
    );
    CREATE INDEX stock_movements_part_id_created_at_index ON stock_movements (part_id, created_at);",

    "CREATE TABLE expenses (
        id BIGSERIAL PRIMARY KEY,
        expense_date DATE NOT NULL,
        category VARCHAR(255) NOT NULL,
        description TEXT NULL,
        amount BIGINT NOT NULL,
        created_by BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP NULL,
        updated_at TIMESTAMP NULL,
        CONSTRAINT check_expenses_amount CHECK (amount > 0)
    );",

    "CREATE TABLE audit_logs (
        id BIGSERIAL PRIMARY KEY,
        user_id BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
        action VARCHAR(255) NOT NULL,
        entity_type VARCHAR(255) NOT NULL,
        entity_id BIGINT NOT NULL,
        old_values JSONB NULL,
        new_values JSONB NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );
    CREATE INDEX audit_logs_entity_type_entity_id_index ON audit_logs (entity_type, entity_id);"
];

foreach ($ddl as $stmt) {
    $out[] = trim($stmt) . "\n";
}

// 3. Data insertion order (topologically sorted)
$tables = [
    'migrations' => ['bool_cols' => []],
    'users' => ['bool_cols' => ['is_active']],
    'settings' => ['bool_cols' => []],
    'suppliers' => ['bool_cols' => []],
    'parts' => ['bool_cols' => ['is_active']],
    'services' => ['bool_cols' => ['is_active']],
    'customers' => ['bool_cols' => []],
    'customer_service_rates' => ['bool_cols' => []],
    'equipment_units' => ['bool_cols' => []],
    'purchases' => ['bool_cols' => []],
    'purchase_items' => ['bool_cols' => []],
    'work_orders' => ['bool_cols' => []],
    'work_order_items' => ['bool_cols' => []],
    'invoices' => ['bool_cols' => []],
    'invoice_items' => ['bool_cols' => []],
    'payments' => ['bool_cols' => []],
    'stock_movements' => ['bool_cols' => []],
    'expenses' => ['bool_cols' => []],
    'audit_logs' => ['bool_cols' => [], 'json_cols' => ['old_values', 'new_values']]
];

$out[] = "-- =================================================================";
$out[] = "-- 2. Seed Data Import";
$out[] = "-- =================================================================";
$out[] = "";

foreach ($tables as $tableName => $opts) {
    $rows = $pdo->query("SELECT * FROM $tableName")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        continue;
    }

    $out[] = "-- Data for $tableName (" . count($rows) . " rows)";
    
    // Chunk inserts into batches of 50
    $chunks = array_chunk($rows, 50);
    foreach ($chunks as $chunk) {
        $cols = array_keys($chunk[0]);
        $colList = implode(', ', array_map(fn($c) => '"' . $c . '"', $cols));
        
        $valueRows = [];
        foreach ($chunk as $row) {
            $vals = [];
            foreach ($cols as $col) {
                $val = $row[$col];
                if ($val === null) {
                    $vals[] = 'NULL';
                } elseif (in_array($col, $opts['bool_cols'] ?? [])) {
                    $vals[] = $val ? 'TRUE' : 'FALSE';
                } elseif (in_array($col, $opts['json_cols'] ?? [])) {
                    $vals[] = "'" . str_replace("'", "''", (string)$val) . "'::jsonb";
                } elseif (is_numeric($val) && !str_starts_with((string)$val, '0') || $val === '0' || $val === 0) {
                    $vals[] = (string)$val;
                } else {
                    $vals[] = "'" . str_replace("'", "''", (string)$val) . "'";
                }
            }
            $valueRows[] = "(" . implode(', ', $vals) . ")";
        }
        
        $out[] = "INSERT INTO $tableName ($colList) VALUES\n  " . implode(",\n  ", $valueRows) . ";";
    }
    
    // Reset sequence if table has id column
    if (in_array('id', array_keys($rows[0]))) {
        $out[] = "SELECT setval(pg_get_serial_sequence('$tableName', 'id'), COALESCE((SELECT MAX(id) FROM $tableName), 1));";
    }
    $out[] = "";
}

$targetFile = dirname(__DIR__, 2) . '/database/supabase_schema_and_seed.sql';
@mkdir(dirname($targetFile), 0777, true);
file_put_contents($targetFile, implode("\n", $out));

echo "Generated: $targetFile (" . number_format(filesize($targetFile)) . " bytes)\n";
