# Model Data

Semua kolom uang `BIGINT` (rupiah). Semua tabel punya `created_at`, `updated_at` kecuali dinyatakan lain. `deleted_at` (soft delete) hanya untuk master data yang disebut.

| Tabel | Kolom penting | Constraint / catatan |
|---|---|---|
| `users` | name, email, password, role (`admin`/`cashier`/`mechanic`), is_active | email unique |
| `customers` | code, name, type (`company`/`individual`), phone, address, payment_term_days (default 0), notes, deleted_at | code unique. Seed satu "Pelanggan Umum" untuk penjualan langsung |
| `services` | code, name, default_price, is_active | code unique, price ≥ 0 |
| `customer_service_rates` | customer_id, service_id, price | unique(customer_id, service_id), price ≥ 0 |
| `suppliers` | name, phone, address | |
| `parts` | sku, name, category, unit, sell_price, avg_cost, qty_on_hand, min_stock, is_active, deleted_at | sku unique; CHECK qty_on_hand ≥ 0; cache dari movements (BR-S1) |
| `purchases` | supplier_id, purchase_date, reference, total, created_by | |
| `purchase_items` | purchase_id, part_id, qty, unit_cost | qty > 0, unit_cost ≥ 0 |
| `stock_movements` | part_id, type, qty_change (bertanda, ≠ 0), unit_cost_snapshot, balance_after, ref_type, ref_id, reason, created_by, created_at | **append-only**, tanpa updated_at; index (part_id, created_at) |
| `equipment_units` | customer_id, brand, model, serial_number, unit_code, hour_meter, notes | |
| `work_orders` | wo_number, customer_id, equipment_unit_id, complaint, diagnosis, status, mechanic_id, opened_at, completed_at, created_by | wo_number unique; index (status), (customer_id) |
| `work_order_items` | work_order_id, item_type (`part`/`service`), part_id, service_id, description, qty, unit_price, unit_cost_snapshot, line_total | qty > 0; CHECK: part → part_id wajib & service_id null, service → sebaliknya; line_total = qty × unit_price |
| `invoices` | invoice_number, customer_id, source_type (`work_order`/`counter_sale`), work_order_id, issue_date, due_date, subtotal, discount_amount, tax_amount, total, status (`issued`/`partially_paid`/`paid`/`void`), void_reason, voided_at, voided_by, created_by | invoice_number unique; total > 0; index (customer_id, status), (due_date); unique parsial: satu invoice non-void per work_order_id |
| `invoice_items` | invoice_id, item_type, part_id, service_id, description, qty, unit_price, unit_cost_snapshot, line_total | sama seperti work_order_items |
| `payments` | invoice_id, paid_at (date), amount, method (`cash`/`transfer`/`other`), reference, status (`valid`/`void`), void_reason, created_by | amount > 0; index (invoice_id), (paid_at) |
| `expenses` | expense_date, category, description, amount, created_by | amount > 0 |
| `audit_logs` | user_id, action, entity_type, entity_id, old_values (json), new_values (json), created_at | append-only |
| `settings` | key, value | key unique (nama bengkel, tax_rate_percent default 0) |

## Relasi inti
customers 1—N equipment_units, work_orders, invoices, customer_service_rates
work_orders 1—N work_order_items; work_orders 1—0..1 invoice aktif
invoices 1—N invoice_items, payments
parts 1—N stock_movements, purchase_items, work_order_items, invoice_items

## Catatan implementasi
- `invoices.status` adalah hasil turunan dari payments (BR-I8); hitung ulang di `PaymentService` setiap pembayaran dibuat/di-void.
- Nomor invoice dan wo_number dibuat dalam transaction dengan lock agar tidak ada duplikat/celah.
- Indeks tambahan boleh ditambah bila ada alasan performa, catat di `docs/DECISIONS.md`.
