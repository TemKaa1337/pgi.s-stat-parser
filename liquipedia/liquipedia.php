<?php

declare(strict_types = 1);

require '../vendor/autoload.php';

use Carbon\Carbon;
use GuzzleHttp\Client;
use simplehtmldom\HtmlWeb;

class Parser
{
    private string $type = 'Weekly_Survival';
    private string $url;
    private string $week;
    private int $tries = 3;
    private int $sleep = 5;
    public array $weekDates;
    public array $weekHolidays;
    private HtmlWeb $dom;

    public function __construct(array $arguments)
    {
        $this->weekDates = [
            date('Y-m-d', strtotime('08.02.2021')).'-'.date('Y-m-d', strtotime('14.02.2021')) => '1-3',
            date('Y-m-d', strtotime('15.02.2021')).'-'.date('Y-m-d', strtotime('21.02.2021')) => '1-3',
            date('Y-m-d', strtotime('22.02.2021')).'-'.date('Y-m-d', strtotime('28.02.2021')) => '1-3',
            date('Y-m-d', strtotime('01.03.2021')).'-'.date('Y-m-d', strtotime('07.03.2021')) => '4-6',
            date('Y-m-d', strtotime('08.03.2021')).'-'.date('Y-m-d', strtotime('14.03.2021')) => '4-6',
            date('Y-m-d', strtotime('15.03.2021')).'-'.date('Y-m-d', strtotime('21.03.2021')) => '4-6'
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

        if (count($arguments) < 5) {
            $this->week = $this->weekDates[$this->getCurrentWeekDates()];

            if (in_array(date('Y-m-d', strtotime('+3 hour')), $this->weekHolidays)) {
                if (date('Y-m-d', strtotime('+3 hour')) > '14:30') {
                    $this->type = 'Weekly_Finals';
                }
            }
        } else {
            $this->type = $arguments[1];
            $this->week = $arguments[2];
            $this->tries = (int)$arguments[3];
            $this->sleep = (int)$arguments[4];
        }

        $this->url = "https://liquipedia.net/pubg/PUBG_Global_Invitational.S/2021/{$this->type}/Week_{$this->week}";
    }

	public function parse() : void
	{
        for ($i = 0; $i < $this->tries; $i ++) {
            $data = $this->sendRequest();
            $this->beautyOutput($data);
            sleep($this->sleep);
        }
	}

	private function sendRequest() : string
    {
        $client = new Client();

        try {
            $response = $client->request('GET', $this->url, [
                'headers' => [
                    ':authority' => 'liquipedia.net',
                    ':method' => 'GET',
                    ':path' => '/pubg/PUBG_Global_Invitational.S/2021/Weekly_Finals/Week_4-6',
                    ':scheme' => 'https',
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                    'accept-encoding' => 'gzip, deflate, br',
                    'accept-language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'cache-control' => 'no-cache',
                    'cookie' => '_ga=GA1.2.727941163.1613679236; _gid=GA1.2.2094144297.1613679236',
                    'pragma' => 'no-cache',
                    'referer' => 'https://liquipedia.net/pubg/PUBG_Global_Invitational.S/2021/Weekly_Finals/Week_1-3',
                    'sec-fetch-dest' => 'document',
                    'sec-fetch-mode' => 'navigate',
                    'sec-fetch-site' => 'same-origin',
                    'sec-fetch-user' => '?1',
                    'upgrade-insecure-requests' => '1',
                    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.182 Safari/537.36'
                ]
            ]);

            $data = $response->getBody()->getContents();
        } catch (Exception $e) {
            echo "Request error: {$e->getMessage()}";
            die();
        }

        return $data;
    }

    private function getCurrentWeekDates() : string
    {
        $now = Carbon::now();

        return $now->startOfWeek()->format('Y-m-d').'-'.$now->endOfWeek()->format('Y-m-d');
    }

    private function beautyOutput(string $data) : void
    {
        $this->dom = new HtmlWeb();
        $this->dom->load($data);

        // TODO: add logic!
    }
}

(new Parser($argv))->parse();