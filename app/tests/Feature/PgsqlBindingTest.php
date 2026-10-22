<?php

namespace Tests\Feature;

use Illuminate\Database\PostgresConnection;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class PgsqlBindingTest extends TestCase
{
    /**
     * Memastikan opsi PDO::ATTR_EMULATE_PREPARES terpasang pada konfigurasi koneksi pgsql.
     */
    public function test_pgsql_connection_has_emulate_prepares_option(): void
    {
        $options = config('database.connections.pgsql.options', []);

        $this->assertIsArray($options);
        $this->assertArrayHasKey(PDO::ATTR_EMULATE_PREPARES, $options);
        $this->assertTrue($options[PDO::ATTR_EMULATE_PREPARES]);
    }

    /**
     * Menguji query dengan binding bertipe integer, boolean, string, dan null pada PostgresConnection.
     */
    public function test_pgsql_connection_executes_queries_with_typed_bindings(): void
    {
        // Inisialisasi PDO dengan ATTR_EMULATE_PREPARES aktif sesuai konfigurasi pgsql
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $connection = new PostgresConnection($pdo, 'postgres');

        // 1. Eksekusi query SELECT langsung dengan 4 tipe binding
        $intVal = 42;
        $boolVal = true;
        $strVal = 'ERP Bengkel Alat Berat';
        $nullVal = null;

        $results = $connection->select(
            'SELECT ? as int_col, ? as bool_col, ? as str_col, ? as null_col',
            [$intVal, $boolVal, $strVal, $nullVal]
        );

        $this->assertCount(1, $results);
        $row = $results[0];

        $this->assertEquals(42, $row->int_col);
        $this->assertEquals(1, $row->bool_col);
        $this->assertEquals('ERP Bengkel Alat Berat', $row->str_col);
        $this->assertNull($row->null_col);

        // 2. Eksekusi query pada tabel dengan tipe kolom beragam
        $connection->statement('CREATE TABLE test_bindings (
            id INTEGER PRIMARY KEY,
            int_val INTEGER,
            bool_val BOOLEAN,
            str_val TEXT,
            null_val TEXT NULL
        )');

        // Insert row 1 (true, non-null)
        $connection->insert(
            'INSERT INTO test_bindings (id, int_val, bool_val, str_val, null_val) VALUES (?, ?, ?, ?, ?)',
            [1, 100, true, 'Aktif', null]
        );

        // Insert row 2 (false, non-null)
        $connection->insert(
            'INSERT INTO test_bindings (id, int_val, bool_val, str_val, null_val) VALUES (?, ?, ?, ?, ?)',
            [2, 200, false, 'Nonaktif', 'Catatan']
        );

        // Query dengan binding integer
        $intResult = $connection->select('SELECT * FROM test_bindings WHERE int_val = ?', [100]);
        $this->assertCount(1, $intResult);
        $this->assertEquals(1, $intResult[0]->id);

        // Query dengan binding boolean true
        $boolTrueResult = $connection->select('SELECT * FROM test_bindings WHERE bool_val = ?', [true]);
        $this->assertCount(1, $boolTrueResult);
        $this->assertEquals(1, $boolTrueResult[0]->id);

        // Query dengan binding boolean false
        $boolFalseResult = $connection->select('SELECT * FROM test_bindings WHERE bool_val = ?', [false]);
        $this->assertCount(1, $boolFalseResult);
        $this->assertEquals(2, $boolFalseResult[0]->id);

        // Query dengan binding string
        $strResult = $connection->select('SELECT * FROM test_bindings WHERE str_val = ?', ['Aktif']);
        $this->assertCount(1, $strResult);
        $this->assertEquals(1, $strResult[0]->id);

        // Query dengan binding null check
        $nullResult = $connection->select('SELECT * FROM test_bindings WHERE null_val IS ?', [null]);
        $this->assertCount(1, $nullResult);
        $this->assertEquals(1, $nullResult[0]->id);
    }

    /**
     * Menguji query binding pada koneksi pgsql live jika tersedia di environment.
     */
    public function test_live_pgsql_connection_bindings_if_configured(): void
    {
        try {
            $liveConn = DB::connection('pgsql');
            $liveConn->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Koneksi pgsql live tidak tersedia pada environment lokal ini: ' . $e->getMessage());
        }

        $results = $liveConn->select(
            'SELECT ?::integer as int_val, ?::boolean as bool_val, ?::text as str_val, ?::text as null_val',
            [12345, true, 'Koneksi Pooler Supabase', null]
        );

        $this->assertCount(1, $results);
        $this->assertEquals(12345, $results[0]->int_val);
        $this->assertTrue((bool) $results[0]->bool_val);
        $this->assertEquals('Koneksi Pooler Supabase', $results[0]->str_val);
        $this->assertNull($results[0]->null_val);
    }
}
