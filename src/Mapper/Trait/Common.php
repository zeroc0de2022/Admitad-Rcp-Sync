<?php
declare(strict_types = 1);
/***
 * Date 26.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 * helper class. it contains common methods that are used in different classes
 */

namespace Cpsync\Mapper\Trait;

/**
 * Common
 *
 */
trait Common
{

	public int $start = 0;

	/**
	 * Returns the first directory
	 * @param string $path
	 * @return string
	 */
	public function firstDir(string $path): string
	{
		if((str_contains($path, '/') || str_contains($path, '\\'))) {
			$home_cwd = str_replace('\\', '/', $path);
			return ($home_cwd[0] == '/') ? substr($home_cwd, 0, 1) : substr($home_cwd, 0, strpos($home_cwd, '/'));
		}
		$path = $this->getFullPath();
		return $this->firstDir($path);
	}

	/**
	 * Converts the size in bytes into a convenient form
	 * @param int $bytes
	 * @return string
	 */
	public function getStrSize(int $bytes = 0): string
	{
		if ($bytes === 0) {
			return '0 B';
		}
		$units = ['B', 'KiB', 'MiB', 'GiB'];
		$base = log($bytes, 1024);
		return sprintf('%1.2f', $bytes / (1 << (10 * floor($base)))).' '.$units[floor($base)];
	}

	/**
	 * Returns the percentage of free space on the disk
	 * @param int $freeSpace
	 * @param int $totalSpace
	 * @return float
	 */
	public function getFreeInPercent(int $freeSpace, int $totalSpace): float
	{
		return round(100 / ($totalSpace / $freeSpace), 2);
	}

	/***
	 * For getting data from the page
	 * @param string $start - beginning of the cut block
	 * @param string $end - end of the cut block
	 * @param string $result - page from which we cut the block
	 * @param string $type - return value type (string/array)
	 * @param int|null $eq - array item number (if array type is specified)
	 * @return string|array - returns an array or string depending on the specified type
	 */
	public function extractContent(string $start, string $end, string $result, string $type = 'string', int $eq = null): array|string
	{
		$start_pattern = preg_quote($start);
		$end_pattern = preg_quote($end);
		$pattern = '~'.$start_pattern.'(.*?)'.$end_pattern.'~s';
		if(!$end_pattern) {
			$pattern = '~'.$start_pattern.'(.*?)~s';
		} elseif(!$start_pattern) {
			$pattern = '~(.*?)'.$end_pattern.'~s';
		}
		$preg_match_func = ($type === 'array') ? 'preg_match_all' : 'preg_match';
		$events = [];
		$preg_match_func($pattern, $result, $events);
		if(!empty($events[1])) {
			$event = (is_numeric($eq)) ? $events[1][$eq] : $events[1];
			return ($type === 'array') ? $event : $events[1];
		}
		return ($type === 'array') ? [] : '';
	}

	/***
	 * Function to get the difference between two dates
	 * @param int $date1 - date in UNIX format
	 * @param int $date2 - date in UNIX format
	 * @return array
	 */
	public function diffBetween2dates(int $date1, int $date2): array
	{
		$list = [];
		$zeroed = function($value) {
			return ($value < 10 ? '0'.(int)$value : (int)$value);
		};
		if($date1 >= $date2) {
			$time_list = ['day' => 86400, 'hour' => 3600, 'min' => 60, 'sec' => 1];
			$difference = ($date1 - $date2);
			foreach($time_list as $key => $value) {
				if($key == 'sec') {
					$list[$key] = $zeroed($difference);
					break;
				}
				$list[$key] = $zeroed(floor($difference / $value));
				$difference = $difference % $value;
			}
			$list['status'] = true;
			$list['message'] = $list['day'].' d. '.$list['hour'].' h. '.$list['min'].' m. '.$list['sec'].' s.';
		} else {
			$list['status'] = false;
			$list['message'] = 'Date-1 must be greater than or equal to Date-2';
		}
		return $list;
	}

	/**
	 * Print message about load and execution time of the script
	 * @param $line
	 * @return void
	 */
	public function codeLoad($line): void
	{
		//$this->start = microtime(true);
		$memory = memory_get_usage();
		$message = nl2br("\n".$line.': Load: '.$this->getStrSize($memory));
		$total_sec = round(microtime(true) - $this->start, 4);
		$sec = $total_sec % 60;
		$min = intval($total_sec / 60);
		$message .= " - Execution time: $min mins $sec sec.\n";
		print_r($message);
	}

	/**
	 * Print array|object|string in a convenient way
	 * @param $any - array|string|object
	 * @param bool $exit - exit program
	 * @param bool $usePre - use pre
	 */
	public function printPre($any, bool $exit = false, bool $usePre = false): void
	{
		print_r($usePre ? '<pre>' : '');
		print_r($any);
		print_r($usePre ? '</pre>' : '');
		if($exit) {
			exit();
		}
	}

	/**
	 * Returns the full path to the current directory
	 * @return string
	 */
	public function getFullPath(): string
	{
		return getcwd();
	}

	/**
	 * Returns the sizes of the space on the disk
	 * - total - total disk size
	 * - free - free space
	 * - free_in_percent - free space in percent
	 * return array
	 */
	public function getHddSize(): array
	{
		$path = $this->getFullPath();
		$first_dir = $this->firstDir($path);
		$free_space = (int)diskfreespace($first_dir);
		$total_space = (int)disk_total_space($first_dir) ?? 1;
		return ['total' => $this->getStrSize($total_space), 'free' => $this->getStrSize($free_space), 'free_in_percent' => $this->getFreeInPercent($free_space, $total_space)];
	}


}