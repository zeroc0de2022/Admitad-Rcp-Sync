<?php
/*
Date: 10.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Test\Unit;

use Cpsync\Database;
use Cpsync\Mapper\Trait\Common;
use Cpsync\Session;
use Cpsync\Twig\AssetExtension;
use PDO;
use PDOStatement;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class CommonTest extends TestCase
{
	use Common;
	use PHPMock;

	/**
	 * @var PDO|MockObject
	 */
	private PDO|MockObject $mockPdo;
	/**
	 * @var Database|MockObject
	 */
	private Database|MockObject $mockDatabase;
	/**
	 * @var MockObject
	 */
	private MockObject $sessionMock;
	/**
	 * @var MockObject
	 */
	private MockObject $assetExtensionMock;
	/**
	 * @var MockObject
	 */
	private MockObject $statementMock;


	/**
	 *  установка окружения для тестирования
	 * @throws Exception
	 */
	protected function setUp(): void
	{
		parent::setUp();

		# Мок подключения к БД
		$this->pdoMock = $this->createMock(PDO::class);
		# Мок Database класса
		$this->databaseMock = $this->createMock(Database::class);
		# Мок AssetExtension класса
		$this->assetExtensionMock = $this->createMock(AssetExtension::class);
		# Мок Session класса
		$this->sessionMock = $this->createMock(Session::class);
		# Мок состояния PDO PDOStatement
		$this->statementMock = $this->createMock(PDOStatement::class);

		# Объект pdoMock ожидает любое количество раз метод prepare, возвращающий объект PDOStatement
		$this->pdoMock->expects(static::any())->method('prepare')->willReturn($this->statementMock);

		# Объект databaseMock ожидает любое количество раз метод getConnection, возвращающий объект PDO
		$this->databaseMock->expects(static::any())->method('getConnection')->willReturn($this->pdoMock);
	}

	public function testFirstDir()
	{

		// Задаем пути для разных систем
		$nix_path = '/path/to/current/directory';
		$win_path = 'C:\path\to\current\directory';
		// Проверяем, что метод возвращает корректное значение для разных систем
		static::assertEquals('/', $this->firstDir($nix_path));
		static::assertEquals('C:', $this->firstDir($win_path));
	}

	public function testGetStrSize(): void
	{
		// Тестирование для разных размеров в байтах
		static::assertEquals('2.00 GB', $this->getStrSize(2147483648)); // 2 GB
		static::assertEquals('512.00 MB', $this->getStrSize(536870912)); // 512 MB
		static::assertEquals('128.00 KB', $this->getStrSize(131072)); // 128 KB
		static::assertEquals('256 B', $this->getStrSize(256)); // 256 B
		// Тестирование для негативного значения (для обработки случая, если метод должен быть защищен от отрицательных чисел)
		static::assertEquals('-1 B', $this->getStrSize(-1)); // -1 B

		// Тестирование для нулевого значения
		static::assertEquals('0 B', $this->getStrSize()); // 0 B
	}

	public function testGetFreeInPercent()
	{
		$free_space = 68;
		$total_space = 1500;
		$expected_result = round(100 / ($total_space / $free_space), 2);
		static::assertEquals($expected_result, $this->getFreeInPercent($free_space, $total_space));
	}

	public function testExtractContent()
	{
		$p = ['start' => 'start ', 'end' => ' end', 'result' => 'start content1 end start content2 end start content3 end start content4 end', 'type' => 'string', 'eq' => 1, 'expected_result' => 'content1'];
		// Testing for different data types
		// Search the first occurrence between start-end, return as a string
		$actual = $this->extractContent($p['start'], $p['end'], $p['result'], $p['type'], $p['eq']);
		static::assertEquals($p['expected_result'], $actual);

		// Search all occurrences between all start-end lines, return a specific occurrence
		$p['type'] = 'array';
		$p['eq'] = 2;
		$p['expected_result'] = 'content3';
		$actual = $this->extractContent($p['start'], $p['end'], $p['result'], $p['type'], $p['eq']);
		static::assertEquals($p['expected_result'], $actual);

		// Search all occurrences of all lines between start-end, return in an array of all occurrences
		$p['expected_result'] = ['content1', 'content2', 'content3', 'content4'];
		$actual = $this->extractContent($p['start'], $p['end'], $p['result'], $p['type']);
		static::assertEquals($p['expected_result'], $actual);

	}

	public function testDiff2Dates()
	{
		$d_day = 1;
		$d_hour = 0;
		$d_min = 0;
		$d_sec = 0;
		$date1 = 1706443872;
		$date2 = $date1 - (60 - $d_sec) * (60 - $d_min) * (24 - $d_hour) * $d_day;
		// When the first date is greater than the second
		$actual = $this->diffBetween2dates($date1, $date2);
		static::assertEquals($d_day, $actual['day']);
		static::assertEquals($d_hour, $actual['hour']);
		static::assertEquals($d_min, $actual['min']);
		static::assertEquals($d_sec, $actual['sec']);
		static::assertTrue($actual['status']);
		static::assertIsArray($actual, "is not an array");
		// When the first date is less than the second
		$actual = $this->diffBetween2dates($date2, $date1);
		static::assertFalse($actual['status']);
		static::assertEquals("Date-1 must be greater than or equal to Date-2", $actual['message']);
	}

}