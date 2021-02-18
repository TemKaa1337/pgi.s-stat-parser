<?php

declare(strict_types = 1);

require '../vendor/autoload.php';

use GuzzleHttp\Client;
use Carbon\Carbon;

class Parser
{
	private string $url;
	private string $type = 's';
	private int $week;
	private int $tries = 3;
	private int $sleep = 5;
	public array $weekDates;
	public array $weekHolidays;
	private string $graphql = 'https://tjjkdyimqrb7jjnc6m5rpefjtu.appsync-api.eu-west-1.amazonaws.com/graphql';

	public function __construct(array $arguments)
	{
		$this->weekDates = [
			date('Y-m-d', strtotime('08.02.2021')).'-'.date('Y-m-d', strtotime('14.02.2021')) => 1,
			date('Y-m-d', strtotime('15.02.2021')).'-'.date('Y-m-d', strtotime('21.02.2021')) => 2,
			date('Y-m-d', strtotime('22.02.2021')).'-'.date('Y-m-d', strtotime('28.02.2021')) => 3,
			date('Y-m-d', strtotime('01.03.2021')).'-'.date('Y-m-d', strtotime('07.03.2021')) => 4,
			date('Y-m-d', strtotime('08.03.2021')).'-'.date('Y-m-d', strtotime('14.03.2021')) => 5,
			date('Y-m-d', strtotime('15.03.2021')).'-'.date('Y-m-d', strtotime('21.03.2021')) => 6
		];
		$this->weekHolidays = [
			date('Y-m-d', strtotime('13.02.2021')),
			date('Y-m-d', strtotime('14.02.2021')),
			date('Y-m-d', strtotime('20.02.2021')),
			date('Y-m-d', strtotime('21.02.2021')),
			date('Y-m-d', strtotime('27.02.2021')),
			date('Y-m-d', strtotime('28.02.2021')),
			date('Y-m-d', strtotime('06.03.2021')),
			date('Y-m-d', strtotime('07.03.2021')),
			date('Y-m-d', strtotime('13.03.2021')),
			date('Y-m-d', strtotime('14.03.2021')),
			date('Y-m-d', strtotime('20.03.2021')),
			date('Y-m-d', strtotime('21.03.2021'))
		];

		// type = survival/finals
		if (count($arguments) < 5) {
			$this->week = $this->weekDates[$this->getCurrentWeekDates()];

			if (in_array(date('Y-m-d', strtotime('+3 hour')), $this->weekHolidays)) {
				if (date('Y-m-d', strtotime('+3 hour')) > '14:30') {
					$this->type = 'f';
				}
			}
		} else {
			$this->type = $arguments[1];
			$this->week = (int)$arguments[2];
			$this->tries = (int)$arguments[3];
			$this->sleep = (int)$arguments[4];
		}

		$this->setParseParams();
	}

	public function parse() : void
	{
		for ($i = 0; $i < $this->tries; $i ++) {
			$data = $this->sendRequest();
			$this->beautyOutput($data);
			sleep($this->sleep);
		}
	}

	private function sendRequest() : array
	{
		$client = new Client();

		try {
			$response = $client->request('POST', $this->graphql, [
				'body' => json_encode($this->params),
				'headers' => [
					':authority' => 'tjjkdyimqrb7jjnc6m5rpefjtu.appsync-api.eu-west-1.amazonaws.com',
					':method' => 'POST',
					':path' => '/graphql',
					':scheme' => 'https',
					'accept' => '*/*',
					'accept-encoding' => 'gzip, deflate, br',
					'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
					'cache-control' => 'no-cache',
					'content-length' => '666',
					'content-type' => 'application/json',
					'origin' => 'https://twire.gg',
					'pragma' => 'no-cache',
					'referer' => 'https://twire.gg/',
					'sec-fetch-dest' => 'empty',
					'sec-fetch-mode' => 'cors',
					'sec-fetch-site' => 'cross-site',
					'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.150 Safari/537.36',
					'x-amz-user-agent' => 'aws-amplify/2.0.3',
					'x-api-key' => 'da2-vqpq6wms5ndbvhl2r7kvzbpmfi'
				]
			]);
			$data = $response->getBody()->getContents();
		} catch (Exception $e) {
			echo "Request error: {$e->getMessage()}";
			die();
		}

		return json_decode($data, true);
	}

	private function beautyOutput(array $data) : void
	{
		$mostKillsValue = 0;
		$mostWinsValue = 0;
		$mostKills = [];
		$mostWins = [];

		echo PHP_EOL.'Teams points:'.PHP_EOL;
		foreach ($data['data']['platformLeaderboard']['leaderboard'] as $teamStat) {
			echo "    {$teamStat['team']} - {$teamStat['totalPoints']}".PHP_EOL;

			if ($teamStat['kills'] > $mostKillsValue) {
				$mostKillsValue = $teamStat['kills'];
				$mostKills = [$teamStat['team']];
			} else if ($teamStat['kills'] == $mostKillsValue) {
				$mostKills[] = $teamStat['team'];
			}

			if ($teamStat['wins'] > $mostWinsValue) {
				$mostWinsValue = $teamStat['wins'];
				$mostWins = [$teamStat['team']];
			} else if ($teamStat['kills'] == $mostWinsValue) {
				$mostWins[] = $teamStat['team'];
			}
		}

		echo PHP_EOL.'Most kills:'.PHP_EOL;
		foreach ($mostKills as $team) {
			echo "    {$team} - {$mostKillsValue} kills".PHP_EOL;
		}

		echo PHP_EOL.'Most wins:'.PHP_EOL;
		foreach ($mostWins as $team) {
			echo "    {$team} - {$mostWinsValue} wins".PHP_EOL;
		}

		echo '_______________________________';
	}

	private function getCurrentWeekDates() : string
	{
		$now = Carbon::now();

		return $now->startOfWeek()->format('Y-m-d').'-'.$now->endOfWeek()->format('Y-m-d');
	}

	private function setParseParams() : void
	{
		$this->params = [
			'operationName' => 'PlatformLeaderboard',
			'query' => file_get_contents('query.txt'),
			'variables' => [
				'token' => null,
				'tournament' => "pgis-s01-w{$this->type}-w{$this->week}"
			]
		];
	}
}

(new Parser($argv))->parse();