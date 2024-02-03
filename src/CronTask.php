<?php
declare(strict_types = 1);

namespace Cpsync;

use Cpsync\Mapper\Cron;
use Exception;
use Cpsync\Mapper\Const\Notice;

/**
 * Class CronTask
 * @package Cpsync
 */
class CronTask
{

	public Cron $cron;

	/**
	 * CronTask constructor.
	 * @param Cron $cron
	 */
	public function __construct(Cron $cron)
	{
		$this->cron = $cron;
	}

	/**
	 * Add task to cron_task table
	 * @param array $params
	 * data request - array of values for update or add task to cron_task table
	 * @return void
	 * @throws Exception
	 */
	public function prepareParamsToCron(array $params): void
	{
		$command = ['min' => '', 'hour' => '', 'day' => '', 'month' => '', 'weekday' => ''];
		foreach($command as $key => $value) {
			if(!isset($params[$key])) {
				throw new Exception(__LINE__.': '.Notice::W_HACKING_ATTEMPT);
			}
			$add_key = $params[$key];
			$command[$key] = (preg_match('#specific#i', $add_key))
				? '*/'.$params[$add_key]
				: $params[$add_key];
		}
		$url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'];
		[$origin, $task] = explode('_', $params['crontask']);
		if($origin == 'local') {
			$command['url'] = $url.'/pars/';
			$command['uri'] = 'tool_name='.$params['tool_name'].'&action='.$task;
			$command['full'] = $command['url'].'?'.$command['uri'];
			$command['comment'] = ($task == 'csv')
				? $command['uri'].'@Update/Add products in CSV file proceccing'
				: $command['uri'].'@Additional product info parsing';
			$this->cron->addCronTask($command);
		}
	}
}
