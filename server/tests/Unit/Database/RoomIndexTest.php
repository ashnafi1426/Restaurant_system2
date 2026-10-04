<?php

namespace Tests\Unit\Database;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoomIndexTest extends TestCase
{
    /**
     * Test that the floor_id index exists on the rooms table.
     *
     * This test verifies that the database migration successfully created
     * the 'rooms_floor_id_index' index on the 'floor_id' column of the
     * 'rooms' table. This index is critical for optimizing queries that
     * eager load the floor relationship, reducing query time from 60+ seconds
     * to under 2 seconds.
     *
     * Requirements: 2.1, 2.3, 2.4
     *
     * @return void
     */
    public function test_floor_id_index_exists_on_rooms_table(): void
    {
        // Query PostgreSQL's pg_indexes view to check if index exists
        $result = DB::select(
            "SELECT indexname 
             FROM pg_indexes 
             WHERE tablename = 'rooms' 
             AND indexname = 'rooms_floor_id_index'"
        );

        $this->assertCount(
            1,
            $result,
            'The rooms_floor_id_index should exist on the rooms table'
        );

        $this->assertEquals(
            'rooms_floor_id_index',
            $result[0]->indexname,
            'Index name should match exactly'
        );
    }

    /**
     * Test that the floor_id index covers the correct column.
     *
     * This test verifies that the index is specifically on the 'floor_id' column
     * and uses the btree index type for optimal query performance.
     *
     * Requirements: 2.1, 2.3, 2.4
     *
     * @return void
     */
    public function test_floor_id_index_covers_correct_column(): void
    {
        // Query PostgreSQL's pg_indexes view to verify index definition
        $result = DB::select(
            "SELECT indexname, indexdef 
             FROM pg_indexes 
             WHERE tablename = 'rooms' 
             AND indexname = 'rooms_floor_id_index'"
        );

        $this->assertCount(
            1,
            $result,
            'The rooms_floor_id_index should exist in pg_indexes'
        );

        // Verify the index definition includes floor_id
        $indexDef = $result[0]->indexdef;
        $this->assertStringContainsString(
            'floor_id',
            $indexDef,
            'The index definition should include the floor_id column'
        );

        // Verify it's a btree index (default for PostgreSQL)
        $this->assertStringContainsString(
            'btree',
            strtolower($indexDef),
            'The index should be a btree index for optimal performance'
        );
    }

    /**
     * Test that the index name follows Laravel conventions.
     *
     * Verifies that the index follows the standard naming pattern:
     * {table_name}_{column_name}_index
     *
     * Requirements: 2.1, 2.3, 2.4
     *
     * @return void
     */
    public function test_index_name_follows_conventions(): void
    {
        // Query PostgreSQL to get the index name
        $result = DB::select(
            "SELECT indexname 
             FROM pg_indexes 
             WHERE tablename = 'rooms' 
             AND indexname = 'rooms_floor_id_index'"
        );

        $this->assertNotEmpty(
            $result,
            'Index should exist with the conventional name'
        );

        $indexName = $result[0]->indexname;

        // Verify naming convention: table_column_index
        $this->assertStringStartsWith(
            'rooms_',
            $indexName,
            'Index name should start with table name'
        );

        $this->assertStringContainsString(
            'floor_id',
            $indexName,
            'Index name should contain column name'
        );

        $this->assertStringEndsWith(
            '_index',
            $indexName,
            'Index name should end with _index suffix'
        );
    }

    /**
     * Test that the index is on the public schema.
     *
     * Verifies that the index exists in the correct schema.
     *
     * Requirements: 2.1, 2.3, 2.4
     *
     * @return void
     */
    public function test_index_is_in_correct_schema(): void
    {
        // Query PostgreSQL's pg_indexes to verify schema
        $result = DB::select(
            "SELECT schemaname, indexname 
             FROM pg_indexes 
             WHERE tablename = 'rooms' 
             AND indexname = 'rooms_floor_id_index'"
        );

        $this->assertCount(
            1,
            $result,
            'Index should exist in database'
        );

        $this->assertEquals(
            'public',
            $result[0]->schemaname,
            'Index should be in the public schema'
        );
    }
}
