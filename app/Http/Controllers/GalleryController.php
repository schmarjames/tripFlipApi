<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Gallery;
use App\Countries;
use App\Likes;
use App\Tfphotos;
use App\PhotoCategories;
use App\CategoryTagsOfPhotos;

class GalleryController extends Controller
{

  protected $message = [
    "error" => [
      "get_countries" => "Sorry, unable to get your favorite countries",
      "get_country_photos" => "Sorry, unable to get the countries photos"
    ]
  ];

  protected $weatherApiKey = "dc917ecc31f3df833231b3804d609fed";

  public function __construct() {
    \Config::set('auth.model', 'App\User');
    $this->middleware('jwt.auth');
  }


    /**
     * Get categories and total liked photos of each category based
     * on user
     *
     * @param  Request
     * @return Response
     */
    public function getUserCategories(Request $request) {
      $user = \JWTAuth::parseToken()->authenticate();
      $data = \Input::all();

      $categories = PhotoCategories::all()
        ->map(function($category) use ($user) {
          $categoryInfo = [];
          $categoryInfo['likesAmount'] = Likes::select('*')
            ->whereIn('photo_id', function($query) use ($category) {

              $query
                ->from('category_tags_of_photos')
                ->selectRaw('photo_id')
                ->where('category_id', '=', $category->id);

            })
            ->where('user_id', $user->id)
            ->get()
            ->count();

            $categoryInfo['category_id'] = $category->id;
            $categoryInfo['category_name'] = $category->category;
            return $categoryInfo;
        });

        return response()->json($categories);

    }

    public function gallerySearchOptions(Request $request) {
      $user = \JWTAuth::parseToken()->authenticate();

      $options = Tfphotos::select('tfphotos.country_id', 'tfphotos.state_region_id', 'tfphotos.city_id', 'countries.country', 'state_regions.state_region', 'cities.city')
        ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
        ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
        ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
        ->whereIn('tfphotos.id', function($query) use ($user) {

          $query
            ->from('likes')
            ->selectRaw('photo_id')
            ->where('user_id', $user->id);

        })
        ->distinct('tfphotos.country_id')
        ->get();

        return response()->json($options);
    }

    public function getUserLocationCollection(Request $request) {
      $data = \Input::all();
      $user = \JWTAuth::parseToken()->authenticate();
      dd($data);
      if(!is_null($data['locationData'])) {

        list($countryId, $stateRegionId, $cityId) = $data['locationData'];

        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->whereIn('tfphotos.id', function($query) use($user) {

            $query
              ->from('likes')
              ->selectRaw('photo_id')
              ->whereIn('photo_id', 'tfphotos.id');
          });

        if ($countryId)
            $collection->where('tfphotos.country_id', $countryId);

        if ($stateRegionId)
            $collection->where('tfphotos.state_regions_id');

        if ($cityId)
            $collection->where('tfphotos.city_id');


        $collection->where('tfphotos.id', '<', $data['lastQueryId'])
          ->take($data['amount'])
          ->orderBy('created_at', 'desc')
          ->get();

          return response()->json($collection);
      }

      return response()->json("we have a problem");
    }

    public function getUserCategoryCollection(Request $request) {
      $data = \Input::all();
      $user = \JWTAuth::parseToken()->authenticate();

      // If adding more photos to feed
      if (is_numeric($data['lastQueryId']) && !(bool)$data['latest']) {
        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->whereIn('tfphotos.id', function($query) use($data) {

            $query
              ->from('likes')
              ->selectRaw('photo_id')
              ->whereIn('photo_id', function($query) use($data) {
                $query
                  ->from('category_tags_of_photos')
                  ->selectRaw('photo_id')
                  ->where('category_id', '=', $data['category']);
              });
          })
          ->where('tfphotos.id', '<', $data['lastQueryId'])
          ->take($data['amount'])
          ->orderBy('created_at', 'desc')
          ->get();
      }
      // If adding latest photos to feed
      else if (is_numeric($data['lastQueryId']) && (bool)$data['latest']) {
        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->whereIn('tfphotos.id', function($query) use($data) {
            $query
              ->from('likes')
              ->selectRaw('photo_id')
              ->whereIn('photo_id', function($query) use($data) {
                $query
                  ->from('category_tags_of_photos')
                  ->selectRaw('photo_id')
                  ->where('category_id', '=', $data['category']);
              });
          })
          ->where('tfphotos.id', '>', $data['lastQueryId'])
          ->orderBy('created_at', 'asc')
          ->get();
      }
      // If initial query for feed
      else {
        $collection = Tfphotos::select('tfphotos.*', 'location_data.lat', 'location_data.long', 'countries.country', 'state_regions.state_region', 'cities.city', 'counties.county')
          ->join('location_data', 'tfphotos.location_id', '=', 'location_data.id')
          ->join('countries', 'tfphotos.country_id', '=', 'countries.id')
          ->leftJoin('state_regions', 'tfphotos.state_region_id', '=', 'state_regions.id')
          ->leftJoin('cities' , 'tfphotos.city_id', '=', 'cities.id')
          ->leftJoin('counties', 'tfphotos.county_id', '=', 'counties.id')
          ->whereIn('tfphotos.id', function($query) use($data) {
            $query
              ->from('likes')
              ->selectRaw('photo_id')
              ->whereIn('photo_id', function($query) use($data) {
                $query
                  ->from('category_tags_of_photos')
                  ->selectRaw('photo_id')
                  ->where('category_id', '=', $data['category']);
              });
          })
          ->take($data['amount'])
          ->orderBy('created_at', 'desc')
          ->get();
      }

      // include the likes and weather data foreach photo
      $collection = $collection->map(function($photo, $v) use ($user) {
        $photoLikedTotal = Likes::where("photo_id", $photo->id)->select("user_id")->get()->count();
        $userLiked = Likes::where(["photo_id" => $photo->id, "user_id" => $user->id])->select("user_id")->get();

        $photo['likes'] = $photoLikedTotal;
        $photo['likedByUser'] = ($userLiked->count() > 0 ? true : false);
        $photo["weatherCondition"] = $this->getWeatherData($photo["lat"], $photo["long"])["weather"][0]["description"];

        return $photo;
      });
      // return collection of photos and last photos id
      return response()->json($collection);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function getWeatherData($lat, $long) {
      //api.openweathermap.org/data/2.5/weather?lat=" + this.state.lat + "&lon=" + this.state.long + "&APPID=dc917ecc31f3df833231b3804d609fed"
      $url = sprintf(
        "api.openweathermap.org/data/2.5/weather?lat=%s&lon=%s&APPID=%s",
        $lat,
        $long,
        $this->weatherApiKey
      );

      $client = new \GuzzleHttp\Client();
      $response = $client->get($url);

      if ($response->getStatusCode() == 200) {
          $res_data = $response->getBody()->getContents();
          return json_decode($res_data, true);
      }
      return false;
    }
}
