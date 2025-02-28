<?php

namespace App\Http\Controllers;

use App\Models\Company;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function fetchCompanies()
    {
        $response = $this->client->get('https://pt.wikipedia.org/wiki/Capitaliza%C3%A7%C3%A3o_de_mercado');
        $html = (string) $response->getBody();

        $dom = new \simplehtmldom\HtmlDocument();
        $dom->load($html);

        $companies = [];
        $listItems = $dom->find('ol li');

        foreach ($listItems as $index => $item) {
            $companyName = trim($item->find('a', 0)->plaintext);
            $capitalization = trim($item->plaintext);

            preg_match('/US\$ ([\d,\.]+) bilhões/', $capitalization, $matches);
            preg_match('/\((\d{4})\)/', $capitalization, $yearMatches);

            $profit = isset($matches[1]) ? $matches[1] : 'N/A';
            $year = isset($yearMatches[1]) ? $yearMatches[1] : 'N/A';

            $companies[] = [
                'company_name' => $companyName,
                'profit' => $profit,
                'year' => $year,
            ];
        }

        return response()->json($companies);
    }

    public function filterCompanies(Request $request)
    {
        $validated = $request->validate([
            'rule' => 'required|in:greater,smaller,between',
            'billions' => 'required|numeric',
            'range' => 'nullable|array',
            'range.*' => 'numeric',
        ]);


        $companies = Company::all();

        if ($validated['rule'] === 'greater') {
            $companies = $companies->filter(function ($company) use ($validated) {
                return $company->profit > $validated['billions'];
            });
        } elseif ($validated['rule'] === 'smaller') {
            $companies = $companies->filter(function ($company) use ($validated) {
                return $company->profit < $validated['billions'];
            });
        } elseif ($validated['rule'] === 'between' && isset($validated['range'])) {
            $companies = $companies->filter(function ($company) use ($validated) {
                return $company->profit >= $validated['range'][0] && $company->profit <= $validated['range'][1];
            });
        }


        return response()->json($companies->values()->map(function ($company) {
            return [
                'company_name' => $company->company_name,
                'profit' => number_format($company->profit, 2),
                'rank' => $company->rank,
            ];
        }));
    }
}