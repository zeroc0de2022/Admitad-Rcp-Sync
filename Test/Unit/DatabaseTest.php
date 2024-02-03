<?php
/*
Date: 07.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Test\Unit;

use Cpsync\Database;
use PDO;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{


	private Database $database;

	/**
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		$connection = $this->createMock(PDO::class);
		$this->database = new Database($connection);
	}

	public function testGetConnection(): void
	{
		$result = $this->database->getConnection();
		static::assertNotEmpty($result, 'invalid result');
		static::assertIsObject($result, 'is not an object');
		static::assertInstanceOf(PDO::class, $result);
	}

}