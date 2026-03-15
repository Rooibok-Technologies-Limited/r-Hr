<?php

class Database {

	// Function to connect to the database and verify it exists
	function create_database($data)
	{
		try {
			// Connect to PostgreSQL (connect to the target database directly)
			$this->conn = new PDO(
				"pgsql:host={$data['hostname']};port=5432;dbname={$data['database']}",
				$data['username'],
				$data['password']
			);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			return false;
		}

		return true;
	}

	// Function to create the tables and fill them with the default data
	function create_tables($data)
	{
		try {
			// Connect to the database
			$conn = new PDO(
				"pgsql:host={$data['hostname']};port=5432;dbname={$data['database']}",
				$data['username'],
				$data['password']
			);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			// Open the default SQL file
			$query = file_get_contents('assets/install_timehrm.sql');
			// Execute the query
			$conn->exec($query);

			$conn = null;
		} catch (PDOException $e) {
			return false;
		}

		return true;
	}
}