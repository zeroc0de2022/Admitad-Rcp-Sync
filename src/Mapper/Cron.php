<?php
declare(strict_types = 1);
/***
 * Description of Cron
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 * Date 13.05.2023
 */

namespace Cpsync\Mapper;

use TiBeN\CrontabManager\CrontabAdapter;
use TiBeN\CrontabManager\CrontabJob;
use TiBeN\CrontabManager\CrontabRepository;
use Cpsync\Mapper\Const\Notice;
use Exception;
use Cpsync\Mapper\Trait\Message;

/**
 * Class Cron - Add, remove and get task from cron
 * @package Cpsync\Mapper
 */
class Cron
{
	use Message;

	/**
	 * Add task to cron
	 * @param array $command - Data for cron task
	 * @throws Exception
	 */
	public function addCronTask(array $command): void
	{
		# init crontab repository
		$crontab_repository = new CrontabRepository(new CrontabAdapter());
		// check if job already exists
		$results = $crontab_repository->findJobByRegex('/'.$command['uri'].'/');
		// if not exists, create new job
		if(!count($results)){
			$crontab_job = new CrontabJob();
			$crontab_job
				->setMinutes($command['min'])
				->setHours($command['hour'])
				->setDayOfMonth($command['day'])
				->setMonths($command['month'])
				->setDayOfWeek($command['weekday'])
				->setTaskCommandLine('curl "'.$command['full'].'" >/dev/null 2>&1')
				->setComments($command['comment']);
			$crontab_repository->addJob($crontab_job);
		}
		else {
			$crontab_job = $results[0];
			$crontab_job->setMinutes($command['min']);
			$crontab_job->setHours($command['hour']);
			$crontab_job->setDayOfMonth($command['day']);
			$crontab_job->setMonths($command['month']);
			$crontab_job->setDayOfWeek($command['weekday']);
		}
		$crontab_repository->persist();
	}

	/**
	 * Get task from cron
	 * @param $tool_name - tool name for search task cron
	 * @throws Exception
	 */
	public function getCronTask($tool_name): array
	{
		$crontab_repository = new CrontabRepository(new CrontabAdapter());
		return $crontab_repository->findJobByRegex('/'.$tool_name.'/');
	}

	/**
	 * Remove task from cron
	 * @param $task_id - Cron task id
	 */
	public function removeCronTask($task_id): void
	{
		$crontab_repository = new CrontabRepository(new CrontabAdapter());
		$results = $crontab_repository->findJobByRegex('/'.$task_id.'/');
		$this->setMessage(Notice::E_TASK_NOT_FOUND, true);
		if(isset($results[0])) {
			$crontab_job = $results[0];
			$crontab_repository->removeJob($crontab_job);
			$crontab_repository->persist();
			$this->setMessage(Notice::N_TASK_DELETED, true);
		}
		print_r($this->getMessageJson());
	}

}