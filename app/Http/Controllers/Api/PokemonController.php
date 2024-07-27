<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MyPokemon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PokemonController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://pokeapi.co/api/v2/',
        ]);
    }


    public function index(Request $request)
    {
        $ability = $request->query('ability');
        $type = $request->query('type');
        $limit = (int) $request->query('limit', 12);
        $offset = (int) $request->query('offset', 0);

        try {
            $filteredPokemons = [];

            if ($ability) {
                $response = $this->client->get("ability/{$ability}");
                $data = json_decode($response->getBody()->getContents(), true);

                foreach ($data['pokemon'] as $pokemon) {
                    $filteredPokemons[] = $pokemon['pokemon']['name'];
                }
            } else if ($type) {
                $response = $this->client->get("type/{$type}");
                $data = json_decode($response->getBody()->getContents(), true);


                foreach ($data['pokemon'] as $pokemon) {
                    $filteredPokemons[] = $pokemon['pokemon']['name'];
                }
            } else {
                $response = $this->client->get("pokemon?limit=1302");
                $data = json_decode($response->getBody()->getContents(), true);
                // return response()->json($data);


                foreach ($data['results'] as $pokemon) {
                    $filteredPokemons[] = $pokemon['name'];
                }
            }


            $totalPokemons = count($filteredPokemons);

            $filteredPokemons = array_slice($filteredPokemons, $offset, $limit);

            $detailedPokemons = [];
            foreach ($filteredPokemons as $pokemonName) {
                $response = $this->client->get("pokemon/{$pokemonName}");
                $details = json_decode($response->getBody()->getContents(), true);
                $detailedPokemons[] = [
                    'name' => $details['name'],
                    'image' => $details['sprites']['other']['home']['front_default'],
                    'types' => array_column($details['types'], 'type', 'name'),
                    'abilities' => array_column($details['abilities'], 'ability', 'name'),
                    'height' => $details['height'],
                    'weight' => $details['weight'],
                ];
            }

            $baseUrl = $request->url();
            $queryParams = $request->query();
            $nextUrl = null;
            $prevUrl = null;

            if ($offset + $limit < $totalPokemons) {
                $nextQuery = array_merge($queryParams, ['offset' => $offset + $limit]);
                $nextUrl = $baseUrl . '?' . http_build_query($nextQuery);
            }

            if ($offset > 0) {
                $prevQuery = array_merge($queryParams, ['offset' => max(0, $offset - $limit)]);
                $prevUrl = $baseUrl . '?' . http_build_query($prevQuery);
            }

            return response()->json([
                'data' => $detailedPokemons,
                'pagination' => [
                    'next' => $nextUrl,
                    'prev' => $prevUrl,
                    'total' => $totalPokemons,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function getType()
    {
        $response = Http::get('https://pokeapi.co/api/v2/type');
        $types = $response->json()['results'];

        return response()->json($types);
    }
    public function getAbility()
    {
        $response = Http::get('https://pokeapi.co/api/v2/ability');
        $types = $response->json()['results'];

        return response()->json($types);
    }

    public function catch(Request $request)
    {

        try {
            $response = $this->client->get("pokemon/{$request->pokemon}");
            $details = json_decode($response->getBody()->getContents(), true);

            $probability = rand(0, 1);
            if ($probability == 1) {
                $pokemon = new MyPokemon();
                $pokemon->name = $details['name'];
                $pokemon->rename_count = 0;
                $pokemon->image =  $details['sprites']['other']['home']['front_default'];
                $pokemon->type = json_encode(array_column($details['types'], 'type', 'name'));
                $pokemon->save();

                return response()->json(['success' => true, 'pokemon' => $pokemon]);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to catch the Pokemon'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        try {
            $response = $this->client->get("pokemon/{$request->pokemon}");
            $details = json_decode($response->getBody()->getContents(), true);

            $own = MyPokemon::where('name', $request->pokemon)->first();
            if (!empty($own)) {
                $details['is_owned'] = true;
                $details['nickname'] = $own->nickname;
            } else {
                $details['is_owned'] = false;
                $details['nickname'] = null;
            }
            return response()->json([
                'success' => true,
                'data' => $details,
                'message' => 'Berhasil mendapatkan data !',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => null,
                // 'message' => 'Gagal mendapatkan data !',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function release(Request $request)
    {
        try {
            $pokemon = MyPokemon::where('name', $request->pokemon)->first();
            $randomNumber = rand(1, 100);

            if ($this->isPrime($randomNumber)) {
                $pokemon->delete();
                return response()->json(['success' => true]);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to release the Pokemon'], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function rename(Request $request)
    {
        $pokemon = MyPokemon::where('name', $request->pokemon)->first();
        $renameCount = $pokemon->rename_count;
        $newNickname = $request->nickname . '-' . $this->fibonacci($renameCount);

        $pokemon->nickname = $newNickname;
        $pokemon->rename_count = $pokemon->rename_count + 1;
        $pokemon->save();

        return response()->json(['success' => true, 'pokemon' => $pokemon]);
    }

    public function myPokemons()
    {
        try {
            $pokemons = MyPokemon::orderBy('created_at', 'desc')->get();
            foreach ($pokemons as $key => $value) {
                $pokemons[$key]['type'] = json_decode($value->type);
            }
            return response()->json([
                'data' => $pokemons
            ]);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    private function isPrime($num)
    {
        if ($num <= 1) return false;
        for ($i = 2; $i < $num; $i++) {
            if ($num % $i == 0) return false;
        }
        return true;
    }

    private function fibonacci($n)
    {
        if ($n <= 1) return $n;
        return $this->fibonacci($n - 1) + $this->fibonacci($n - 2);
    }
}
